<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { $api } from '@/utils/api'
import { findDistrictForCoordinates } from '@/utils/geo'

definePage({
  meta: {
    permission: 'listar_cliente',
  },
})

const router = useRouter()

// Datos
const clients = ref([])
const loading = ref(false)
const searchQuery = ref('')
const selectedCity = ref('TODAS')
const selectedDistrict = ref('TODOS')

// Cache GeoJSON para detección automática de distritos
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
    console.error('Error cargando GeoJSON en clientes:', e)
  }
}

// Diálogo de Registro / Edición
const isDialogOpen = ref(false)
const isEditing = ref(false)
const formLoading = ref(false)
const detectedDistrict = ref(null)

const clientForm = ref({
  id: null,
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

// Mini-mapa Leaflet para el Diálogo
const dialogMapContainer = ref(null)
let dialogMapInstance = null
let dialogMarker = null
let dialogGeojsonLayer = null

// Opciones de distritos por ciudad (14 de El Alto y los 7 Macrodistritos Urbanos + 2 Rurales de La Paz)
const districtsByCity = {
  'El Alto': [
    'Distrito 1', 'Distrito 2', 'Distrito 3', 'Distrito 4', 'Distrito 5',
    'Distrito 6', 'Distrito 7', 'Distrito 8', 'Distrito 9', 'Distrito 10',
    'Distrito 11', 'Distrito 12', 'Distrito 13', 'Distrito 14',
  ],
  'La Paz': [
    'CENTRO', 'COTAHUMA', 'MAX PAREDES', 'PERIFERICA',
    'SAN ANTONIO', 'SUR', 'MALLASA', 'HAMPATURI', 'ZONGO',
  ],
}

const currentDistrictOptions = computed(() => {
  return districtsByCity[clientForm.value.city] || []
})

const filterDistrictOptions = computed(() => {
  if (selectedCity.value === 'El Alto') {
    return ['TODOS', ...districtsByCity['El Alto']]
  }
  if (selectedCity.value === 'La Paz') {
    return ['TODOS', ...districtsByCity['La Paz']]
  }
  return ['TODOS', ...districtsByCity['El Alto'], ...districtsByCity['La Paz']]
})

watch(selectedCity, () => {
  selectedDistrict.value = 'TODOS'
})

// Headers de tabla
const headers = [
  { title: 'CLIENTE / COMERCIO', key: 'business_name' },
  { title: 'CONTACTO', key: 'phone' },
  { title: 'CIUDAD', key: 'city' },
  { title: 'DISTRITO', key: 'district' },
  { title: 'ZONA Y DIRECCIÓN', key: 'address' },
  { title: 'PEDIDOS', key: 'orders_count', align: 'center' },
  { title: 'ACCIONES', key: 'actions', sortable: false, align: 'center' },
]

// Cargar Clientes
const fetchClients = async () => {
  loading.value = true
  try {
    const res = await $api('clients?all=1')
    clients.value = res.clients || []
  } catch (error) {
    console.error('Error al cargar clientes:', error)
  } finally {
    loading.value = false
  }
}

// Clientes filtrados
const filteredClients = computed(() => {
  return clients.value.filter(c => {
    if (selectedCity.value !== 'TODAS' && c.city !== selectedCity.value) {
      return false
    }
    if (selectedDistrict.value !== 'TODOS' && c.district !== selectedDistrict.value) {
      return false
    }
    if (searchQuery.value.trim() !== '') {
      const q = searchQuery.value.toLowerCase()
      const fullName = `${c.name || ''} ${c.surname || ''} ${c.business_name || ''} ${c.phone || ''} ${c.address || ''} ${c.zone || ''}`.toLowerCase()
      if (!fullName.includes(q)) return false
    }
    return true
  })
})

// Métricas de Clientes
const stats = computed(() => {
  const total = clients.value.length
  const elAlto = clients.value.filter(c => c.city === 'El Alto').length
  const laPaz = clients.value.filter(c => c.city === 'La Paz').length
  const activeOrders = clients.value.reduce((acc, c) => acc + (c.orders_count || 0), 0)
  return { total, elAlto, laPaz, activeOrders }
})

// Detectar distrito por coordenadas
const detectDistrict = (lat, lng) => {
  const city = clientForm.value.city
  const geojson = geojsonCache[city]
  const key = city === 'El Alto' ? 'name' : 'macro_ante'
  if (geojson) {
    const found = findDistrictForCoordinates(lat, lng, geojson, key)
    if (found) {
      detectedDistrict.value = found
      clientForm.value.district = found
      return found
    }
  }
  return null
}

// Inicializar el Mini-Mapa del Diálogo
const initDialogMap = () => {
  if (dialogMapInstance) {
    dialogMapInstance.remove()
    dialogMapInstance = null
  }

  const initialLat = clientForm.value.lat || -16.5165
  const initialLng = clientForm.value.lng || -68.1550

  dialogMapInstance = L.map(dialogMapContainer.value, {
    center: [initialLat, initialLng],
    zoom: 13,
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(dialogMapInstance)

  // Crear pin arrastrable
  const pinIcon = L.divIcon({
    className: 'draggable-client-pin',
    html: `
      <div style="background:#2563EB;color:#fff;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.4);border:2px solid #fff;font-size:16px;">
        📍
      </div>
    `,
    iconSize: [34, 34],
    iconAnchor: [17, 34],
  })

  dialogMarker = L.marker([initialLat, initialLng], {
    draggable: true,
    icon: pinIcon,
  }).addTo(dialogMapInstance)

  // Evento al arrastrar el marcador
  dialogMarker.on('dragend', (e) => {
    const pos = e.target.getLatLng()
    clientForm.value.lat = Number(pos.lat.toFixed(6))
    clientForm.value.lng = Number(pos.lng.toFixed(6))
    detectDistrict(pos.lat, pos.lng)
  })

  // Evento al hacer clic en el mapa
  dialogMapInstance.on('click', (e) => {
    const { lat, lng } = e.latlng
    clientForm.value.lat = Number(lat.toFixed(6))
    clientForm.value.lng = Number(lng.toFixed(6))
    dialogMarker.setLatLng([lat, lng])
    detectDistrict(lat, lng)
  })

  // Cargar capa GeoJSON de distritos en el mini-mapa
  updateDialogGeojsonLayer()

  // Detectar distrito del punto actual
  detectDistrict(initialLat, initialLng)
}

// Actualizar Capa GeoJSON en el mini-mapa
const updateDialogGeojsonLayer = () => {
  if (dialogGeojsonLayer && dialogMapInstance) {
    dialogMapInstance.removeLayer(dialogGeojsonLayer)
    dialogGeojsonLayer = null
  }

  const city = clientForm.value.city
  const geojson = geojsonCache[city]
  const key = city === 'El Alto' ? 'name' : 'macro_ante'

  if (geojson && dialogMapInstance) {
    dialogGeojsonLayer = L.geoJSON(geojson, {
      style: {
        color: '#3B82F6',
        weight: 1.5,
        fillColor: '#3B82F6',
        fillOpacity: 0.12,
        dashArray: '3',
      },
      onEachFeature: (feature, layer) => {
        const districtName = feature.properties?.[key] || feature.properties?.macro_ante || feature.properties?.name || ''
        if (districtName) {
          layer.bindTooltip(`<b>${districtName}</b>`, { sticky: true })
        }
      },
    }).addTo(dialogMapInstance)
  }
}

// Al cambiar ciudad en el formulario
const onCityChange = (newCity) => {
  clientForm.value.city = newCity
  const defaultCoords = newCity === 'El Alto' ? { lat: -16.5165, lng: -68.1550 } : { lat: -16.5050, lng: -68.1250 }
  clientForm.value.lat = defaultCoords.lat
  clientForm.value.lng = defaultCoords.lng
  clientForm.value.district = newCity === 'El Alto' ? 'Distrito 1' : 'CENTRO'

  if (dialogMapInstance) {
    dialogMapInstance.flyTo([defaultCoords.lat, defaultCoords.lng], 13)
    if (dialogMarker) {
      dialogMarker.setLatLng([defaultCoords.lat, defaultCoords.lng])
    }
    updateDialogGeojsonLayer()
    detectDistrict(defaultCoords.lat, defaultCoords.lng)
  }
}

// Usar Geolocalización del Navegador
const useCurrentLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((pos) => {
      const lat = Number(pos.coords.latitude.toFixed(6))
      const lng = Number(pos.coords.longitude.toFixed(6))
      clientForm.value.lat = lat
      clientForm.value.lng = lng
      if (dialogMapInstance && dialogMarker) {
        dialogMapInstance.flyTo([lat, lng], 15)
        dialogMarker.setLatLng([lat, lng])
        detectDistrict(lat, lng)
      }
    }, () => {
      alert('No se pudo obtener la ubicación GPS actual.')
    })
  }
}

// Abrir diálogo de creación
const openCreateDialog = () => {
  isEditing.value = false
  clientForm.value = {
    id: null,
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
  }
  detectedDistrict.value = null
  isDialogOpen.value = true

  nextTick(() => {
    setTimeout(initDialogMap, 200)
  })
}

// Abrir diálogo de edición
const openEditDialog = (item) => {
  isEditing.value = true
  clientForm.value = { ...item }
  detectedDistrict.value = item.district
  isDialogOpen.value = true

  nextTick(() => {
    setTimeout(initDialogMap, 200)
  })
}

// Guardar Cliente
const saveClient = async () => {
  formLoading.value = true
  try {
    if (isEditing.value) {
      await $api(`clients/${clientForm.value.id}`, {
        method: 'PUT',
        body: clientForm.value,
      })
    } else {
      await $api('clients', {
        method: 'POST',
        body: clientForm.value,
      })
    }
    isDialogOpen.value = false
    await fetchClients()
  } catch (error) {
    console.error('Error al guardar cliente:', error)
  } finally {
    formLoading.value = false
  }
}

// Eliminar Cliente
const deleteClient = async (id) => {
  if (!confirm('¿Está seguro de eliminar este cliente?')) return
  try {
    await $api(`clients/${id}`, { method: 'DELETE' })
    await fetchClients()
  } catch (error) {
    console.error('Error al eliminar cliente:', error)
  }
}

// Redirigir al Mapa con enfoque
const viewOnMap = () => {
  router.push({ name: 'mapa' })
}

onMounted(() => {
  fetchClients()
  loadGeojsonCache()
})
</script>

<template>
  <div class="clients-view-wrapper">
    <!-- TARJETAS DE MÉTRICAS -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">Total Clientes</div>
              <div class="text-h4 font-weight-bold mt-1 text-primary">{{ stats.total }}</div>
            </div>
            <VAvatar color="primary" variant="tonal" size="48">
              <VIcon icon="ri-user-star-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">El Alto</div>
              <div class="text-h4 font-weight-bold mt-1 text-info">{{ stats.elAlto }}</div>
            </div>
            <VAvatar color="info" variant="tonal" size="48">
              <VIcon icon="ri-map-pin-2-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">La Paz</div>
              <div class="text-h4 font-weight-bold mt-1 text-success">{{ stats.laPaz }}</div>
            </div>
            <VAvatar color="success" variant="tonal" size="48">
              <VIcon icon="ri-building-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">Pedidos Históricos</div>
              <div class="text-h4 font-weight-bold mt-1 text-warning">{{ stats.activeOrders }}</div>
            </div>
            <VAvatar color="warning" variant="tonal" size="48">
              <VIcon icon="ri-shopping-cart-2-line" size="26" />
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
              Directorio de Clientes Georreferenciados
            </VCardTitle>
            <VCardSubtitle>
              Puntos comerciales distribuidos por distritos y coordenadas GPS
            </VCardSubtitle>
          </div>

          <VBtn
            color="primary"
            prepend-icon="ri-map-pin-add-line"
            @click="openCreateDialog"
          >
            Georreferenciar Nuevo Cliente
          </VBtn>
        </div>

        <!-- FILTROS -->
        <VRow class="mt-3 gap-y-2" align="center">
          <VCol cols="12" sm="4" md="4">
            <VTextField
              v-model="searchQuery"
              placeholder="Buscar por nombre, comercio, teléfono..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              hide-details
              clearable
            />
          </VCol>

          <VCol cols="12" sm="4" md="3">
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
              v-model="selectedDistrict"
              :items="filterDistrictOptions"
              label="Distrito"
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
              @click="fetchClients"
            >
              Refrescar
            </VBtn>
          </VCol>
        </VRow>
      </VCardItem>

      <!-- TABLA DE DATOS -->
      <VDataTable
        :headers="headers"
        :items="filteredClients"
        :loading="loading"
        hover
        class="text-no-wrap"
      >
        <!-- Cliente / Comercio -->
        <template #item.business_name="{ item }">
          <div class="d-flex align-center gap-3 py-2">
            <VAvatar color="primary" variant="tonal" size="38">
              <VIcon icon="ri-store-2-line" size="20" />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-body-1">{{ item.business_name || 'Sin Razón Social' }}</div>
              <div class="text-caption text-disabled">{{ item.name }} {{ item.surname }}</div>
            </div>
          </div>
        </template>

        <!-- Contacto -->
        <template #item.phone="{ item }">
          <div class="d-flex align-center gap-1">
            <span class="text-body-2">{{ item.phone || 'S/N' }}</span>
            <VBtn
              v-if="item.phone"
              icon="ri-whatsapp-line"
              size="x-small"
              variant="text"
              color="success"
              :href="`https://wa.me/591${item.phone}`"
              target="_blank"
              title="Contactar por WhatsApp"
            />
          </div>
        </template>

        <!-- Ciudad -->
        <template #item.city="{ item }">
          <VChip size="small" variant="tonal" :color="item.city === 'El Alto' ? 'info' : 'success'">
            {{ item.city }}
          </VChip>
        </template>

        <!-- Distrito -->
        <template #item.district="{ item }">
          <VChip size="small" variant="elevated" color="primary">
            {{ item.district }}
          </VChip>
        </template>

        <!-- Zona y Dirección -->
        <template #item.address="{ item }">
          <div>
            <div class="text-body-2 font-weight-medium">{{ item.zone || 'Zona no esp.' }}</div>
            <div class="text-caption text-disabled">{{ item.address || 'Sin dirección' }}</div>
          </div>
        </template>

        <!-- Total Pedidos -->
        <template #item.orders_count="{ item }">
          <VChip size="small" variant="tonal" color="warning">
            {{ item.orders_count || 0 }} pedidos
          </VChip>
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
              @click="viewOnMap(item)"
            />
            <VBtn
              icon="ri-edit-line"
              size="small"
              variant="text"
              color="default"
              title="Editar datos"
              @click="openEditDialog(item)"
            />
            <VBtn
              icon="ri-delete-bin-line"
              size="small"
              variant="text"
              color="error"
              title="Eliminar cliente"
              @click="deleteClient(item.id)"
            />
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- DIÁLOGO DE REGISTRO CON MINI-MAPA GEORREFERENCIADOR -->
    <VDialog v-model="isDialogOpen" max-width="980px" persistent>
      <VCard>
        <VCardTitle class="pa-4 border-b d-flex justify-space-between align-center">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-map-pin-user-line" color="primary" />
            <span class="font-weight-bold">
              {{ isEditing ? 'Editar Georreferenciación de Cliente' : 'Georreferenciar y Registrar Nuevo Cliente' }}
            </span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isDialogOpen = false" />
        </VCardTitle>

        <VCardText class="pa-4">
          <VRow>
            <!-- COLUMNA IZQUIERDA: MAPA INTERACTIVO -->
            <VCol cols="12" md="6">
              <div class="georeferencer-container">
                <div class="d-flex justify-space-between align-center mb-2">
                  <span class="text-caption font-weight-bold text-primary">
                    📍 Haz clic o arrastra el pin al punto del cliente
                  </span>
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="primary"
                    prepend-icon="ri-crosshair-2-line"
                    @click="useCurrentLocation"
                  >
                    Mi Ubicación
                  </VBtn>
                </div>

                <!-- Contenedor del Mapa Leaflet -->
                <div class="dialog-map-box">
                  <div ref="dialogMapContainer" class="dialog-map-el" />
                </div>

                <!-- Tarjeta de Detección Automática de Distrito -->
                <div class="detection-alert mt-2 pa-2 d-flex align-center justify-space-between">
                  <div>
                    <span class="text-caption text-disabled d-block">Distrito Autodetectado:</span>
                    <span class="text-body-2 font-weight-bold text-primary">
                      {{ detectedDistrict ? `🎯 ${detectedDistrict}` : 'Haz clic dentro de un distrito' }}
                    </span>
                  </div>
                  <div class="text-end">
                    <span class="text-caption text-disabled d-block">GPS Coords:</span>
                    <span class="text-caption font-weight-mono">
                      {{ clientForm.lat }}, {{ clientForm.lng }}
                    </span>
                  </div>
                </div>
              </div>
            </VCol>

            <!-- COLUMNA DERECHA: FORMULARIO -->
            <VCol cols="12" md="6">
              <VRow>
                <VCol cols="12">
                  <VTextField
                    v-model="clientForm.business_name"
                    label="Nombre Comercial / Micromarket"
                    placeholder="Ej. Micromarket Los Andes"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-store-2-line"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="clientForm.name"
                    label="Nombre del Propietario"
                    placeholder="Doña Remedios"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="clientForm.surname"
                    label="Apellidos"
                    placeholder="Mamani"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="clientForm.phone"
                    label="WhatsApp / Celular"
                    placeholder="71542389"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-whatsapp-line"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VSelect
                    v-model="clientForm.city"
                    :items="['El Alto', 'La Paz']"
                    label="Ciudad"
                    variant="outlined"
                    density="comfortable"
                    @update:model-value="onCityChange"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VSelect
                    v-model="clientForm.district"
                    :items="currentDistrictOptions"
                    label="Distrito Asignado"
                    variant="outlined"
                    density="comfortable"
                    color="primary"
                    prepend-inner-icon="ri-compass-3-line"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <VTextField
                    v-model="clientForm.zone"
                    label="Zona o Barrio"
                    placeholder="Ej. Ciudad Satélite, Sopocachi"
                    variant="outlined"
                    density="comfortable"
                  />
                </VCol>

                <VCol cols="12">
                  <VTextField
                    v-model="clientForm.address"
                    label="Dirección de la Tienda / Negocio"
                    placeholder="Av. Alfredo Sanjinés #450, Plan 112"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-map-pin-line"
                  />
                </VCol>

                <VCol cols="12">
                  <VTextarea
                    v-model="clientForm.notes"
                    label="Instrucciones para el Repartidor / Horarios"
                    placeholder="Atención de 07:00 a 22:00. Punto de entrega por puerta trasera..."
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
          <VBtn color="primary" :loading="formLoading" prepend-icon="ri-save-line" @click="saveClient">
            {{ isEditing ? 'Guardar Cambios' : 'Guardar Cliente Georreferenciado' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.clients-view-wrapper {
  display: flex;
  flex-direction: column;
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
  background: rgba(var(--v-theme-primary), 0.08);
  border: 1px solid rgba(var(--v-theme-primary), 0.2);
  border-radius: 8px;
}
</style>
