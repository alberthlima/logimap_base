<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { $api } from '@/utils/api'
import { findDistrictForCoordinates } from '@/utils/geo'

definePage({
  meta: {
    permission: 'listar_pedido',
  },
})

const router = useRouter()

// Datos
const orders = ref([])
const clients = ref([])
const loading = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('TODOS')
const selectedCity = ref('TODAS')

// Cache GeoJSON
const geojsonCache = {
  'El Alto': null,
  'La Paz': null,
}

const loadGeojsonCache = async () => {
  try {
    const [resElAlto, resLaPaz] = await Promise.all([
      fetch('/geojson/elalto_distritos.geojson').then(r => r.json()),
      fetch('/geojson/lapaz_macrodistritos.geojson').then(r => r.json()),
    ])
    geojsonCache['El Alto'] = resElAlto
    geojsonCache['La Paz'] = resLaPaz
  } catch (e) {
    console.error('Error cargando GeoJSON en pedidos:', e)
  }
}

// Diálogo de Registro
const isDialogOpen = ref(false)
const formLoading = ref(false)
const detectedDistrict = ref(null)

const orderForm = ref({
  client_id: null,
  total_amount: 150.00,
  payment_method: 'Efectivo',
  delivery_address: '',
  city: 'El Alto',
  district: 'Distrito 1',
  lat: -16.5165,
  lng: -68.1550,
  delivery_driver: 'Rodrigo Ramos (Moto 1)',
  items_summary: '',
  notes: '',
  delivery_date: new Date().toISOString().substr(0, 10),
})

// Mini-mapa Leaflet para Pedidos
const orderMapContainer = ref(null)
let orderMapInstance = null
let orderMarker = null
let orderGeojsonLayer = null

const statusColors = {
  REGISTRADO: 'warning',
  EN_CAMINO: 'info',
  ENTREGADO: 'success',
  PAGADO: 'purple',
}

const statusLabels = {
  REGISTRADO: 'Registrado',
  EN_CAMINO: 'En Camino',
  ENTREGADO: 'Entregado',
  PAGADO: 'Pagado',
}

const driversList = [
  'Rodrigo Ramos (Moto 1)',
  'Marcelo Huanca (Camioneta 3)',
  'David Choque (Furgón 2)',
  'Alvaro Cruz (Moto 4)',
]

// Headers de tabla
const headers = [
  { title: 'CÓDIGO', key: 'code' },
  { title: 'CLIENTE / COMERCIO', key: 'client' },
  { title: 'UBICACIÓN', key: 'district' },
  { title: 'REPARTIDOR', key: 'delivery_driver' },
  { title: 'MONTO (BS)', key: 'total_amount', align: 'end' },
  { title: 'MÉTODO PAGO', key: 'payment_method', align: 'center' },
  { title: 'ESTADO', key: 'status', align: 'center' },
  { title: 'ACCIONES', key: 'actions', sortable: false, align: 'center' },
]

// Cargar Pedidos y Clientes
const fetchOrders = async () => {
  loading.value = true
  try {
    const res = await $api('orders?all=1')
    orders.value = res.orders || []

    const clientsRes = await $api('clients?all=1')
    clients.value = clientsRes.clients || []
  } catch (error) {
    console.error('Error al cargar pedidos:', error)
  } finally {
    loading.value = false
  }
}

// Detectar distrito por coordenadas
const detectDistrict = (lat, lng) => {
  const city = orderForm.value.city
  const geojson = geojsonCache[city]
  const key = city === 'El Alto' ? 'name' : 'macro_vige'
  if (geojson) {
    const found = findDistrictForCoordinates(lat, lng, geojson, key)
    if (found) {
      detectedDistrict.value = found
      orderForm.value.district = found
      return found
    }
  }
  return null
}

// Inicializar el Mini-Mapa de Pedidos
const initOrderMap = () => {
  if (orderMapInstance) {
    orderMapInstance.remove()
    orderMapInstance = null
  }

  const initialLat = orderForm.value.lat || -16.5165
  const initialLng = orderForm.value.lng || -68.1550

  orderMapInstance = L.map(orderMapContainer.value, {
    center: [initialLat, initialLng],
    zoom: 14,
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(orderMapInstance)

  const pinIcon = L.divIcon({
    className: 'draggable-delivery-pin',
    html: `
      <div style="background:#F59E0B;color:#fff;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.4);border:2px solid #fff;font-size:16px;">
        🚚
      </div>
    `,
    iconSize: [34, 34],
    iconAnchor: [17, 34],
  })

  orderMarker = L.marker([initialLat, initialLng], {
    draggable: true,
    icon: pinIcon,
  }).addTo(orderMapInstance)

  orderMarker.on('dragend', (e) => {
    const pos = e.target.getLatLng()
    orderForm.value.lat = Number(pos.lat.toFixed(6))
    orderForm.value.lng = Number(pos.lng.toFixed(6))
    detectDistrict(pos.lat, pos.lng)
  })

  orderMapInstance.on('click', (e) => {
    const { lat, lng } = e.latlng
    orderForm.value.lat = Number(lat.toFixed(6))
    orderForm.value.lng = Number(lng.toFixed(6))
    orderMarker.setLatLng([lat, lng])
    detectDistrict(lat, lng)
  })

  updateOrderGeojsonLayer()
  detectDistrict(initialLat, initialLng)
}

const updateOrderGeojsonLayer = () => {
  if (orderGeojsonLayer && orderMapInstance) {
    orderMapInstance.removeLayer(orderGeojsonLayer)
    orderGeojsonLayer = null
  }

  const city = orderForm.value.city
  const geojson = geojsonCache[city]

  if (geojson && orderMapInstance) {
    orderGeojsonLayer = L.geoJSON(geojson, {
      style: {
        color: '#F59E0B',
        weight: 1.5,
        fillColor: '#F59E0B',
        fillOpacity: 0.1,
        dashArray: '3',
      },
    }).addTo(orderMapInstance)
  }
}

// Al seleccionar cliente
const onClientSelected = (clientId) => {
  const c = clients.value.find(item => item.id === clientId)
  if (c) {
    orderForm.value.delivery_address = c.address || ''
    orderForm.value.city = c.city || 'El Alto'
    orderForm.value.district = c.district || 'Distrito 1'
    orderForm.value.lat = c.lat || -16.5165
    orderForm.value.lng = c.lng || -68.1550

    if (orderMapInstance && c.lat && c.lng) {
      orderMapInstance.flyTo([c.lat, c.lng], 15)
      if (orderMarker) {
        orderMarker.setLatLng([c.lat, c.lng])
      }
      updateOrderGeojsonLayer()
      detectDistrict(c.lat, c.lng)
    }
  }
}

// Pedidos filtrados
const filteredOrders = computed(() => {
  return orders.value.filter(o => {
    if (selectedStatus.value !== 'TODOS' && o.status !== selectedStatus.value) {
      return false
    }
    if (selectedCity.value !== 'TODAS' && o.city !== selectedCity.value) {
      return false
    }
    if (searchQuery.value.trim() !== '') {
      const q = searchQuery.value.toLowerCase()
      const clientName = `${o.client?.name || ''} ${o.client?.business_name || ''}`.toLowerCase()
      const matchCode = o.code?.toLowerCase().includes(q)
      const matchClient = clientName.includes(q)
      const matchDriver = o.delivery_driver?.toLowerCase().includes(q)
      const matchItems = o.items_summary?.toLowerCase().includes(q)
      if (!matchCode && !matchClient && !matchDriver && !matchItems) return false
    }
    return true
  })
})

// Métricas de Pedidos
const stats = computed(() => {
  const total = orders.value.length
  const enCamino = orders.value.filter(o => o.status === 'EN_CAMINO').length
  const entregados = orders.value.filter(o => o.status === 'ENTREGADO').length
  const pagados = orders.value.filter(o => o.status === 'PAGADO').length
  const totalBs = orders.value.reduce((acc, o) => acc + Number(o.total_amount || 0), 0)
  return { total, enCamino, entregados, pagados, totalBs }
})

// Cambiar Estado Rápido
const updateOrderStatus = async (order, newStatus) => {
  try {
    await $api(`orders/${order.id}/status`, {
      method: 'PATCH',
      body: { status: newStatus },
    })
    order.status = newStatus
  } catch (error) {
    console.error('Error al actualizar estado:', error)
  }
}

// Abrir diálogo de creación
const openCreateDialog = () => {
  const defaultClient = clients.value[0]
  orderForm.value = {
    client_id: defaultClient?.id || null,
    total_amount: 250.00,
    payment_method: 'Efectivo',
    delivery_address: defaultClient?.address || '',
    city: defaultClient?.city || 'El Alto',
    district: defaultClient?.district || 'Distrito 1',
    lat: defaultClient?.lat || -16.5165,
    lng: defaultClient?.lng || -68.1550,
    delivery_driver: 'Rodrigo Ramos (Moto 1)',
    items_summary: '10x Aceite Fino, 20x Fideo Corona',
    notes: '',
    delivery_date: new Date().toISOString().substr(0, 10),
  }
  detectedDistrict.value = defaultClient?.district || 'Distrito 1'
  isDialogOpen.value = true

  nextTick(() => {
    setTimeout(initOrderMap, 200)
  })
}

// Guardar Pedido
const saveOrder = async () => {
  formLoading.value = true
  try {
    await $api('orders', {
      method: 'POST',
      body: orderForm.value,
    })
    isDialogOpen.value = false
    await fetchOrders()
  } catch (error) {
    console.error('Error al guardar pedido:', error)
  } finally {
    formLoading.value = false
  }
}

// Eliminar Pedido
const deleteOrder = async (id) => {
  if (!confirm('¿Está seguro de eliminar este pedido?')) return
  try {
    await $api(`orders/${id}`, { method: 'DELETE' })
    await fetchOrders()
  } catch (error) {
    console.error('Error al eliminar pedido:', error)
  }
}

// Ver en Mapa
const viewOnMap = () => {
  router.push({ name: 'mapa' })
}

onMounted(() => {
  fetchOrders()
  loadGeojsonCache()
})
</script>

<template>
  <div class="orders-view-wrapper">
    <!-- TARJETAS DE MÉTRICAS -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">Total Pedidos</div>
              <div class="text-h4 font-weight-bold mt-1 text-primary">{{ stats.total }}</div>
            </div>
            <VAvatar color="primary" variant="tonal" size="48">
              <VIcon icon="ri-shopping-bag-3-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">En Camino 🚚</div>
              <div class="text-h4 font-weight-bold mt-1 text-info">{{ stats.enCamino }}</div>
            </div>
            <VAvatar color="info" variant="tonal" size="48">
              <VIcon icon="ri-truck-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">Entregados & Pagados</div>
              <div class="text-h4 font-weight-bold mt-1 text-success">{{ stats.entregados + stats.pagados }}</div>
            </div>
            <VAvatar color="success" variant="tonal" size="48">
              <VIcon icon="ri-checkbox-circle-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">Volumen Total</div>
              <div class="text-h4 font-weight-bold mt-1 text-purple">Bs {{ stats.totalBs.toFixed(2) }}</div>
            </div>
            <VAvatar color="purple" variant="tonal" size="48">
              <VIcon icon="ri-money-dollar-circle-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- LISTA PRINCIPAL -->
    <VCard elevation="1">
      <VCardItem class="pa-4 border-b">
        <div class="d-flex flex-wrap align-center justify-space-between gap-3">
          <div>
            <VCardTitle class="text-h6 font-weight-bold">
              Despacho de Pedidos y Entregas
            </VCardTitle>
            <VCardSubtitle>
              Flujo operativo: Registrado ➔ En Camino ➔ Entregado ➔ Pagado
            </VCardSubtitle>
          </div>

          <div class="d-flex gap-2">
            <VBtn
              variant="tonal"
              color="primary"
              prepend-icon="ri-map-pin-line"
              @click="viewOnMap"
            >
              Ver en Mapa
            </VBtn>

            <VBtn
              color="primary"
              prepend-icon="ri-map-pin-add-line"
              @click="openCreateDialog"
            >
              Georreferenciar Nuevo Pedido
            </VBtn>
          </div>
        </div>

        <!-- FILTROS -->
        <VRow class="mt-3 gap-y-2" align="center">
          <VCol cols="12" sm="5" md="4">
            <VTextField
              v-model="searchQuery"
              placeholder="Buscar por código, cliente o repartidor..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              hide-details
              clearable
            />
          </VCol>

          <VCol cols="12" sm="3" md="3">
            <VSelect
              v-model="selectedCity"
              :items="['TODAS', 'El Alto', 'La Paz']"
              label="Ciudad"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="4" md="3">
            <VSelect
              v-model="selectedStatus"
              :items="['TODOS', 'REGISTRADO', 'EN_CAMINO', 'ENTREGADO', 'PAGADO']"
              label="Estado de Pedido"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="12" md="2" class="d-flex justify-end">
            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="ri-refresh-line"
              density="comfortable"
              @click="fetchOrders"
            >
              Refrescar
            </VBtn>
          </VCol>
        </VRow>
      </VCardItem>

      <!-- TABLA DE DATOS -->
      <VDataTable
        :headers="headers"
        :items="filteredOrders"
        :loading="loading"
        hover
        class="text-no-wrap"
      >
        <!-- Código -->
        <template #item.code="{ item }">
          <span class="font-weight-bold text-primary">{{ item.code }}</span>
        </template>

        <!-- Cliente / Comercio -->
        <template #item.client="{ item }">
          <div>
            <div class="font-weight-bold text-body-2">
              {{ item.client?.business_name || (item.client?.name + ' ' + (item.client?.surname || '')) }}
            </div>
            <div class="text-caption text-disabled">
              📍 {{ item.delivery_address || 'Sin dirección' }}
            </div>
          </div>
        </template>

        <!-- Ubicación -->
        <template #item.district="{ item }">
          <div>
            <VChip size="small" variant="tonal" color="primary">
              {{ item.district }}
            </VChip>
            <div class="text-caption text-disabled mt-1">{{ item.city }}</div>
          </div>
        </template>

        <!-- Repartidor -->
        <template #item.delivery_driver="{ item }">
          <div class="d-flex align-center gap-1">
            <VIcon icon="ri-steering-line" size="16" color="primary" />
            <span class="text-body-2">{{ item.delivery_driver || 'Sin asignar' }}</span>
          </div>
        </template>

        <!-- Monto -->
        <template #item.total_amount="{ item }">
          <span class="font-weight-bold text-body-1">
            Bs {{ Number(item.total_amount).toFixed(2) }}
          </span>
        </template>

        <!-- Método de Pago -->
        <template #item.payment_method="{ item }">
          <VChip size="small" variant="outlined" color="default">
            {{ item.payment_method }}
          </VChip>
        </template>

        <!-- Estado con Selector Rápido -->
        <template #item.status="{ item }">
          <VSelect
            :model-value="item.status"
            :items="['REGISTRADO', 'EN_CAMINO', 'ENTREGADO', 'PAGADO']"
            density="compact"
            variant="plain"
            hide-details
            class="status-table-select"
            @update:model-value="(val) => updateOrderStatus(item, val)"
          >
            <template #selection="{ item: selectItem }">
              <VChip
                size="small"
                variant="elevated"
                :color="statusColors[selectItem.value]"
              >
                {{ statusLabels[selectItem.value] }}
              </VChip>
            </template>
          </VSelect>
        </template>

        <!-- Acciones -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <VBtn
              icon="ri-map-pin-line"
              size="small"
              variant="text"
              color="primary"
              title="Ver en Monitoreo de Mapa"
              @click="viewOnMap"
            />
            <VBtn
              icon="ri-delete-bin-line"
              size="small"
              variant="text"
              color="error"
              title="Eliminar pedido"
              @click="deleteOrder(item.id)"
            />
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- DIÁLOGO DE NUEVO PEDIDO CON MINI-MAPA DE ENTREGA -->
    <VDialog v-model="isDialogOpen" max-width="980px" persistent>
      <VCard>
        <VCardTitle class="pa-4 border-b d-flex justify-space-between align-center">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-truck-line" color="primary" />
            <span class="font-weight-bold">
              Georreferenciar Entrega y Asignar Pedido
            </span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isDialogOpen = false" />
        </VCardTitle>

        <VCardText class="pa-4">
          <VRow>
            <!-- COLUMNA IZQUIERDA: MINI-MAPA DE ENTREGA -->
            <VCol cols="12" md="6">
              <div class="georeferencer-container">
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-caption font-weight-bold text-primary">
                    📍 Ubicación exacta de descarga / entrega
                  </span>
                  <span class="text-caption text-disabled">
                    Arrastra el camión al punto exacto
                  </span>
                </div>

                <!-- Contenedor del Mapa Leaflet -->
                <div class="dialog-map-box">
                  <div ref="orderMapContainer" class="dialog-map-el" />
                </div>

                <!-- Tarjeta de Detección Automática de Distrito -->
                <div class="detection-alert mt-2 pa-2 d-flex align-center justify-space-between">
                  <div>
                    <span class="text-caption text-disabled d-block">Distrito de Despacho:</span>
                    <span class="text-body-2 font-weight-bold text-warning">
                      🎯 {{ orderForm.district }} ({{ orderForm.city }})
                    </span>
                  </div>
                  <div class="text-end">
                    <span class="text-caption text-disabled d-block">GPS Entrega:</span>
                    <span class="text-caption font-weight-mono">
                      {{ orderForm.lat }}, {{ orderForm.lng }}
                    </span>
                  </div>
                </div>
              </div>
            </VCol>

            <!-- COLUMNA DERECHA: FORMULARIO DEL PEDIDO -->
            <VCol cols="12" md="6">
              <VRow>
                <!-- Seleccionar Cliente -->
                <VCol cols="12">
                  <VSelect
                    v-model="orderForm.client_id"
                    :items="clients"
                    item-title="business_name"
                    item-value="id"
                    label="Cliente / Negocio Destino"
                    variant="outlined"
                    density="comfortable"
                    color="primary"
                    prepend-inner-icon="ri-store-2-line"
                    @update:model-value="onClientSelected"
                  >
                    <template #item="{ props: itemProps, item: clientItem }">
                      <VListItem
                        v-bind="itemProps"
                        :title="clientItem.raw.business_name || (clientItem.raw.name + ' ' + clientItem.raw.surname)"
                        :subtitle="`${clientItem.raw.district} (${clientItem.raw.city}) - ${clientItem.raw.address || 'Sin dir.'}`"
                      />
                    </template>
                  </VSelect>
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField
                    v-model.number="orderForm.total_amount"
                    label="Monto Total (Bs)"
                    type="number"
                    step="0.50"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-money-dollar-circle-line"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VSelect
                    v-model="orderForm.payment_method"
                    :items="['Efectivo', 'QR Simple', 'Transferencia', 'Crédito 15 días']"
                    label="Forma de Pago"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VSelect
                    v-model="orderForm.delivery_driver"
                    :items="driversList"
                    label="Repartidor Asignado"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-steering-line"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="orderForm.delivery_date"
                    label="Fecha de Entrega"
                    type="date"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>

                <VCol cols="12">
                  <VTextField
                    v-model="orderForm.delivery_address"
                    label="Dirección de Descarga"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-map-pin-line"
                  />
                </VCol>

                <VCol cols="12">
                  <VTextarea
                    v-model="orderForm.items_summary"
                    label="Detalle de Productos / Abarrotes"
                    placeholder="Ej. 10x Aceite Fino 900ml, 20x Fideo Corona..."
                    rows="2"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-4 border-t d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isDialogOpen = false">
            Cancelar
          </VBtn>
          <VBtn color="primary" :loading="formLoading" prepend-icon="ri-send-plane-line" @click="saveOrder">
            Generar Pedido Georreferenciado
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.orders-view-wrapper {
  display: flex;
  flex-direction: column;
}

.status-table-select {
  min-width: 140px;
}

.georeferencer-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.dialog-map-box {
  width: 100%;
  height: 320px;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid rgba(var(--v-border-color), 0.2);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.dialog-map-el {
  width: 100%;
  height: 100%;
}

.detection-alert {
  background: rgba(var(--v-theme-warning), 0.08);
  border: 1px solid rgba(var(--v-theme-warning), 0.2);
  border-radius: 8px;
}
</style>
