# AutoControl - Sistema de Gestión para Taller Automotriz

Sistema completo de gestión para talleres de lubricantes y mantenimiento automotriz. Permite gestionar clientes, vehículos, servicios, mantenimientos y facturación con recordatorios automáticos.

## 🚀 Características

- ✅ **Gestión de Clientes**: Registro completo de información de clientes
- ✅ **Gestión de Vehículos**: Control de placas, marcas, modelos y kilometraje
- ✅ **Catálogo de Servicios**: Servicios personalizables con precios y frecuencias
- ✅ **Historial de Mantenimientos**: Seguimiento completo de servicios realizados
- ✅ **Facturación**: Generación de facturas con detalles de servicios
- ✅ **Sistema de Usuarios**: Autenticación segura con contraseñas encriptadas
- ✅ **Recordatorios WhatsApp**: Alertas automáticas de mantenimientos próximos
- ✅ **Base de Datos MySQL**: Persistencia completa de datos
- ✅ **API REST**: Endpoints completos para todas las operaciones
- ✅ **Responsive Design**: Interfaz adaptable a móviles y tablets

## 📋 Requisitos

- PHP 8.0 o superior
- MySQL 5.7 o superior
- Apache/Nginx
- Extensiones PHP: `pdo`, `pdo_mysql`, `curl`

### Para Docker:
- Docker Engine
- Docker Compose (opcional)

## 🔧 Instalación

### Opción 1: Con XAMPP (Desarrollo Local)

1. **Clonar el repositorio:**
```bash
git clone https://github.com/DanielPuin2025/autocontrol.git
cd autocontrol
```

2. **Configurar la base de datos:**
```bash
# Importar el esquema
mysql -u root -p < Base_de_datos/autocontrol_db.sql

# (Opcional) Importar datos de ejemplo
mysql -u root -p < Base_de_datos/datos_ejemplo.sql
```

3. **Configurar variables de entorno:**

Editar `Backend/db.php` o configurar variables de entorno:
- `AUTOCTRL_DB_HOST` (default: localhost)
- `AUTOCTRL_DB_PORT` (default: 3306)
- `AUTOCTRL_DB_NAME` (default: autocontrol_db)
- `AUTOCTRL_DB_USER` (default: root)
- `AUTOCTRL_DB_PASS` (default: vacío)

4. **Copiar archivos a htdocs:**
```bash
cp -r . C:/xampp/htdocs/autocontrol/
```

5. **Acceder a la aplicación:**
```
http://localhost/autocontrol/Frontend/
```

### Opción 2: Con Docker

1. **Construir la imagen:**
```bash
docker build -t autocontrol .
```

2. **Ejecutar el contenedor:**
```bash
docker run -d -p 8080:80 \
  -e AUTOCTRL_DB_HOST=tu_host_mysql \
  -e AUTOCTRL_DB_PORT=3306 \
  -e AUTOCTRL_DB_NAME=autocontrol_db \
  -e AUTOCTRL_DB_USER=tu_usuario \
  -e AUTOCTRL_DB_PASS=tu_contraseña \
  --name autocontrol \
  autocontrol
```

3. **Acceder:**
```
http://localhost:8080
```

### Opción 3: Despliegue en EasyPanel

Ver archivo [DEPLOY_EASYPANEL.md](DEPLOY_EASYPANEL.md) para instrucciones detalladas.

## 📚 Documentación

### APIs REST

Todas las APIs REST están documentadas en [Backend/README_API.md](Backend/README_API.md)

**Endpoints disponibles:**
- `/Backend/api_clientes.php` - Gestión de clientes
- `/Backend/api_vehiculos.php` - Gestión de vehículos
- `/Backend/api_servicios.php` - Catálogo de servicios
- `/Backend/api_usuarios.php` - Usuarios y autenticación
- `/Backend/api_mantenimientos.php` - Historial de mantenimientos
- `/Backend/api_facturas.php` - Facturas con detalles

### Base de Datos

**Tablas principales:**
- `clientes` - Información de clientes
- `vehiculos` - Vehículos asociados a clientes
- `servicios` - Catálogo de servicios
- `usuarios` - Usuarios del sistema
- `mantenimientos` - Historial de mantenimientos
- `facturas` / `detalle_factura` - Facturación
- `alertas` - Notificaciones y recordatorios
- `administradores` - Administradores del sistema
- `configuracion` - Configuración del taller

Ver [Base_de_datos/autocontrol_db.sql](Base_de_datos/autocontrol_db.sql) para el esquema completo.

## 🔐 Seguridad

**⚠️ IMPORTANTE:** La versión actual NO incluye autenticación en las APIs REST.

**Para usar en producción:**
1. Implementar autenticación JWT o sesiones
2. Validar permisos de usuario
3. Configurar CORS específico
4. Usar HTTPS obligatorio
5. Implementar rate limiting
6. Validar y sanitizar todas las entradas
7. No exponer credenciales en el código

## 👥 Usuarios de Ejemplo

Si importaste `datos_ejemplo.sql`:

**Usuarios cliente:**
- Usuario: `juan.perez` / Contraseña: `password123`
- Usuario: `maria.rodriguez` / Contraseña: `password123`

**Administrador (tabla administradores):**
- Usuario: `admin` / Contraseña: `admin123` (⚠️ Cambiar en producción)

## 📱 Recordatorios de WhatsApp

Para habilitar recordatorios automáticos por WhatsApp:

1. Configurar las variables de entorno `AUTOCTRL_WA_*`
2. Ver [Backend/RECORDATORIOS_WHATSAPP.md](Backend/RECORDATORIOS_WHATSAPP.md)
3. Configurar cron job para ejecutar `Backend/procesar_recordatorios.php`

## 🛠️ Desarrollo

### Estructura del Proyecto

```
autocontrol/
├── Frontend/           # Aplicación web frontend
│   ├── app.js         # Lógica principal de la aplicación
│   ├── index.html     # Página principal
│   └── sw.js          # Service Worker (PWA)
├── Backend/           # APIs y lógica del servidor
│   ├── api_clientes.php
│   ├── api_vehiculos.php
│   ├── api_servicios.php
│   ├── api_usuarios.php
│   ├── api_mantenimientos.php
│   ├── api_facturas.php
│   ├── api.php        # API original (compatibilidad)
│   ├── db.php         # Conexión a base de datos
│   └── README_API.md  # Documentación de APIs
├── Base_de_datos/     # Scripts SQL
│   ├── autocontrol_db.sql    # Esquema completo
│   └── datos_ejemplo.sql     # Datos de prueba
├── docker/            # Configuración Docker
├── Dockerfile
└── README.md          # Este archivo
```

### Agregar Nuevas Funcionalidades

1. **Agregar nueva tabla:** Modificar `Base_de_datos/autocontrol_db.sql`
2. **Crear nueva API:** Seguir el patrón de las APIs existentes
3. **Actualizar Frontend:** Modificar `Frontend/app.js`

## 🧪 Pruebas

### Probar APIs con cURL

```bash
# Listar clientes
curl http://localhost/autocontrol/Backend/api_clientes.php

# Crear cliente
curl -X POST http://localhost/autocontrol/Backend/api_clientes.php \
  -H "Content-Type: application/json" \
  -d '{"nombre":"Pedro","apellido":"López","telefono":"3001234567"}'

# Login
curl -X POST "http://localhost/autocontrol/Backend/api_usuarios.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"usuario":"juan.perez","contrasena":"password123"}'
```

### Verificar Conexión a Base de Datos

```bash
curl http://localhost/autocontrol/Backend/api.php?clave=autocontrol_data_v1
```

Debería devolver: `{"valor":null}` o JSON con datos guardados.

## 🤝 Contribuir

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abrir un Pull Request

## 📝 Licencia

Este proyecto está desarrollado para uso del Taller Lubricantes Molina.

## 📞 Contacto

- **Repositorio:** https://github.com/DanielPuin2025/autocontrol
- **Dominio:** autocontrol.programamega.com

## 🔄 Actualizaciones Recientes

### v2.0 - Sistema de Base de Datos Completo
- ✅ APIs REST completas para todas las entidades
- ✅ Persistencia total en MySQL (no más JSON local)
- ✅ Sistema de usuarios con autenticación
- ✅ Gestión de facturas con detalles
- ✅ Historial completo de mantenimientos
- ✅ Documentación completa de APIs
- ✅ Datos de ejemplo para pruebas

### Migración desde v1.0
La versión anterior usaba `localStorage` y la tabla `almacenamiento` con JSON. La nueva versión usa tablas estructuradas para mejor rendimiento y funcionalidad. La API original (`api.php`) se mantiene por compatibilidad.

## ⚠️ Notas Importantes

1. **Backup:** Siempre hacer copias de seguridad de la base de datos antes de actualizar
2. **Contraseñas:** Cambiar todas las contraseñas de ejemplo en producción
3. **HTTPS:** Usar siempre HTTPS en producción
4. **Permisos MySQL:** Crear usuario dedicado, no usar root
5. **Logs:** Monitorear logs de PHP y MySQL regularmente

## 🎯 Roadmap

- [ ] Panel de administración completo
- [ ] Dashboard con estadísticas
- [ ] Reportes en PDF
- [ ] Integración con pasarelas de pago
- [ ] Aplicación móvil nativa
- [ ] Sistema de inventario de repuestos
- [ ] Multi-tenancy (múltiples talleres)
- [ ] API pública con autenticación OAuth2

---

## 👨‍💻 Creadores del Proyecto

**AutoControl - Sistema de Gestión para Taller Automotriz**

### Equipo de Desarrollo

**Dirección y Desarrollo:**
- **Jhon Freddy Molina** - Director del Proyecto
- **Taller Lubricantes Molina** - Propietario y Visionario del Sistema

**Desarrollo Técnico:**
- **Daniel Humberto Puin Granados** - Desarrollador Full Stack
  - Email: danielpuin2025@gmail.com
  - GitHub: [@DanielPuin2025](https://github.com/DanielPuin2025)

### Tecnologías Utilizadas
- Frontend: HTML5, CSS3, JavaScript (Vanilla)
- Backend: PHP 8.0+
- Base de Datos: MySQL 8.0
- Arquitectura: REST API
- Deployment: Docker, EasyPanel

## 📄 Derechos Reservados

© 2026 Taller Lubricantes Molina & Daniel Humberto Puin Granados.  
Todos los derechos reservados.

Este software fue desarrollado específicamente para **Taller Lubricantes Molina**.

### Términos de Uso
- ✅ Uso autorizado para Taller Lubricantes Molina
- ✅ Modificaciones permitidas para el cliente autorizado
- ❌ Redistribución no autorizada sin permiso expreso
- ❌ Uso comercial por terceros no autorizado

Para consultas sobre licenciamiento o uso del software, contactar a:
- **Taller Lubricantes Molina:** Jhon Freddy Molina
- **Desarrollo Técnico:** danielpuin2025@gmail.com

---

Desarrollado con ❤️ por Daniel Puin para Taller Lubricantes Molina
