-- =====================================================================
--  AutoControl - Taller Lubricantes Molina
--  Script de creación de base de datos MySQL
--  Motor: InnoDB | Charset: utf8mb4 (soporta tildes, ñ, emojis)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS autocontrol_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE autocontrol_db;

-- =====================================================================
-- 1. ADMINISTRADORES
-- =====================================================================
CREATE TABLE administradores (
  id_administrador INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario           VARCHAR(60)  NOT NULL,
  contrasena        VARCHAR(255) NOT NULL,      -- guardar siempre HASH (bcrypt/argon2), nunca texto plano
  creado_en         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_usuario (usuario)
) ENGINE=InnoDB;

-- =====================================================================
-- 2. CLIENTES
-- =====================================================================
CREATE TABLE clientes (
  id_cliente     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(100) NOT NULL,
  apellido       VARCHAR(100) NOT NULL,
  documento      VARCHAR(30)  NULL,
  telefono       VARCHAR(20)  NULL,
  whatsapp       VARCHAR(20)  NULL,
  correo         VARCHAR(150) NULL,
  creado_en      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cliente_documento (documento)
) ENGINE=InnoDB;

-- =====================================================================
-- 3. VEHICULOS
-- =====================================================================
CREATE TABLE vehiculos (
  id_vehiculo         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cliente          INT UNSIGNED NOT NULL,
  placa               VARCHAR(15)  NOT NULL,
  marca               VARCHAR(60)  NULL,
  modelo              VARCHAR(60)  NULL,
  anio                SMALLINT     NULL,          -- "año"
  tipo_vehiculo       ENUM('carro','moto','camioneta','otro') NOT NULL DEFAULT 'carro',
  kilometraje_actual  INT UNSIGNED NOT NULL DEFAULT 0,
  creado_en           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  actualizado_en      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_vehiculo_placa (placa),
  KEY idx_vehiculo_cliente (id_cliente),
  CONSTRAINT fk_vehiculo_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 4. SERVICIOS  (cambio de aceite, bandas, llantas, alineación, balanceo, etc.)
-- =====================================================================
CREATE TABLE servicios (
  id_servicio     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_servicio VARCHAR(120)  NOT NULL,
  descripcion     TEXT          NULL,
  precio          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  duracion_km     INT UNSIGNED  NULL,    -- cada cuántos km se recomienda repetir
  duracion_dias   INT UNSIGNED  NULL,    -- cada cuántos días se recomienda repetir
  estado          ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 5. FACTURAS
-- =====================================================================
CREATE TABLE facturas (
  id_factura           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cliente           INT UNSIGNED NOT NULL,
  id_vehiculo          INT UNSIGNED NOT NULL,
  fecha                DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  kilometraje_actual   INT UNSIGNED NULL,
  kilometraje_proximo  INT UNSIGNED NULL,
  subtotal             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado               ENUM('pendiente','pagada','anulada') NOT NULL DEFAULT 'pendiente',
  creado_en            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY idx_factura_cliente (id_cliente),
  KEY idx_factura_vehiculo (id_vehiculo),
  CONSTRAINT fk_factura_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_factura_vehiculo
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 6. DETALLE_FACTURA  (una factura puede tener varios servicios)
-- =====================================================================
CREATE TABLE detalle_factura (
  id_detalle   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_factura   INT UNSIGNED NOT NULL,
  id_servicio  INT UNSIGNED NOT NULL,
  cantidad     INT UNSIGNED  NOT NULL DEFAULT 1,
  precio       DECIMAL(10,2) NOT NULL DEFAULT 0.00,   -- precio unitario al momento de facturar
  subtotal     DECIMAL(10,2) NOT NULL DEFAULT 0.00,   -- cantidad * precio
  KEY idx_detalle_factura (id_factura),
  KEY idx_detalle_servicio (id_servicio),
  CONSTRAINT fk_detalle_factura
    FOREIGN KEY (id_factura) REFERENCES facturas(id_factura)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detalle_servicio
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 7. MANTENIMIENTOS
-- =====================================================================
CREATE TABLE mantenimientos (
  id_mantenimiento       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cliente             INT UNSIGNED NOT NULL,
  id_vehiculo            INT UNSIGNED NOT NULL,
  id_servicio            INT UNSIGNED NOT NULL,
  fecha_servicio         DATE NOT NULL,
  proximo_mantenimiento  DATE NULL,
  kilometraje_actual     INT UNSIGNED NULL,
  kilometraje_proximo    INT UNSIGNED NULL,
  estado                 ENUM('pendiente','realizado','vencido') NOT NULL DEFAULT 'pendiente',
  creado_en              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mant_cliente (id_cliente),
  KEY idx_mant_vehiculo (id_vehiculo),
  KEY idx_mant_servicio (id_servicio),
  CONSTRAINT fk_mant_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mant_vehiculo
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mant_servicio
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 8. ALERTAS
-- =====================================================================
CREATE TABLE alertas (
  id_alerta        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cliente       INT UNSIGNED NOT NULL,
  id_vehiculo      INT UNSIGNED NOT NULL,
  id_mantenimiento INT UNSIGNED NULL,
  mensaje          VARCHAR(255) NOT NULL,
  fecha_alerta     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado           ENUM('pendiente','enviada','atendida') NOT NULL DEFAULT 'pendiente',
  KEY idx_alerta_cliente (id_cliente),
  KEY idx_alerta_vehiculo (id_vehiculo),
  KEY idx_alerta_mantenimiento (id_mantenimiento),
  CONSTRAINT fk_alerta_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_alerta_vehiculo
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_alerta_mantenimiento
    FOREIGN KEY (id_mantenimiento) REFERENCES mantenimientos(id_mantenimiento)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 9. USUARIOS  (cuenta del cliente, solo consulta)
-- =====================================================================
CREATE TABLE usuarios (
  id_usuario     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cliente     INT UNSIGNED NOT NULL,
  usuario        VARCHAR(60)  NOT NULL,
  contrasena     VARCHAR(255) NOT NULL,   -- guardar siempre HASH, nunca texto plano
  estado         ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario_usuario (usuario),
  KEY idx_usuario_cliente (id_cliente),
  CONSTRAINT fk_usuario_cliente
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 10. CONFIGURACION  (datos generales del taller)
-- =====================================================================
CREATE TABLE configuracion (
  id_configuracion       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_taller          VARCHAR(150) NOT NULL DEFAULT 'AutoControl',
  direccion              VARCHAR(200) NULL,
  telefono               VARCHAR(20)  NULL,
  whatsapp               VARCHAR(20)  NULL,
  logo                   VARCHAR(255) NULL,     -- ruta, URL o referencia al archivo del logo
  usuario_administrador  INT UNSIGNED NULL,
  actualizado_en         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_config_administrador
    FOREIGN KEY (usuario_administrador) REFERENCES administradores(id_administrador)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Almacenamiento JSON utilizado por Backend/api.php para la aplicación web.
CREATE TABLE IF NOT EXISTS almacenamiento (
  clave VARCHAR(100) NOT NULL PRIMARY KEY,
  valor LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Evita enviar dos veces el mismo recordatorio para la misma fecha y alerta.
CREATE TABLE IF NOT EXISTS recordatorios_enviados (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alerta_id VARCHAR(100) NOT NULL,
  regla_id VARCHAR(100) NOT NULL,
  fecha_programada DATE NOT NULL,
  enviado_en DATETIME NOT NULL,
  whatsapp_message_id VARCHAR(150) NULL,
  UNIQUE KEY uq_recordatorio (alerta_id, regla_id, fecha_programada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================================
-- Datos iniciales de ejemplo (opcional, puedes borrar o ajustar)
-- =====================================================================

-- Administrador por defecto. Cambia la contraseña real y guárdala con
-- password_hash() en PHP, bcrypt en Node, etc. Aquí solo dejo un ejemplo
-- con SHA2 como referencia, NO lo uses tal cual en producción.
INSERT INTO administradores (usuario, contrasena)
VALUES ('admin', SHA2('admin123', 256));

INSERT INTO configuracion (nombre_taller, direccion, telefono, whatsapp, usuario_administrador)
VALUES ('Taller Lubricantes Molina', 'Por definir', 'Por definir', 'Por definir', 1);

INSERT INTO servicios (nombre_servicio, descripcion, precio, duracion_km, duracion_dias, estado) VALUES
('Cambio de aceite', 'Cambio de aceite de motor y filtro', 45000, 5000, 180, 'activo'),
('Cambio de bandas', 'Revisión y cambio de bandas de distribución/accesorios', 90000, 40000, 730, 'activo'),
('Cambio de llantas', 'Cambio de llantas', 250000, 40000, NULL, 'activo'),
('Alineación', 'Alineación de dirección', 40000, 10000, 90, 'activo'),
('Balanceo', 'Balanceo de llantas', 30000, 10000, 90, 'activo');
