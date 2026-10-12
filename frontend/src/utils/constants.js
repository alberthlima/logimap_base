export const COOKIE_MAX_AGE_1_YEAR = 365 * 24 * 60 * 60;

export const PERMISOS = [
    {
        'name': 'Dashboard',
        'permisos': [
            { name: 'Escritorio', permiso: 'escritorio' }
        ]
    },
    {
        'name': 'Monitoreo Territorial',
        'permisos': [
            { name: 'Ver Mapa en Vivo', permiso: 'ver_mapa' }
        ]
    },
    {
        'name': 'Clientes',
        'permisos': [
            { name: 'Listado', permiso: 'listar_cliente' },
            { name: 'Registrar', permiso: 'registrar_cliente' },
            { name: 'Editar', permiso: 'editar_cliente' },
            { name: 'Eliminar', permiso: 'eliminar_cliente' }
        ]
    },
    {
        'name': 'Pedidos y Entregas',
        'permisos': [
            { name: 'Listado', permiso: 'listar_pedido' },
            { name: 'Registrar', permiso: 'registrar_pedido' },
            { name: 'Editar', permiso: 'editar_pedido' },
            { name: 'Eliminar', permiso: 'eliminar_pedido' }
        ]
    },
    {
        'name': 'Roles',
        'permisos': [
            { name: 'Listado', permiso: 'listar_rol' },
            { name: 'Registrar', permiso: 'registrar_rol' },
            { name: 'Editar', permiso: 'editar_rol' },
            { name: 'Eliminar', permiso: 'eliminar_rol' }
        ]
    },
    {
        'name': 'Usuarios',
        'permisos': [
            { name: 'Listado', permiso: 'listar_usuario' },
            { name: 'Registrar', permiso: 'registrar_usuario' },
            { name: 'Editar', permiso: 'editar_usuario' },
            { name: 'Eliminar', permiso: 'eliminar_usuario' }
        ]
    }
];