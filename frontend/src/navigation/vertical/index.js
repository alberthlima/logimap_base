export default [
  {
    title: 'Escritorio',
    to: { name: 'dashboard' },
    permission: 'all',
    icon: { icon: 'ri-pie-chart-box-line' },
  },

  {
    heading: 'Operaciones y Logística',
    permissions: ['ver_mapa', 'listar_cliente', 'listar_pedido'],
  },
  {
    title: 'Monitoreo en Mapa',
    to: { name: 'mapa' },
    permission: 'ver_mapa',
    icon: { icon: 'ri-map-pin-user-line' },
  },
  {
    title: 'Clientes',
    to: { name: 'clientes' },
    permission: 'listar_cliente',
    icon: { icon: 'ri-user-location-line' },
  },
  {
    title: 'Pedidos y Entregas',
    to: { name: 'pedidos' },
    permission: 'listar_pedido',
    icon: { icon: 'ri-truck-line' },
  },

  {
    heading: 'Accesos y Seguridad',
    permissions: ['listar_rol', 'listar_usuario'],
  },
  {
    title: 'Roles y Permisos',
    to: { name: 'roles-permisos' },
    permission: 'listar_rol',
    icon: { icon: 'ri-shield-keyhole-line' },
  },
  {
    title: 'Usuarios',
    to: { name: 'usuarios' },
    permission: 'listar_usuario',
    icon: { icon: 'ri-user-settings-line' },
  },
]
