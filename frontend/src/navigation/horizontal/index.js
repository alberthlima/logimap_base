export default [
  {
    title: 'Escritorio',
    to: { name: 'dashboard' },
    icon: { icon: 'ri-pie-chart-box-line' },
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
