export default [
  {
    title: 'Escritorio',
    to: { name: 'dashboard' },
    permission: 'all',
    icon: { icon: 'ri-pie-chart-box-line' },
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
