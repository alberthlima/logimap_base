<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import VueApexCharts from 'vue3-apexcharts'
import { $api } from '@/utils/api'

definePage({
  meta: {
    permission: 'all',
  },
})

const router = useRouter()

// Datos reactivos
const loading = ref(true)
const stats = ref({
  total_clients: 0,
  total_orders: 0,
  total_revenue: 0,
  status_summary: {
    REGISTRADO: 0,
    EN_CAMINO: 0,
    ENTREGADO: 0,
    PAGADO: 0,
  },
  district_stats: [],
  recent_orders: [],
})

const fetchDashboardStats = async () => {
  loading.value = true
  try {
    const res = await $api('dashboard/stats')
    stats.value = res
  } catch (error) {
    console.error('Error al cargar métricas de dashboard:', error)
  } finally {
    loading.value = false
  }
}

// Configuración de Gráfico de Barras: Distribución Territorial
const districtChartOptions = computed(() => {
  const districts = stats.value.district_stats.map(d => `${d.district}`)
  return {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      fontFamily: 'inherit',
    },
    plotOptions: {
      bar: {
        borderRadius: 6,
        horizontal: false,
        columnWidth: '45%',
        distributed: true,
      },
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B', '#EC4899', '#8B5CF6', '#06B6D4', '#F97316'],
    dataLabels: { enabled: false },
    legend: { show: false },
    xaxis: {
      categories: districts,
      labels: {
        rotate: -30,
        style: { fontSize: '11px' },
      },
    },
    yaxis: {
      title: { text: 'Cantidad de Pedidos' },
    },
    tooltip: {
      y: {
        formatter: (val) => `${val} pedidos`,
      },
    },
  }
})

const districtChartSeries = computed(() => {
  return [{
    name: 'Pedidos',
    data: stats.value.district_stats.map(d => d.order_count),
  }]
})

// Configuración de Gráfico Circular: Estados de Despacho
const statusChartOptions = computed(() => {
  return {
    chart: {
      type: 'donut',
      fontFamily: 'inherit',
    },
    labels: ['Registrado ⏱', 'En Camino 🚚', 'Entregado ✓', 'Pagado 💳'],
    colors: ['#F59E0B', '#3B82F6', '#10B981', '#8B5CF6'],
    legend: {
      position: 'bottom',
      fontSize: '12px',
    },
    dataLabels: {
      enabled: true,
      formatter: (val) => `${val.toFixed(0)}%`,
    },
    plotOptions: {
      pie: {
        donut: {
          size: '68%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Total Pedidos',
              fontSize: '13px',
              formatter: () => stats.value.total_orders,
            },
          },
        },
      },
    },
  }
})

const statusChartSeries = computed(() => {
  const s = stats.value.status_summary
  return [s.REGISTRADO || 0, s.EN_CAMINO || 0, s.ENTREGADO || 0, s.PAGADO || 0]
})

const statusChipColor = (status) => {
  if (status === 'REGISTRADO') return 'warning'
  if (status === 'EN_CAMINO') return 'info'
  if (status === 'ENTREGADO') return 'success'
  if (status === 'PAGADO') return 'purple'
  return 'default'
}

onMounted(() => {
  fetchDashboardStats()
})
</script>

<template>
  <div class="dashboard-wrapper">
    <!-- BANNER DE BIENVENIDA Y ACCESO RÁPIDO A MAPA -->
    <VCard class="mb-5 welcome-banner overflow-hidden" elevation="2">
      <VRow no-gutters align="center">
        <VCol cols="12" md="8" class="pa-6">
          <div class="d-flex align-center gap-2 mb-2">
            <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
              LOGIMAP V2.0
            </VChip>
            <span class="text-caption text-disabled">Gestión Territorial & Despacho</span>
          </div>

          <h2 class="text-h4 font-weight-bold mb-2">
            Control Logístico Inteligente por Distritos
          </h2>
          <p class="text-body-1 text-disabled mb-4" style="max-width: 600px;">
            Monitorea en tiempo real tus clientes georreferenciados, pedidos y cobertura de entregas entre El Alto y La Paz con delimitación poligonal de distritos.
          </p>

          <div class="d-flex flex-wrap gap-3">
            <VBtn
              color="primary"
              prepend-icon="ri-map-pin-user-line"
              size="large"
              elevation="2"
              @click="router.push({ name: 'mapa' })"
            >
              Abrir Monitoreo en Mapa
            </VBtn>

            <VBtn
              variant="outlined"
              color="primary"
              prepend-icon="ri-truck-line"
              size="large"
              @click="router.push({ name: 'pedidos' })"
            >
              Gestionar Pedidos
            </VBtn>
          </div>
        </VCol>

        <VCol cols="12" md="4" class="d-none d-md-flex justify-center align-center pa-4">
          <img
            src="@images/pages/auth-v2-login-illustration-light.png"
            alt="Dashboard Banner"
            style="max-height: 180px; object-fit: contain;"
          />
        </VCol>
      </VRow>
    </VCard>

    <!-- TARJETAS KPI -->
    <VRow class="mb-5">
      <!-- Clientes -->
      <VCol cols="12" sm="6" lg="3">
        <VCard elevation="1" class="pa-4 h-100">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">CLIENTES REGISTRADOS</div>
              <div class="text-h4 font-weight-bold mt-1 text-primary">{{ stats.total_clients }}</div>
              <div class="text-caption text-success mt-1">
                <VIcon icon="ri-check-line" size="14" /> Puntos georreferenciados
              </div>
            </div>
            <VAvatar color="primary" variant="tonal" size="48" rounded="lg">
              <VIcon icon="ri-user-location-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <!-- Pedidos en Tránsito -->
      <VCol cols="12" sm="6" lg="3">
        <VCard elevation="1" class="pa-4 h-100">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">EN CAMINO 🚚</div>
              <div class="text-h4 font-weight-bold mt-1 text-info">{{ stats.status_summary.EN_CAMINO || 0 }}</div>
              <div class="text-caption text-info mt-1">
                Despacho activo en ruta
              </div>
            </div>
            <VAvatar color="info" variant="tonal" size="48" rounded="lg">
              <VIcon icon="ri-truck-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <!-- Entregados y Pagados -->
      <VCol cols="12" sm="6" lg="3">
        <VCard elevation="1" class="pa-4 h-100">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">COMPLETADOS</div>
              <div class="text-h4 font-weight-bold mt-1 text-success">
                {{ (stats.status_summary.ENTREGADO || 0) + (stats.status_summary.PAGADO || 0) }}
              </div>
              <div class="text-caption text-success mt-1">
                Entregados y pagados
              </div>
            </div>
            <VAvatar color="success" variant="tonal" size="48" rounded="lg">
              <VIcon icon="ri-checkbox-circle-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <!-- Volumen Facturado -->
      <VCol cols="12" sm="6" lg="3">
        <VCard elevation="1" class="pa-4 h-100">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled font-weight-medium">VOLUMEN COBRADO</div>
              <div class="text-h4 font-weight-bold mt-1 text-purple">
                Bs {{ Number(stats.total_revenue || 0).toFixed(2) }}
              </div>
              <div class="text-caption text-disabled mt-1">
                Total órdenes efectivas
              </div>
            </div>
            <VAvatar color="purple" variant="tonal" size="48" rounded="lg">
              <VIcon icon="ri-money-dollar-circle-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- GRÁFICOS ANALÍTICOS -->
    <VRow class="mb-5">
      <!-- Gráfico por Distrito -->
      <VCol cols="12" md="8">
        <VCard elevation="1" class="h-100">
          <VCardItem class="pa-4 border-b">
            <div class="d-flex align-center justify-space-between">
              <div>
                <VCardTitle class="text-h6 font-weight-bold">
                  Concentración Territorial por Distrito
                </VCardTitle>
                <VCardSubtitle>
                  Volumen de pedidos distribuidos entre distritos de El Alto y La Paz
                </VCardSubtitle>
              </div>
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                prepend-icon="ri-map-pin-2-line"
                @click="router.push({ name: 'mapa' })"
              >
                Ver en Mapa
              </VBtn>
            </div>
          </VCardItem>
          <VCardText class="pa-4">
            <VueApexCharts
              v-if="stats.district_stats.length > 0"
              type="bar"
              height="300"
              :options="districtChartOptions"
              :series="districtChartSeries"
            />
            <div v-else class="text-center py-8 text-disabled">
              Cargando métricas territoriales...
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Gráfico Donut de Estados -->
      <VCol cols="12" md="4">
        <VCard elevation="1" class="h-100">
          <VCardItem class="pa-4 border-b">
            <VCardTitle class="text-h6 font-weight-bold">
              Embudo de Entregas
            </VCardTitle>
            <VCardSubtitle>
              Distribución por etapa de despacho
            </VCardSubtitle>
          </VCardItem>
          <VCardText class="pa-4 d-flex align-center justify-center">
            <VueApexCharts
              v-if="stats.total_orders > 0"
              type="donut"
              height="300"
              :options="statusChartOptions"
              :series="statusChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ÚLTIMOS PEDIDOS REGISTRADOS -->
    <VCard elevation="1">
      <VCardItem class="pa-4 border-b">
        <div class="d-flex align-center justify-space-between">
          <div>
            <VCardTitle class="text-h6 font-weight-bold">
              Últimas Entregas y Despachos Registrados
            </VCardTitle>
            <VCardSubtitle>
              Monitoreo directo del flujo reciente de órdenes
            </VCardSubtitle>
          </div>
          <VBtn
            size="small"
            variant="text"
            color="primary"
            append-icon="ri-arrow-right-line"
            @click="router.push({ name: 'pedidos' })"
          >
            Ver todos los pedidos
          </VBtn>
        </div>
      </VCardItem>

      <VTable hover class="text-no-wrap">
        <thead>
          <tr>
            <th class="font-weight-bold">CÓDIGO</th>
            <th class="font-weight-bold">CLIENTE</th>
            <th class="font-weight-bold">UBICACIÓN</th>
            <th class="font-weight-bold">REPARTIDOR</th>
            <th class="font-weight-bold text-end">MONTO</th>
            <th class="font-weight-bold text-center">ESTADO</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in stats.recent_orders" :key="order.id">
            <td class="font-weight-bold text-primary">{{ order.code }}</td>
            <td>
              <div class="font-weight-medium text-body-2">
                {{ order.client?.business_name || (order.client?.name + ' ' + (order.client?.surname || '')) }}
              </div>
              <div class="text-caption text-disabled">{{ order.delivery_address }}</div>
            </td>
            <td>
              <VChip size="small" variant="tonal" color="primary">
                {{ order.district }}
              </VChip>
              <span class="text-caption text-disabled ms-1">({{ order.city }})</span>
            </td>
            <td>
              <span class="text-body-2">{{ order.delivery_driver || 'Sin asignar' }}</span>
            </td>
            <td class="text-end font-weight-bold">
              Bs {{ Number(order.total_amount).toFixed(2) }}
            </td>
            <td class="text-center">
              <VChip size="small" variant="elevated" :color="statusChipColor(order.status)">
                {{ order.status }}
              </VChip>
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </div>
</template>

<style scoped lang="scss">
.dashboard-wrapper {
  display: flex;
  flex-direction: column;
}

.welcome-banner {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.08) 0%, rgba(var(--v-theme-surface), 1) 100%);
  border: 1px solid rgba(var(--v-theme-primary), 0.16);
}
</style>
