-- Script para agregar soporte de servicios por tipo de vehículo
-- Ejecutar en MySQL: mysql -u root -p autocontrol_db < agregar_servicios_vehiculos.sql

USE autocontrol_db;

-- =====================================================================
-- NUEVA TABLA: servicios_vehiculos
-- Relaciona servicios con tipos de vehículos y precios específicos
-- =====================================================================
CREATE TABLE IF NOT EXISTS servicios_vehiculos (
  id_servicio_vehiculo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_servicio          INT UNSIGNED NOT NULL,
  tipo_vehiculo        ENUM('carro','moto','camioneta') NOT NULL,
  precio_especifico    DECIMAL(10,2) NULL COMMENT 'Precio para este tipo de vehículo (opcional)',
  duracion_km_especifica  INT UNSIGNED NULL COMMENT 'KM específicos para este tipo (opcional)',
  duracion_dias_especifica INT UNSIGNED NULL COMMENT 'Días específicos para este tipo (opcional)',
  notas                TEXT NULL,
  activo               BOOLEAN NOT NULL DEFAULT TRUE,
  creado_en            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_servicio_vehiculo (id_servicio, tipo_vehiculo),
  KEY idx_servicio (id_servicio),
  KEY idx_tipo_vehiculo (tipo_vehiculo),

  CONSTRAINT fk_sv_servicio
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Relación de servicios con tipos de vehículos';

-- =====================================================================
-- MIGRACIÓN DE DATOS EXISTENTES
-- Si hay servicios en JSON con array vehiculos, se migrarían aquí
-- =====================================================================

-- Ejemplo: Si un servicio "Cambio de aceite" existe para los 3 tipos:
-- INSERT INTO servicios_vehiculos (id_servicio, tipo_vehiculo, precio_especifico) VALUES
-- (1, 'carro', 50000.00),
-- (1, 'moto', 30000.00),
-- (1, 'camioneta', 60000.00);

-- =====================================================================
-- VISTA PARA CONSULTAS SIMPLIFICADAS
-- =====================================================================
CREATE OR REPLACE VIEW v_servicios_completos AS
SELECT
  s.id_servicio,
  s.nombre_servicio,
  s.descripcion,
  s.precio AS precio_base,
  s.duracion_km AS duracion_km_base,
  s.duracion_dias AS duracion_dias_base,
  s.estado,
  sv.tipo_vehiculo,
  COALESCE(sv.precio_especifico, s.precio) AS precio_final,
  COALESCE(sv.duracion_km_especifica, s.duracion_km) AS duracion_km_final,
  COALESCE(sv.duracion_dias_especifica, s.duracion_dias) AS duracion_dias_final,
  sv.activo AS disponible_para_vehiculo
FROM servicios s
LEFT JOIN servicios_vehiculos sv ON s.id_servicio = sv.id_servicio
WHERE s.estado = 'activo';

SELECT '✅ Tabla servicios_vehiculos creada exitosamente' AS Resultado;
