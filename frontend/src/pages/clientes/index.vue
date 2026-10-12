<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { $api } from '@/utils/api'

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

// Diálogo de Registro / Edición
const isDialogOpen = ref(false)
const isEditing = ref(false)
const formLoading = ref(false)

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
  lat: -16.5100,
  lng: -68.1650,
  notes: '',
})

// Opciones de distritos por ciudad
const districtsByCity = {
  'El Alto': ['Distrito 1', 'Distrito 2', 'Distrito 3', 'Distrito 4', 'Distrito 5', 'Distrito 6', 'Distrito 8'],
  'La Paz': ['CENTRO', 'COTAHUMA', 'MAX PAREDES', 'SUR'],
}

const currentDistrictOptions = computed(() => {
  return districtsByCity[clientForm.value.city] || []
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
    lat: -16.5100,
    lng: -68.1650,
    notes: '',
  }
  isDialogOpen.value = true
}

// Abrir diálogo de edición
const openEditDialog = (item) => {
  isEditing.value = true
  clientForm.value = { ...item }
  isDialogOpen.value = true
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
const viewOnMap = (client) => {
  router.push({ name: 'mapa' })
}

onMounted(() => {
  fetchClients()
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
            prepend-icon="ri-user-add-line"
            @click="openCreateDialog"
          >
            Nuevo Cliente
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
              :items="['TODOS', ...districtsByCity['El Alto'], ...districtsByCity['La Paz']]"
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

    <!-- DIÁLOGO DE REGISTRO / EDICIÓN -->
    <VDialog v-model="isDialogOpen" max-width="650px">
      <VCard>
        <VCardTitle class="pa-4 border-b">
          {{ isEditing ? 'Editar Cliente Georreferenciado' : 'Registrar Nuevo Cliente' }}
        </VCardTitle>

        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="clientForm.business_name"
                label="Nombre Comercial / Micromarket"
                placeholder="Ej. Micromarket El Progreso"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="clientForm.phone"
                label="Teléfono / Celular (WhatsApp)"
                placeholder="Ej. 71512345"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="clientForm.name"
                label="Nombre del Propietario"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="clientForm.surname"
                label="Apellidos"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VSelect
                v-model="clientForm.city"
                :items="['El Alto', 'La Paz']"
                label="Ciudad"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VSelect
                v-model="clientForm.district"
                :items="currentDistrictOptions"
                label="Distrito"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="clientForm.zone"
                label="Zona o Barrio"
                placeholder="Ej. Santiago II, Sopocachi"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="clientForm.address"
                label="Dirección Completa"
                placeholder="Av. 6 de Marzo #450"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model.number="clientForm.lat"
                label="Latitud GPS"
                type="number"
                step="0.0001"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model.number="clientForm.lng"
                label="Longitud GPS"
                type="number"
                step="0.0001"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="clientForm.notes"
                label="Instrucciones de Entrega / Notas"
                rows="2"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-4 border-t d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isDialogOpen = false">
            Cancelar
          </VBtn>
          <VBtn color="primary" :loading="formLoading" @click="saveClient">
            {{ isEditing ? 'Guardar Cambios' : 'Registrar Cliente' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.clients-view-wrapper {
  display: flex;
  flex-direction: column;
}
</style>
