# API REST de AutoControl

Este documento describe todas las APIs REST disponibles para la gestión persistente de datos en AutoControl.

## Configuración

Todas las APIs utilizan la conexión a MySQL configurada en `db.php` mediante las variables de entorno:
- `AUTOCTRL_DB_HOST`
- `AUTOCTRL_DB_PORT`
- `AUTOCTRL_DB_NAME`
- `AUTOCTRL_DB_USER`
- `AUTOCTRL_DB_PASS`

## APIs Disponibles

### 1. API de Clientes (`api_clientes.php`)

Gestión completa de clientes del taller.

#### Listar todos los clientes
```http
GET /Backend/api_clientes.php
```

**Respuesta:**
```json
[
  {
    "id_cliente": 1,
    "nombre": "Juan",
    "apellido": "Pérez",
    "documento": "123456789",
    "telefono": "3001234567",
    "whatsapp": "3001234567",
    "correo": "juan@email.com",
    "creado_en": "2024-01-15 10:30:00",
    "actualizado_en": "2024-01-15 10:30:00"
  }
]
```

#### Obtener un cliente específico
```http
GET /Backend/api_clientes.php?id=1
```

#### Crear nuevo cliente
```http
POST /Backend/api_clientes.php
Content-Type: application/json

{
  "nombre": "Juan",
  "apellido": "Pérez",
  "documento": "123456789",
  "telefono": "3001234567",
  "whatsapp": "3001234567",
  "correo": "juan@email.com"
}
```

#### Actualizar cliente
```http
PUT /Backend/api_clientes.php?id=1
Content-Type: application/json

{
  "nombre": "Juan Carlos",
  "apellido": "Pérez",
  "documento": "123456789",
  "telefono": "3009876543",
  "whatsapp": "3009876543",
  "correo": "juanc@email.com"
}
```

#### Eliminar cliente
```http
DELETE /Backend/api_clientes.php?id=1
```

---

### 2. API de Vehículos (`api_vehiculos.php`)

Gestión de vehículos (placas) asociados a clientes.

#### Listar todos los vehículos
```http
GET /Backend/api_vehiculos.php
```

#### Listar vehículos de un cliente
```http
GET /Backend/api_vehiculos.php?cliente=1
```

#### Obtener un vehículo específico
```http
GET /Backend/api_vehiculos.php?id=1
```

**Respuesta:**
```json
{
  "id_vehiculo": 1,
  "id_cliente": 1,
  "placa": "ABC123",
  "marca": "Toyota",
  "modelo": "Corolla",
  "anio": 2020,
  "tipo_vehiculo": "carro",
  "kilometraje_actual": 45000,
  "nombre": "Juan",
  "apellido": "Pérez"
}
```

#### Crear nuevo vehículo
```http
POST /Backend/api_vehiculos.php
Content-Type: application/json

{
  "id_cliente": 1,
  "placa": "ABC123",
  "marca": "Toyota",
  "modelo": "Corolla",
  "anio": 2020,
  "tipo_vehiculo": "carro",
  "kilometraje_actual": 45000
}
```

**Tipos de vehículo:** `carro`, `moto`, `camioneta`, `otro`

#### Actualizar vehículo
```http
PUT /Backend/api_vehiculos.php?id=1
Content-Type: application/json

{
  "id_cliente": 1,
  "placa": "ABC123",
  "marca": "Toyota",
  "modelo": "Corolla",
  "anio": 2020,
  "tipo_vehiculo": "carro",
  "kilometraje_actual": 50000
}
```

#### Eliminar vehículo
```http
DELETE /Backend/api_vehiculos.php?id=1
```

---

### 3. API de Servicios (`api_servicios.php`)

Gestión del catálogo de servicios del taller.

#### Listar todos los servicios
```http
GET /Backend/api_servicios.php
```

**Respuesta:**
```json
[
  {
    "id_servicio": 1,
    "nombre_servicio": "Cambio de aceite",
    "descripcion": "Cambio de aceite de motor y filtro",
    "precio": 45000,
    "duracion_km": 5000,
    "duracion_dias": 180,
    "estado": "activo",
    "creado_en": "2024-01-01 08:00:00",
    "actualizado_en": "2024-01-01 08:00:00"
  }
]
```

#### Obtener un servicio específico
```http
GET /Backend/api_servicios.php?id=1
```

#### Crear nuevo servicio
```http
POST /Backend/api_servicios.php
Content-Type: application/json

{
  "nombre_servicio": "Cambio de aceite",
  "descripcion": "Cambio de aceite de motor y filtro",
  "precio": 45000,
  "duracion_km": 5000,
  "duracion_dias": 180,
  "estado": "activo"
}
```

#### Actualizar servicio
```http
PUT /Backend/api_servicios.php?id=1
Content-Type: application/json

{
  "nombre_servicio": "Cambio de aceite premium",
  "descripcion": "Cambio de aceite sintético y filtro de alta calidad",
  "precio": 65000,
  "duracion_km": 7000,
  "duracion_dias": 180,
  "estado": "activo"
}
```

#### Eliminar servicio
```http
DELETE /Backend/api_servicios.php?id=1
```

---

### 4. API de Usuarios (`api_usuarios.php`)

Gestión de usuarios para acceso al sistema.

#### Listar todos los usuarios
```http
GET /Backend/api_usuarios.php
```

#### Obtener un usuario específico
```http
GET /Backend/api_usuarios.php?id=1
```

#### Autenticar usuario (Login)
```http
POST /Backend/api_usuarios.php?action=login
Content-Type: application/json

{
  "usuario": "juan.perez",
  "contrasena": "mipassword123"
}
```

**Respuesta exitosa:**
```json
{
  "ok": true,
  "usuario": {
    "id_usuario": 1,
    "id_cliente": 1,
    "usuario": "juan.perez",
    "estado": "activo",
    "creado_en": "2024-01-15 10:00:00",
    "nombre": "Juan",
    "apellido": "Pérez"
  }
}
```

#### Crear nuevo usuario
```http
POST /Backend/api_usuarios.php
Content-Type: application/json

{
  "id_cliente": 1,
  "usuario": "juan.perez",
  "contrasena": "mipassword123",
  "estado": "activo"
}
```

**Nota:** Las contraseñas se almacenan con hash seguro usando `password_hash()` de PHP.

#### Actualizar usuario
```http
PUT /Backend/api_usuarios.php?id=1
Content-Type: application/json

{
  "id_cliente": 1,
  "usuario": "juan.perez",
  "contrasena": "nuevopassword456",
  "estado": "activo"
}
```

**Nota:** El campo `contrasena` es opcional en la actualización. Si se omite, no se cambia la contraseña.

#### Eliminar usuario
```http
DELETE /Backend/api_usuarios.php?id=1
```

---

### 5. API de Mantenimientos (`api_mantenimientos.php`)

Gestión de mantenimientos realizados y programados.

#### Listar todos los mantenimientos
```http
GET /Backend/api_mantenimientos.php
```

#### Listar mantenimientos de un vehículo
```http
GET /Backend/api_mantenimientos.php?vehiculo=1
```

#### Listar mantenimientos de un cliente
```http
GET /Backend/api_mantenimientos.php?cliente=1
```

#### Obtener un mantenimiento específico
```http
GET /Backend/api_mantenimientos.php?id=1
```

**Respuesta:**
```json
{
  "id_mantenimiento": 1,
  "id_cliente": 1,
  "id_vehiculo": 1,
  "id_servicio": 1,
  "fecha_servicio": "2024-01-15",
  "proximo_mantenimiento": "2024-07-15",
  "kilometraje_actual": 45000,
  "kilometraje_proximo": 50000,
  "estado": "realizado",
  "nombre": "Juan",
  "apellido": "Pérez",
  "telefono": "3001234567",
  "whatsapp": "3001234567",
  "placa": "ABC123",
  "marca": "Toyota",
  "modelo": "Corolla",
  "nombre_servicio": "Cambio de aceite",
  "precio": 45000
}
```

#### Crear nuevo mantenimiento
```http
POST /Backend/api_mantenimientos.php
Content-Type: application/json

{
  "id_cliente": 1,
  "id_vehiculo": 1,
  "id_servicio": 1,
  "fecha_servicio": "2024-01-15",
  "proximo_mantenimiento": "2024-07-15",
  "kilometraje_actual": 45000,
  "kilometraje_proximo": 50000,
  "estado": "realizado"
}
```

**Estados:** `pendiente`, `realizado`, `vencido`

#### Actualizar mantenimiento
```http
PUT /Backend/api_mantenimientos.php?id=1
Content-Type: application/json

{
  "id_cliente": 1,
  "id_vehiculo": 1,
  "id_servicio": 1,
  "fecha_servicio": "2024-01-15",
  "proximo_mantenimiento": "2024-07-15",
  "kilometraje_actual": 45000,
  "kilometraje_proximo": 50000,
  "estado": "realizado"
}
```

#### Eliminar mantenimiento
```http
DELETE /Backend/api_mantenimientos.php?id=1
```

---

### 6. API de Facturas (`api_facturas.php`)

Gestión de facturas con sus detalles de servicios.

#### Listar todas las facturas
```http
GET /Backend/api_facturas.php
```

#### Listar facturas de un cliente
```http
GET /Backend/api_facturas.php?cliente=1
```

#### Obtener una factura específica con detalles
```http
GET /Backend/api_facturas.php?id=1
```

**Respuesta:**
```json
{
  "id_factura": 1,
  "id_cliente": 1,
  "id_vehiculo": 1,
  "fecha": "2024-01-15 14:30:00",
  "kilometraje_actual": 45000,
  "kilometraje_proximo": 50000,
  "subtotal": 45000,
  "total": 45000,
  "estado": "pagada",
  "nombre": "Juan",
  "apellido": "Pérez",
  "documento": "123456789",
  "telefono": "3001234567",
  "placa": "ABC123",
  "marca": "Toyota",
  "modelo": "Corolla",
  "detalles": [
    {
      "id_detalle": 1,
      "id_factura": 1,
      "id_servicio": 1,
      "cantidad": 1,
      "precio": 45000,
      "subtotal": 45000,
      "nombre_servicio": "Cambio de aceite"
    }
  ]
}
```

#### Crear nueva factura
```http
POST /Backend/api_facturas.php
Content-Type: application/json

{
  "id_cliente": 1,
  "id_vehiculo": 1,
  "fecha": "2024-01-15 14:30:00",
  "kilometraje_actual": 45000,
  "kilometraje_proximo": 50000,
  "estado": "pendiente",
  "detalles": [
    {
      "id_servicio": 1,
      "cantidad": 1,
      "precio": 45000
    },
    {
      "id_servicio": 4,
      "cantidad": 1,
      "precio": 40000
    }
  ]
}
```

**Nota:** El `subtotal` y `total` se calculan automáticamente a partir de los detalles.

**Estados:** `pendiente`, `pagada`, `anulada`

#### Actualizar factura
```http
PUT /Backend/api_facturas.php?id=1
Content-Type: application/json

{
  "id_cliente": 1,
  "id_vehiculo": 1,
  "fecha": "2024-01-15 14:30:00",
  "kilometraje_actual": 45000,
  "kilometraje_proximo": 50000,
  "subtotal": 45000,
  "total": 45000,
  "estado": "pagada"
}
```

#### Eliminar factura
```http
DELETE /Backend/api_facturas.php?id=1
```

**Nota:** Al eliminar una factura, también se eliminan sus detalles automáticamente por CASCADE.

---

## Códigos de Respuesta HTTP

- `200 OK` - Solicitud exitosa
- `201 Created` - Recurso creado exitosamente
- `400 Bad Request` - Faltan parámetros requeridos o datos inválidos
- `401 Unauthorized` - Credenciales inválidas (solo en login)
- `404 Not Found` - Recurso no encontrado
- `405 Method Not Allowed` - Método HTTP no permitido
- `500 Internal Server Error` - Error en el servidor o base de datos

---

## Manejo de Errores

Todas las APIs devuelven errores en formato JSON:

```json
{
  "error": "Descripción del error"
}
```

---

## CORS

Todas las APIs incluyen headers CORS para permitir peticiones desde cualquier origen:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type`

---

## Seguridad

**IMPORTANTE:** En la versión actual, las APIs no tienen autenticación. Para producción:

1. Implementar autenticación con JWT o sesiones
2. Validar permisos de usuario antes de cada operación
3. Configurar CORS para dominios específicos
4. Usar HTTPS en producción
5. Implementar rate limiting
6. Validar y sanitizar todas las entradas

---

## Ejemplos de Uso con JavaScript

### Listar clientes
```javascript
fetch('/Backend/api_clientes.php')
  .then(response => response.json())
  .then(clientes => console.log(clientes))
  .catch(error => console.error('Error:', error));
```

### Crear un cliente
```javascript
fetch('/Backend/api_clientes.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    nombre: 'María',
    apellido: 'García',
    documento: '987654321',
    telefono: '3109876543',
    whatsapp: '3109876543',
    correo: 'maria@email.com'
  })
})
  .then(response => response.json())
  .then(data => console.log('Cliente creado:', data))
  .catch(error => console.error('Error:', error));
```

### Login de usuario
```javascript
fetch('/Backend/api_usuarios.php?action=login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    usuario: 'juan.perez',
    contrasena: 'mipassword123'
  })
})
  .then(response => response.json())
  .then(data => {
    if (data.ok) {
      console.log('Login exitoso:', data.usuario);
      // Guardar sesión
      localStorage.setItem('usuario', JSON.stringify(data.usuario));
    } else {
      console.error('Login fallido:', data.error);
    }
  })
  .catch(error => console.error('Error:', error));
```

---

## Base de Datos

Todas estas APIs utilizan las tablas definidas en `Base_de_datos/autocontrol_db.sql`:

- `clientes` - Información de clientes
- `vehiculos` - Vehículos asociados a clientes
- `servicios` - Catálogo de servicios del taller
- `usuarios` - Usuarios del sistema
- `mantenimientos` - Historial de mantenimientos
- `facturas` - Facturas generadas
- `detalle_factura` - Detalles de servicios en facturas
- `administradores` - Administradores del sistema
- `alertas` - Alertas y notificaciones
- `configuracion` - Configuración del taller

---

## Migración desde JSON

Si estás migrando desde el sistema anterior que usaba `almacenamiento` (JSON), puedes:

1. Mantener ambos sistemas temporalmente
2. Usar las nuevas APIs para nuevos datos
3. Migrar datos históricos con un script de conversión
4. La API original `api.php` sigue funcionando para compatibilidad

---

## Soporte

Para reportar problemas o solicitar nuevas funcionalidades, contacta al administrador del sistema.
