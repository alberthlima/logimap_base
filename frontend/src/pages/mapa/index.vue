<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { $api } from '@/utils/api'
import { findDistrictForCoordinates } from '@/utils/geo'

definePage({
  meta: {
    permission: 'ver_mapa',
  },
})

// Estado de la Ciudad y Datos
const selectedCity = ref('El Alto')
const cities = ['El Alto', 'La Paz']

const cityConfigs = {
  'El Alto': {
    center: [-16.5100, -68.1700],
    zoom: 12,
    geojsonUrl: '/geojson/elalto_distritos.geojson',
    districtKey: 'name',
    districts: [
      'Distrito 1', 'Distrito 2', 'Distrito 3', 'Distrito 4', 'Distrito 5', 'Distrito 6', 'Distrito 7',
      'Distrito 8', 'Distrito 9', 'Distrito 10', 'Distrito 11', 'Distrito 12', 'Distrito 13', 'Distrito 14'
    ],
  },
  'La Paz': {
    center: [-16.5050, -68.1250],
    zoom: 12,
    geojsonUrl: '/geojson/lapaz_macrodistritos.geojson',
    districtKey: 'macro_ante',
    districts: [
      'CENTRO', 'COTAHUMA', 'MAX PAREDES', 'PERIFERICA', 'SAN ANTONIO', 'SUR', 'MALLASA', 'HAMPATURI', 'ZONGO'
    ],
  },
}

// Colores armoniosos por Distrito
const districtColors = {
  'Distrito 1': '#3B82F6',
  'Distrito 2': '#10B981',
  'Distrito 3': '#F59E0B',
  'Distrito 4': '#EC4899',
  'Distrito 5': '#8B5CF6',
  'Distrito 6': '#06B6D4',
  'Distrito 7': '#6366F1',
  'Distrito 8': '#F97316',
  'Distrito 9': '#14B8A6',
  'Distrito 10': '#E11D48',
  'Distrito 11': '#84CC16',
  'Distrito 12': '#A855F7',
  'Distrito 13': '#0EA5E9',
  'Distrito 14': '#D97706',
  // 7 Macrodistritos Urbanos de La Paz + 2 Rurales
  'CENTRO': '#3B82F6',
  'COTAHUMA': '#10B981',
  'MAX PAREDES': '#EC4899',
  'PERIFERICA': '#06B6D4',
  'SAN ANTONIO': '#F59E0B',
  'SUR': '#8B5CF6',
  'MALLASA': '#F97316',
  'HAMPATURI': '#14B8A6',
  'ZONGO': '#64748B',
}

const statusColors = {
  REGISTRADO: '#F59E0B',
  EN_CAMINO: '#3B82F6',
  ENTREGADO: '#10B981',
  PAGADO: '#8B5CF6',
}

const statusLabels = {
  REGISTRADO: 'Registrado',
  EN_CAMINO: 'En Camino',
  ENTREGADO: 'Entregado',
  PAGADO: 'Pagado',
}

// Filtros
const selectedDistrict = ref('Todos')
const selectedStatus = ref('TODOS')
const searchQuery = ref('')
const showDistrictsLayer = ref(true)
const isDrawerOpen = ref(true)

// Datos cargados de pedidos y clientes
const orders = ref([])
const clients = ref([])
const loading = ref(false)
const selectedOrder = ref(null)

// Diálogos rápidos de georreferenciación desde el mapa
const isQuickClientOpen = ref(false)
const isQuickOrderOpen = ref(false)
const quickLoading = ref(false)

const quickClientForm = ref({
  name: '',
  surname: '',
  business_name: '',
  phone: '',
  city: 'El Alto',
  district: 'Distrito 1',
  zone: '',
  address: '',
  lat: -16.5165,
  lng: -68.1550,
  notes: '',
})

const quickOrderForm = ref({
  client_id: null,
  total_amount: 180.00,
  payment_method: 'Efectivo',
  delivery_address: '',
  city: 'El Alto',
  district: 'Distrito 1',
  lat: -16.5165,
  lng: -68.1550,
  delivery_driver: 'Rodrigo Ramos (Moto 1)',
  items_summary: 'Pedido generado directamente desde el mapa',
  notes: '',
  delivery_date: new Date().toISOString().substr(0, 10),
})

const saveQuickClient = async () => {
  quickLoading.value = true
  try {
    await $api('clients', {
      method: 'POST',
      body: quickClientForm.value,
    })
    isQuickClientOpen.value = false
    await fetchOperationsData()
  } catch (e) {
    console.error('Error al guardar cliente rápido:', e)
  } finally {
    quickLoading.value = false
  }
}

const saveQuickOrder = async () => {
  quickLoading.value = true
  try {
    await $api('orders', {
      method: 'POST',
      body: quickOrderForm.value,
    })
    isQuickOrderOpen.value = false
    await fetchOperationsData()
  } catch (e) {
    console.error('Error al guardar pedido rápido:', e)
  } finally {
    quickLoading.value = false
  }
}

// Mapa y Capas Leaflet
const mapContainer = ref(null)
let mapInstance = null
let geojsonLayer = null
let markersGroup = null

// Obtener color del distrito
const getDistrictColor = (district) => districtColors[district] || '#6366F1'

// Cargar pedidos y clientes de la API
const fetchOperationsData = async () => {
  loading.value = true
  try {
    const ordersRes = await $api('orders?all=1')
    orders.value = ordersRes.orders || []

    const clientsRes = await $api('clients?all=1')
    clients.value = clientsRes.clients || []

    renderMarkers()
  } catch (error) {
    console.error('Error al cargar datos operativos:', error)
  } finally {
    loading.value = false
  }
}

// Pedidos filtrados
const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    // Filtro por ciudad
    if (order.city !== selectedCity.value) return false

    // Filtro por distrito
    if (selectedDistrict.value !== 'Todos' && order.district !== selectedDistrict.value) {
      return false
    }

    // Filtro por estado
    if (selectedStatus.value !== 'TODOS' && order.status !== selectedStatus.value) {
      return false
    }

    // Filtro por búsqueda
    if (searchQuery.value.trim() !== '') {
      const q = searchQuery.value.toLowerCase()
      const clientName = (order.client?.name || '') + ' ' + (order.client?.surname || '') + ' ' + (order.client?.business_name || '')
      const matchCode = order.code?.toLowerCase().includes(q)
      const matchClient = clientName.toLowerCase().includes(q)
      const matchAddress = order.delivery_address?.toLowerCase().includes(q)
      if (!matchCode && !matchClient && !matchAddress) return false
    }

    return true
  })
})

// Conteo por estado en la ciudad actual
const statusCounts = computed(() => {
  const cityOrders = orders.value.filter(o => o.city === selectedCity.value)
  return {
    TODOS: cityOrders.length,
    REGISTRADO: cityOrders.filter(o => o.status === 'REGISTRADO').length,
    EN_CAMINO: cityOrders.filter(o => o.status === 'EN_CAMINO').length,
    ENTREGADO: cityOrders.filter(o => o.status === 'ENTREGADO').length,
    PAGADO: cityOrders.filter(o => o.status === 'PAGADO').length,
  }
})

// Inicializar Mapa
const initMap = () => {
  if (mapInstance) {
    mapInstance.remove()
    mapInstance = null
  }

  const currentConfig = cityConfigs[selectedCity.value]

  mapInstance = L.map(mapContainer.value, {
    center: currentConfig.center,
    zoom: currentConfig.zoom,
    zoomControl: false,
  })

  // Añadir controles de zoom en esquina inferior derecha
  L.control.zoom({ position: 'bottomright' }).addTo(mapInstance)

  // Capa base limpia y profesional de OpenStreetMap (sin marcas de agua)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(mapInstance)

  markersGroup = L.layerGroup().addTo(mapInstance)

  // Georreferenciador al hacer clic en el mapa
  mapInstance.on('click', (e) => {
    handleMapClick(e.latlng)
  })

  loadGeoJsonLayer()
  renderMarkers()
}

let currentGeojsonData = null
let tempGeorefMarker = null

const handleMapClick = (latlng) => {
  const { lat, lng } = latlng
  const config = cityConfigs[selectedCity.value]
  const detected = currentGeojsonData ? findDistrictForCoordinates(lat, lng, currentGeojsonData, config.districtKey) : null
  const districtName = detected || 'Zona Central / Sin delimitar'

  if (tempGeorefMarker && mapInstance) {
    mapInstance.removeLayer(tempGeorefMarker)
  }

  const pinIcon = L.divIcon({
    className: 'temp-georef-pin',
    html: `
      <div style="background:#EF4444; color:#fff; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 0 16px rgba(239,68,68,0.8); border:2px solid #fff; font-size:16px; animation: bounce 1s infinite alternate;">
        📍
      </div>
    `,
    iconSize: [34, 34],
    iconAnchor: [17, 34],
  })

  tempGeorefMarker = L.marker([lat, lng], { icon: pinIcon }).addTo(mapInstance)

  const popupHtml = `
    <div style="min-width: 220px; padding: 6px; font-family: inherit;">
      <div style="font-weight: 700; font-size: 13px; color: #1E293B; margin-bottom: 4px;">
        🎯 Punto Georreferenciado
      </div>
      <div style="font-size: 12px; color: #2563EB; font-weight: 600; margin-bottom: 2px;">
        Distrito: ${districtName} (${selectedCity.value})
      </div>
      <div style="font-size: 11px; color: #64748B; margin-bottom: 8px;">
        GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}
      </div>
      <div style="display: flex; flex-direction: column; gap: 4px;">
        <button id="btn-quick-client" style="background:#2563EB; color:#fff; border:none; padding:6px 10px; border-radius:6px; font-size:11px; font-weight:600; cursor:pointer;">
          + Registrar Cliente Aquí
        </button>
        <button id="btn-quick-order" style="background:#F59E0B; color:#fff; border:none; padding:6px 10px; border-radius:6px; font-size:11px; font-weight:600; cursor:pointer;">
          + Crear Pedido de Entrega Aquí
        </button>
      </div>
    </div>
  `

  tempGeorefMarker.bindPopup(popupHtml, { maxWidth: 280 }).openPopup()

  setTimeout(() => {
    const btnClient = document.getElementById('btn-quick-client')
    const btnOrder = document.getElementById('btn-quick-order')
    if (btnClient) {
      btnClient.onclick = () => {
        quickClientForm.value.lat = Number(lat.toFixed(6))
        quickClientForm.value.lng = Number(lng.toFixed(6))
        quickClientForm.value.city = selectedCity.value
        quickClientForm.value.district = districtName
        isQuickClientOpen.value = true
        tempGeorefMarker.closePopup()
      }
    }
    if (btnOrder) {
      btnOrder.onclick = () => {
        quickOrderForm.value.lat = Number(lat.toFixed(6))
        quickOrderForm.value.lng = Number(lng.toFixed(6))
        quickOrderForm.value.city = selectedCity.value
        quickOrderForm.value.district = districtName
        isQuickOrderOpen.value = true
        tempGeorefMarker.closePopup()
      }
    }
  }, 100)
}

// Cargar Capa GeoJSON de Distritos
const loadGeoJsonLayer = async () => {
  if (geojsonLayer && mapInstance) {
    mapInstance.removeLayer(geojsonLayer)
    geojsonLayer = null
  }

  if (!showDistrictsLayer.value) return

  const config = cityConfigs[selectedCity.value]
  try {
    const res = await fetch(config.geojsonUrl)
    const data = await res.json()
    currentGeojsonData = data

    geojsonLayer = L.geoJSON(data, {
      style: (feature) => {
        const districtName = feature.properties[config.districtKey] || feature.properties.macro_ante || feature.properties.name || feature.properties.macro_vige
        const color = getDistrictColor(districtName)
        const isSelected = selectedDistrict.value === 'Todos' || selectedDistrict.value === districtName

        return {
          fillColor: color,
          weight: isSelected ? 2 : 1,
          opacity: isSelected ? 0.9 : 0.3,
          color: color,
          dashArray: '3',
          fillOpacity: isSelected ? 0.22 : 0.05,
        }
      },
      onEachFeature: (feature, layer) => {
        const districtName = feature.properties[config.districtKey] || feature.properties.macro_ante || feature.properties.name || feature.properties.macro_vige
        const area = feature.properties.area_km2
          ? `${Number(feature.properties.area_km2).toFixed(1)} km²`
          : (feature.properties.ha ? `${(Number(feature.properties.ha) / 100).toFixed(1)} km²` : '')
        const pob = feature.properties.poblacion ? `${feature.properties.poblacion.toLocaleString()} hab.` : ''

        layer.bindTooltip(`
          <div style="font-family: inherit; font-size: 13px; font-weight: 600;">
            ${districtName}
          </div>
          ${area || pob ? `<div style="font-size: 11px; opacity: 0.8;">${area} ${pob}</div>` : ''}
        `, {
          sticky: true,
          direction: 'top',
          className: 'district-tooltip',
        })

        layer.on({
          mouseover: (e) => {
            const l = e.target
            l.setStyle({
              weight: 3,
              fillOpacity: 0.38,
            })
          },
          mouseout: (e) => {
            geojsonLayer.resetStyle(e.target)
          },
          click: (e) => {
            selectedDistrict.value = districtName
            // Si el usuario hace clic en el polígono, georreferenciar ese punto
            L.DomEvent.stopPropagation(e)
            handleMapClick(e.latlng)
          },
        })
      },
    }).addTo(mapInstance)
  } catch (error) {
    console.error('Error al cargar capa GeoJSON:', error)
  }
}

// Crear Icono Personalizado por Distrito y Estado
const createCustomMarkerIcon = (order) => {
  const districtColor = getDistrictColor(order.district)
  const isDelivery = order.status === 'EN_CAMINO'
  
  let iconHtml = ''
  if (order.status === 'REGISTRADO') iconHtml = '⏱'
  else if (order.status === 'EN_CAMINO') iconHtml = '🚚'
  else if (order.status === 'ENTREGADO') iconHtml = '✓'
  else if (order.status === 'PAGADO') iconHtml = 'Bs'

  const html = `
    <div class="custom-map-pin ${isDelivery ? 'pulse-pin' : ''}" style="--pin-color: ${districtColor};">
      <div class="pin-head">
        <span class="pin-symbol">${iconHtml}</span>
      </div>
      <div class="pin-point"></div>
    </div>
  `

  return L.divIcon({
    className: 'custom-leaflet-marker',
    html: html,
    iconSize: [36, 44],
    iconAnchor: [18, 42],
    popupAnchor: [0, -38],
  })
}

// Renderizar Marcadores
const renderMarkers = () => {
  if (!markersGroup || !mapInstance) return

  markersGroup.clearLayers()

  filteredOrders.value.forEach(order => {
    if (!order.lat || !order.lng) return

    const marker = L.marker([order.lat, order.lng], {
      icon: createCustomMarkerIcon(order),
    })

    const clientName = order.client?.business_name || (order.client?.name + ' ' + (order.client?.surname || ''))
    const districtColor = getDistrictColor(order.district)

    const popupContent = `
      <div class="map-popup-card">
        <div class="popup-header" style="border-left: 4px solid ${districtColor};">
          <div class="popup-code">${order.code}</div>
          <div class="popup-badge" style="background-color: ${statusColors[order.status]}20; color: ${statusColors[order.status]};">
            ${statusLabels[order.status]}
          </div>
        </div>
        <div class="popup-body">
          <div class="popup-title">${clientName}</div>
          <div class="popup-info">📍 ${order.delivery_address || 'Sin dirección registrada'}</div>
          <div class="popup-district" style="color: ${districtColor};">📌 ${order.district} (${order.city})</div>
          <div class="popup-amount">Monto: <strong>Bs ${Number(order.total_amount).toFixed(2)}</strong></div>
          ${order.delivery_driver ? `<div class="popup-driver">🚚 ${order.delivery_driver}</div>` : ''}
        </div>
      </div>
    `

    marker.bindPopup(popupContent, { maxWidth: 300 })
    marker.on('click', () => {
      selectedOrder.value = order
    })

    markersGroup.addLayer(marker)
  })
}

// Enfocar un pedido en el mapa
const focusOrder = (order) => {
  selectedOrder.value = order
  if (mapInstance && order.lat && order.lng) {
    mapInstance.flyTo([order.lat, order.lng], 15, {
      duration: 1.2,
    })
  }
}

// Cambiar Estado de Pedido
const updateOrderStatus = async (order, newStatus) => {
  try {
    await $api(`orders/${order.id}/status`, {
      method: 'PATCH',
      body: { status: newStatus },
    })
    order.status = newStatus
    renderMarkers()
  } catch (error) {
    console.error('Error al actualizar estado:', error)
  }
}

// Cambiar de Ciudad
const changeCity = (newCity) => {
  selectedCity.value = newCity
  selectedDistrict.value = 'Todos'
  const config = cityConfigs[newCity]
  if (mapInstance) {
    mapInstance.flyTo(config.center, config.zoom, { duration: 1.5 })
  }
  loadGeoJsonLayer()
  renderMarkers()
}

// Observadores
watch([selectedDistrict, selectedStatus, searchQuery], () => {
  renderMarkers()
})

watch(showDistrictsLayer, () => {
  loadGeoJsonLayer()
})

onMounted(() => {
  nextTick(() => {
    initMap()
    fetchOperationsData()
  })
})
</script>

<template>
  <div class="map-view-wrapper">
    <!-- BARRA SUPERIOR DE CONTROL -->
    <VCard class="mb-4 pa-3" elevation="1">
      <VRow align="center" no-gutters class="gap-y-2">
        <!-- Selector de Ciudad -->
        <VCol cols="12" md="3" class="d-flex align-center gap-2">
          <div class="d-flex align-center gap-1">
            <VBtn
              size="small"
              :variant="selectedCity === 'El Alto' ? 'elevated' : 'tonal'"
              :color="selectedCity === 'El Alto' ? 'primary' : 'default'"
              prepend-icon="ri-map-pin-2-line"
              @click="changeCity('El Alto')"
            >
              El Alto
            </VBtn>
            <VBtn
              size="small"
              :variant="selectedCity === 'La Paz' ? 'elevated' : 'tonal'"
              :color="selectedCity === 'La Paz' ? 'primary' : 'default'"
              prepend-icon="ri-building-line"
              @click="changeCity('La Paz')"
            >
              La Paz
            </VBtn>
          </div>

          <VBtn
            :icon="isDrawerOpen ? 'ri-layout-right-line' : 'ri-layout-right-fill'"
            variant="text"
            color="primary"
            density="comfortable"
            title="Mostrar/Ocultar lista"
            @click="isDrawerOpen = !isDrawerOpen"
          />
        </VCol>

        <!-- Filtro por Distrito -->
        <VCol cols="12" sm="6" md="3" class="px-md-2">
          <VSelect
            v-model="selectedDistrict"
            :items="['Todos', ...cityConfigs[selectedCity].districts]"
            label="Filtrar por Distrito"
            density="compact"
            variant="outlined"
            hide-details
            prepend-inner-icon="ri-compass-3-line"
          />
        </VCol>

        <!-- Búsqueda Rápida -->
        <VCol cols="12" sm="6" md="4" class="px-md-2">
          <VTextField
            v-model="searchQuery"
            placeholder="Buscar por cliente, pedido o dirección..."
            prepend-inner-icon="ri-search-line"
            density="compact"
            variant="outlined"
            hide-details
            clearable
          />
        </VCol>

        <!-- Toggle Polígonos de Distritos -->
        <VCol cols="12" md="2" class="d-flex justify-md-end align-center">
          <VSwitch
            v-model="showDistrictsLayer"
            label="Capas de Distritos"
            color="primary"
            density="compact"
            hide-details
          />
        </VCol>
      </VRow>

      <!-- Pestañas de Estado con Contadores -->
      <VDivider class="my-3" />
      <div class="d-flex flex-wrap align-center gap-2">
        <span class="text-caption font-weight-medium me-1">Estado de Entrega:</span>

        <VChip
          :color="selectedStatus === 'TODOS' ? 'primary' : 'default'"
          :variant="selectedStatus === 'TODOS' ? 'elevated' : 'tonal'"
          class="cursor-pointer"
          @click="selectedStatus = 'TODOS'"
        >
          Todos ({{ statusCounts.TODOS }})
        </VChip>

        <VChip
          :color="selectedStatus === 'REGISTRADO' ? 'warning' : 'default'"
          :variant="selectedStatus === 'REGISTRADO' ? 'elevated' : 'tonal'"
          class="cursor-pointer"
          @click="selectedStatus = 'REGISTRADO'"
        >
          ⏱ Registrados ({{ statusCounts.REGISTRADO }})
        </VChip>

        <VChip
          :color="selectedStatus === 'EN_CAMINO' ? 'info' : 'default'"
          :variant="selectedStatus === 'EN_CAMINO' ? 'elevated' : 'tonal'"
          class="cursor-pointer"
          @click="selectedStatus = 'EN_CAMINO'"
        >
          🚚 En Camino ({{ statusCounts.EN_CAMINO }})
        </VChip>

        <VChip
          :color="selectedStatus === 'ENTREGADO' ? 'success' : 'default'"
          :variant="selectedStatus === 'ENTREGADO' ? 'elevated' : 'tonal'"
          class="cursor-pointer"
          @click="selectedStatus = 'ENTREGADO'"
        >
          ✓ Entregados ({{ statusCounts.ENTREGADO }})
        </VChip>

        <VChip
          :color="selectedStatus === 'PAGADO' ? 'purple' : 'default'"
          :variant="selectedStatus === 'PAGADO' ? 'elevated' : 'tonal'"
          class="cursor-pointer"
          @click="selectedStatus = 'PAGADO'"
        >
          💳 Pagados ({{ statusCounts.PAGADO }})
        </VChip>
      </div>
    </VCard>

    <!-- ÁREA PRINCIPAL: MAPA + PANEL LATERAL -->
    <div class="map-and-sidebar-layout">
      <!-- CONTENEDOR DEL MAPA LEAFLET -->
      <div class="map-wrapper position-relative">
        <div ref="mapContainer" class="leaflet-map-element" />

        <!-- Leyenda Flotante de Colores por Distrito -->
        <div class="district-legend-floating">
          <div class="legend-header">
            <span class="text-caption font-weight-bold">Distritos ({{ selectedCity }})</span>
          </div>
          <div class="legend-items">
            <div
              v-for="dist in cityConfigs[selectedCity].districts"
              :key="dist"
              class="legend-item"
              :class="{ 'active-district': selectedDistrict === dist }"
              @click="selectedDistrict = (selectedDistrict === dist ? 'Todos' : dist)"
            >
              <span class="legend-dot" :style="{ backgroundColor: getDistrictColor(dist) }" />
              <span class="legend-name">{{ dist }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- PANEL LATERAL DE PEDIDOS Y ENTREGAS -->
      <VCard
        v-if="isDrawerOpen"
        class="orders-sidebar"
        elevation="1"
      >
        <VCardItem class="pa-3 border-b">
          <div class="d-flex align-center justify-space-between">
            <VCardTitle class="text-subtitle-1 font-weight-bold">
              Pedidos ({{ filteredOrders.length }})
            </VCardTitle>
            <VBtn
              icon="ri-close-line"
              size="x-small"
              variant="text"
              @click="isDrawerOpen = false"
            />
          </div>
        </VCardItem>

        <div class="orders-list-scroll">
          <div
            v-for="order in filteredOrders"
            :key="order.id"
            class="order-card-item pa-3"
            :class="{ 'order-card-active': selectedOrder?.id === order.id }"
            @click="focusOrder(order)"
          >
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="font-weight-bold text-caption text-primary">{{ order.code }}</span>
              <VChip
                size="x-small"
                :color="statusColors[order.status]"
                variant="tonal"
              >
                {{ statusLabels[order.status] }}
              </VChip>
            </div>

            <div class="text-body-2 font-weight-medium mb-1">
              {{ order.client?.business_name || (order.client?.name + ' ' + (order.client?.surname || '')) }}
            </div>

            <div class="text-caption text-disabled d-flex align-center gap-1 mb-1">
              <VIcon icon="ri-map-pin-line" size="14" />
              <span>{{ order.delivery_address }}</span>
            </div>

            <div class="d-flex align-center justify-space-between mt-2 pt-2 border-t">
              <span
                class="district-pill"
                :style="{ backgroundColor: `${getDistrictColor(order.district)}20`, color: getDistrictColor(order.district) }"
              >
                {{ order.district }}
              </span>
              <span class="font-weight-bold text-body-2">Bs {{ Number(order.total_amount).toFixed(2) }}</span>
            </div>

            <!-- Acciones Rápidas de Estado -->
            <div class="d-flex align-center gap-1 mt-2">
              <VSelect
                :model-value="order.status"
                :items="['REGISTRADO', 'EN_CAMINO', 'ENTREGADO', 'PAGADO']"
                density="compact"
                variant="plain"
                class="status-quick-select"
                hide-details
                @click.stop
                @update:model-value="(val) => updateOrderStatus(order, val)"
              />
              <VBtn
                size="x-small"
                variant="text"
                color="primary"
                icon="ri-focus-3-line"
                title="Centrar en mapa"
                @click.stop="focusOrder(order)"
              />
            </div>
          </div>

          <div v-if="filteredOrders.length === 0" class="pa-6 text-center text-disabled">
            <VIcon icon="ri-inbox-line" size="36" class="mb-2" />
            <p class="text-caption mb-0">No se encontraron pedidos con los filtros actuales</p>
          </div>
        </div>
      </VCard>
    </div>

    <!-- DIÁLOGO RÁPIDO: NUEVO CLIENTE DESDE MAPA -->
    <VDialog v-model="isQuickClientOpen" max-width="600px">
      <VCard>
        <VCardTitle class="pa-4 border-b d-flex justify-space-between align-center">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-map-pin-add-line" color="primary" />
            <span class="font-weight-bold">Registrar Cliente en Coordenada Georreferenciada</span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isQuickClientOpen = false" />
        </VCardTitle>
        <VCardText class="pa-4">
          <div class="mb-3 pa-2 rounded bg-var-theme-surface border d-flex justify-space-between align-center">
            <div>
              <span class="text-caption text-disabled d-block">Distrito Autodetectado:</span>
              <span class="text-body-2 font-weight-bold text-primary">🎯 {{ quickClientForm.district }} ({{ quickClientForm.city }})</span>
            </div>
            <div class="text-end">
              <span class="text-caption text-disabled d-block">GPS Coords:</span>
              <span class="text-caption font-weight-mono">{{ quickClientForm.lat }}, {{ quickClientForm.lng }}</span>
            </div>
          </div>
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="quickClientForm.business_name"
                label="Nombre Comercial / Micromarket"
                placeholder="Ej. Abarrotes San José"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="quickClientForm.name"
                label="Nombre del Propietario"
                placeholder="Juan"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="quickClientForm.phone"
                label="WhatsApp / Teléfono"
                placeholder="71234567"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="quickClientForm.address"
                label="Dirección / Calle"
                placeholder="Av. 6 de Marzo frente al cruce..."
                variant="outlined"
                density="comfortable"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4 border-t d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isQuickClientOpen = false">Cancelar</VBtn>
          <VBtn color="primary" :loading="quickLoading" prepend-icon="ri-save-line" @click="saveQuickClient">
            Guardar Cliente Georreferenciado
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- DIÁLOGO RÁPIDO: NUEVO PEDIDO DESDE MAPA -->
    <VDialog v-model="isQuickOrderOpen" max-width="600px">
      <VCard>
        <VCardTitle class="pa-4 border-b d-flex justify-space-between align-center">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-truck-line" color="warning" />
            <span class="font-weight-bold">Crear Pedido de Entrega en Punto Seleccionado</span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isQuickOrderOpen = false" />
        </VCardTitle>
        <VCardText class="pa-4">
          <div class="mb-3 pa-2 rounded bg-var-theme-surface border d-flex justify-space-between align-center">
            <div>
              <span class="text-caption text-disabled d-block">Distrito Destino:</span>
              <span class="text-body-2 font-weight-bold text-warning">🎯 {{ quickOrderForm.district }} ({{ quickOrderForm.city }})</span>
            </div>
            <div class="text-end">
              <span class="text-caption text-disabled d-block">GPS Entrega:</span>
              <span class="text-caption font-weight-mono">{{ quickOrderForm.lat }}, {{ quickOrderForm.lng }}</span>
            </div>
          </div>
          <VRow>
            <VCol cols="12">
              <VSelect
                v-model="quickOrderForm.client_id"
                :items="clients"
                item-title="business_name"
                item-value="id"
                label="Cliente / Micromarket Destino"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField
                v-model.number="quickOrderForm.total_amount"
                label="Monto del Pedido (Bs)"
                type="number"
                step="0.5"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VSelect
                v-model="quickOrderForm.delivery_driver"
                :items="['Rodrigo Ramos (Moto 1)', 'Marcelo Huanca (Camioneta 3)', 'David Choque (Furgón 2)', 'Alvaro Cruz (Moto 4)']"
                label="Repartidor Asignado"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="quickOrderForm.delivery_address"
                label="Instrucción de Entrega / Dirección"
                placeholder="Punto de entrega marcado en mapa"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
            <VCol cols="12">
              <VTextarea
                v-model="quickOrderForm.items_summary"
                label="Detalle de Productos"
                rows="2"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4 border-t d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isQuickOrderOpen = false">Cancelar</VBtn>
          <VBtn color="warning" :loading="quickLoading" prepend-icon="ri-send-plane-line" @click="saveQuickOrder">
            Generar Pedido en esta Ubicación
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.map-view-wrapper {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 120px);
}

.map-and-sidebar-layout {
  display: flex;
  flex: 1;
  gap: 16px;
  min-height: 500px;
  position: relative;
  overflow: hidden;
}

.map-wrapper {
  flex: 1;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
}

.leaflet-map-element {
  width: 100%;
  height: 100%;
  z-index: 1;
}

/* PANEL LATERAL */
.orders-sidebar {
  width: 340px;
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  overflow: hidden;
  height: 100%;
}

.orders-list-scroll {
  flex: 1;
  overflow-y: auto;
}

.order-card-item {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.04);
  }

  &.order-card-active {
    background-color: rgba(var(--v-theme-primary), 0.08);
    border-left: 4px solid rgb(var(--v-theme-primary));
  }
}

.district-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 12px;
}

.status-quick-select {
  font-size: 11px;
  max-width: 140px;
}

/* LEYENDA FLOTANTE */
.district-legend-floating {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 500;
  background: rgba(var(--v-theme-surface), 0.92);
  color: rgb(var(--v-theme-on-surface));
  backdrop-filter: blur(10px);
  padding: 10px 14px;
  border-radius: 10px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(var(--v-border-color), 0.16);
  max-width: 190px;

  .legend-header {
    margin-bottom: 6px;
  }

  .legend-items {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    cursor: pointer;
    padding: 3px 6px;
    border-radius: 6px;
    transition: background-color 0.15s;

    &:hover, &.active-district {
      background-color: rgba(var(--v-theme-primary), 0.12);
      font-weight: 600;
    }

    .legend-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      flex-shrink: 0;
    }
  }
}
</style>

<style lang="scss">
/* PINES PERSONALIZADOS EN MAPA */
.custom-map-pin {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;

  .pin-head {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: var(--pin-color, #3B82F6);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
    border: 2px solid #ffffff;
    font-size: 14px;
    font-weight: bold;
    z-index: 2;
  }

  .pin-point {
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 8px solid var(--pin-color, #3B82F6);
    margin-top: -2px;
    z-index: 1;
  }

  &.pulse-pin .pin-head {
    animation: pin-pulse 1.6s infinite ease-in-out;
  }
}

@keyframes pin-pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.15); box-shadow: 0 0 16px var(--pin-color); }
  100% { transform: scale(1); }
}

/* POPUP ESTILIZADO */
.leaflet-popup-content-wrapper {
  border-radius: 12px !important;
  padding: 0 !important;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18) !important;
}

.leaflet-popup-content {
  margin: 0 !important;
  line-height: 1.4 !important;
}

.map-popup-card {
  padding: 12px 14px;

  .popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-left: 8px;
    margin-bottom: 8px;
  }

  .popup-code {
    font-size: 13px;
    font-weight: 700;
  }

  .popup-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 8px;
    text-transform: uppercase;
  }

  .popup-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
  }

  .popup-info {
    font-size: 12px;
    color: #64748B;
    margin-bottom: 4px;
  }

  .popup-district {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px;
  }

  .popup-amount {
    font-size: 13px;
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px dashed #E2E8F0;
  }

  .popup-driver {
    font-size: 11px;
    color: #2563EB;
    margin-top: 2px;
  }
}
</style>
