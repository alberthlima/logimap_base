export default [
  {
    title: 'Escritorio',
    to: { name: 'dashboard' },
    icon: { icon: 'ri-pie-chart-box-line' },
  },
  {
    title: 'Monitoreo en Mapa',
    to: { name: 'mapa' },
    icon: { icon: 'ri-map-pin-user-line' },
  },
  {
    title: 'Operaciones',
    icon: { icon: 'ri-truck-line' },
    children: [
      {
        title: 'Clientes',
        to: { name: 'clientes' },
        icon: { icon: 'ri-user-location-line' },
      },
      {
        title: 'Pedidos y Entregas',
        to: { name: 'pedidos' },
        icon: { icon: 'ri-shopping-bag-3-line' },
      },
    ],
  },
  {
    title: 'Accesos',
    icon: { icon: 'ri-shield-user-line' },
    children: [
      {
        title: 'Roles y Permisos',
        to: { name: 'roles-permisos' },
        icon: { icon: 'ri-shield-keyhole-line' },
      },
      {
        title: 'Usuarios',
        to: { name: 'usuarios' },
        icon: { icon: 'ri-user-settings-line' },
      },
    ],
  },
]
