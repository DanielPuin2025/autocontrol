-- Script para agregar tabla de imágenes a AutoControl
-- Ejecutar: mysql -u root -p autocontrol_db < agregar_tabla_imagenes.sql

USE autocontrol_db;

-- Tabla para almacenar imágenes por separado (más eficiente)
CREATE TABLE IF NOT EXISTS imagenes (
  id_imagen INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_imagen ENUM('logo_taller','foto_local','foto_servicio','foto_producto') NOT NULL,
  referencia_id VARCHAR(50) NULL COMMENT 'ID del servicio/producto si aplica',
  imagen_data MEDIUMBLOB NOT NULL COMMENT 'Imagen en formato binario',
  mime_type VARCHAR(50) NOT NULL DEFAULT 'image/jpeg',
  tamanio_bytes INT UNSIGNED NOT NULL,
  ancho INT UNSIGNED NULL,
  alto INT UNSIGNED NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tipo_referencia (tipo_imagen, referencia_id),
  KEY idx_tipo (tipo_imagen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Almacenamiento optimizado de imágenes';

-- Agregar índice para búsquedas rápidas
CREATE INDEX idx_referencia ON imagenes(referencia_id);

-- Mensaje de confirmación
SELECT 'Tabla imagenes creada exitosamente' AS Resultado;
