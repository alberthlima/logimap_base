# LogiMap Base 🗺️

Base limpia de arquitectura para el sistema **LogiMap** (Gestión Territorial, Georreferenciación y Distribución por Distritos Municipales).

## 🚀 Características Principales

- **Autenticación JWT:** Sistema robusto de inicio de sesión con tokens JWT.
- **Control de Acceso (RBAC):** Gestión granular de Roles y Permisos con Spatie Laravel-permission.
- **Gestión de Usuarios:** Módulo de usuarios con asignación dinámica de roles y avatares.
- **Escritorio / Dashboard:** Panel inicial limpio y preparado para widgets de métricas.
- **Capas Geoespaciales:** Soporte para archivos GeoJSON con distritos municipales de ciudades de Bolivia (El Alto, La Paz, Cobija).

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 12+** / PHP 8.2+
- **MySQL**
- **JWT Auth** (`php-open-source-saver/jwt-auth`)
- **Spatie Laravel-permission**

### Frontend
- **Vue.js 3** (Composition API)
- **Vite**
- **Vuetify 3** (Material Design)
- **Pinia** (State Management)
- **Remix Icons** / Iconify

---

## ⚙️ Configuración del Proyecto (Local con XAMPP)

### 1. Backend (`api-panel`)
```bash
cd api-panel
composer install
cp .env.example .env
# Configurar base de datos en .env (ej: db_distribucion_demo)
php artisan key:generate
php artisan jwt:secret --force
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve --port=8000
```

### 2. Frontend (`frontend`)
```bash
cd frontend
pnpm install
# Crear .env con: VITE_API_BASE_URL=http://localhost:8000/api
pnpm dev --port 5173
```

---

## 🔑 Credenciales por Defecto

- **Email:** `mlima@gmail.com`
- **Contraseña:** `12345678`
- **Rol:** Super-Admin

## 🌐 URLs de Acceso

- **API Backend:** [http://localhost:8000](http://localhost:8000)
- **Frontend Web:** [http://localhost:5173](http://localhost:5173)

---
Desarrollado por **Alberth Lima**
