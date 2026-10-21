-- Script para corregir referencias de imágenes de servicios
-- Convierte referencias de formato 's123' a formato '123'
-- Ejecutar: mysql -u root -p autocontrol_db < corregir_referencias_imagenes.sql

USE autocontrol_db;

-- Ver referencias actuales antes de corregir
SELECT
    id_imagen,
    tipo_imagen,
    referencia_id AS referencia_antes,
    CASE
        WHEN referencia_id LIKE 's%' THEN SUBSTRING(referencia_id, 2)
        ELSE referencia_id
    END AS referencia_despues
FROM imagenes
WHERE tipo_imagen = 'foto_servicio' AND referencia_id IS NOT NULL;

-- Actualizar referencias que empiezan con 's'
UPDATE imagenes
SET referencia_id = SUBSTRING(referencia_id, 2)
WHERE tipo_imagen = 'foto_servicio'
  AND referencia_id LIKE 's%';

-- Mostrar resultado
SELECT
    COUNT(*) AS imagenes_corregidas
FROM imagenes
WHERE tipo_imagen = 'foto_servicio' AND referencia_id IS NOT NULL;

SELECT 'Referencias de imágenes corregidas exitosamente' AS Resultado;
