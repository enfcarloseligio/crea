# CREA - Constructor Relacional de Entornos Analíticos

**CREA** es un potente *Custom Database Builder* para WordPress. Diseñado inicialmente para resolver necesidades complejas de gestión en salud digital y enfermería informática, CREA ha evolucionado para convertirse en un motor dinámico que permite a cualquier usuario diseñar, desplegar y gestionar bases de datos relacionales personalizadas directamente desde el panel de administración.

### 🚀 ¿Qué hace esta herramienta?
* **Constructor Dinámico:** Interfaz visual para definir tablas personalizadas y tipos de datos (texto, fechas, coordenadas geográficas, archivos).
* **Generación de UI (Shortcodes):** Crea automáticamente formularios de captura de datos y tablas de visualización (DataTables) para el frontend.
* **Estadística y GIS:** Preparado para análisis inferencial y exportación de recursos estáticos (CSV/GeoJSON) compatibles con Sistemas de Información Geográfica.
* **Arquitectura Híbrida:** Combina el poder transaccional de SQL (MySQL/MariaDB) con la generación programada de archivos estáticos para un alto rendimiento en integraciones API.

---

### 📂 Estructura Interna del Proyecto

El plugin sigue un estándar de desarrollo modular (Boilerplate Pattern) separando estrictamente la lógica de administración, el frontend y el motor de base de datos.

```text
crea/
├── crea.php                      # Punto de entrada y Bootstrap del plugin
├── uninstall.php                 # Limpieza al eliminar el plugin (DROP TABLES generadas)
├── admin/                        # GESTIÓN: Lógica y vistas del WP-Admin
│   ├── class-crea-admin.php      # Clase maestra del área administrativa
│   ├── assets/                   
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   ├── partials/                 # Vistas HTML fragmentadas
│   │   ├── crea-dashboard.php    # Panel principal (Estadísticas globales)
│   │   ├── crea-builder.php      # Interfaz visual para crear tablas y campos
│   │   ├── crea-settings.php     # Contenedor de configuración general
│   │   └── settings-tabs/        
│   │       ├── tab-general.php
│   │       ├── tab-apariencia.php
│   │       └── tab-permisos.php  # Control de roles y accesos
│   └── tables/                   
│       └── class-crea-list.php   # WP_List_Table para mostrar las bases creadas
├── api/                          
│   └── class-crea-api.php        # Generación de CSV/JSON y endpoints REST
├── includes/                     # CORE: Lógica compartida e infraestructura
│   ├── class-crea.php            # Orquestador central (Carga de hooks)
│   ├── class-crea-loader.php     # Gestor de acciones y filtros
│   ├── class-crea-i18n.php       # Soporte de idiomas
│   ├── class-crea-db.php         # Manejo de la conexión y dbDelta principal
│   ├── class-crea-schema.php     # Motor SQL dinámico (CREATE TABLE al vuelo)
│   └── shortcodes/               
│       ├── class-crea-shortcodes.php
│       └── views/                # Vistas dinámicas para el frontend
│           ├── view-form.php     # Renderiza el formulario de captura
│           └── view-table.php    # Renderiza la visualización de datos
├── public/                       # FRONTEND: Lo que ve el usuario final
│   ├── class-crea-public.php     # Procesamiento de envíos de formularios
│   ├── assets/                   
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── partials/                 
│       └── crea-public-display.php
└── uploads/                      # Almacenamiento local para archivos subidos