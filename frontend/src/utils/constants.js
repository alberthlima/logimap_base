export const COOKIE_MAX_AGE_1_YEAR = 365 * 24 * 60 * 60;

export const PERMISOS = [
    {
        'name': 'Dashboard',
        'permisos': [
            { name: 'Escritorio', permiso: 'escritorio' }
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