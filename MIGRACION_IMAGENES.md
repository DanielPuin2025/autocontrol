# Migración a Sistema de Imágenes con Tabla Dedicada

## 📋 Objetivo

Separar las imágenes del JSON principal y guardarlas en una tabla `imagenes` dedicada. Esto mejora:
- ✅ **Rendimiento**: El JSON principal es más pequeño
- ✅ **Sincronización**: Las imágenes se guardan por separado
- ✅ **Escalabilidad**: Fácil agregar más tipos de imágenes
- ✅ **Almacenamiento**: Usa BLOB binario (más eficiente que base64 en JSON)

## 🗄️ Paso 1: Crear la Tabla de Imágenes

Ejecutar en MySQL:

```bash
mysql -u root -p autocontrol_db < Base_de_datos/agregar_tabla_imagenes.sql
```

O manualmente:

```sql
USE autocontrol_db;

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
  KEY idx_tipo (tipo_imagen),
  KEY idx_referencia (referencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🚀 Paso 2: API de Imágenes

Ya está creada: `Backend/api_imagenes.php`

### Endpoints:

**Obtener imagen:**
```
GET /Backend/api_imagenes.php?tipo=foto_local
GET /Backend/api_imagenes.php?tipo=foto_servicio&ref=s123
```

**Guardar imagen:**
```
POST /Backend/api_imagenes.php
Body: {
  "tipo": "foto_local",
  "imagen": "data:image/jpeg;base64,...",
  "referencia": null,
  "width": 1280,
  "height": 720
}
```

**Eliminar imagen:**
```
DELETE /Backend/api_imagenes.php?tipo=foto_local
```

## 📝 Paso 3: Modificar Frontend

### 3.1 Agregar constante en app.js (línea 2, después de API_URL):

```javascript
const API_URL = '../Backend/api.php';
const API_IMAGENES_URL = '../Backend/api_imagenes.php';
```

### 3.2 Modificar función de carga de imagen del local:

Buscar la función `onConfigFotoLocalChange` (alrededor de línea 2132) y cambiar:

```javascript
async function onConfigFotoLocalChange(input){
  const file = input.files && input.files[0];
  if(!file) return;

  if(file.size > 5 * 1024 * 1024){
    showToast('La imagen es demasiado grande. Máximo 5MB.', true);
    input.value = '';
    return;
  }

  showToast('Procesando imagen...');

  const reader = new FileReader();
  reader.onload = async function(e){
    const img = new Image();
    img.onload = async function(){
      try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        let width = img.width;
        let height = img.height;
        const maxWidth = 1280;
        const maxHeight = 720;

        if(width > maxWidth || height > maxHeight){
          const ratio = Math.min(maxWidth / width, maxHeight / height);
          width = Math.floor(width * ratio);
          height = Math.floor(height * ratio);
        }

        canvas.width = width;
        canvas.height = height;
        ctx.drawImage(img, 0, 0, width, height);

        const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.7);

        // Guardar en tabla imagenes
        try {
          const res = await fetch(API_IMAGENES_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
              tipo: 'foto_local',
              imagen: compressedDataUrl,
              referencia: null,
              width: width,
              height: height
            })
          });

          if(!res.ok) {
            const error = await res.json();
            throw new Error(error.error || 'Error al guardar imagen');
          }

          // Ya no guardar en config.fotoLocal, solo marcar que existe
          state.data.config.fotoLocal = 'DB'; // Marcador
          await persist();
          
          showToast('✓ Foto del local guardada correctamente.');
          render();
        } catch(error) {
          showToast('Error al guardar la foto: ' + error.message, true);
          input.value = '';
        }
      } catch(error) {
        showToast('Error al procesar la imagen: ' + error.message, true);
        input.value = '';
      }
    };
    img.onerror = function(){
      showToast('Error al cargar la imagen. Verifica que sea un archivo válido.', true);
      input.value = '';
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}
```

### 3.3 Cargar imagen al renderizar:

Buscar donde se aplica el fondo (alrededor de línea 3049) y modificar:

```javascript
// Cargar foto del local desde la tabla imagenes
let fotoLocal = null;
if(state.data.config && state.data.config.fotoLocal === 'DB'){
  try {
    const res = await fetch(`${API_IMAGENES_URL}?tipo=foto_local`);
    if(res.ok){
      const data = await res.json();
      fotoLocal = data.data_url;
    }
  } catch(e) {
    console.error('Error cargando foto local:', e);
  }
}

const vistasPublicas = ['welcome','login','registro','loading'];
if(fotoLocal && vistasPublicas.includes(state.view)){
  // ... resto del código de aplicación de fondo
}
```

## ✅ Ventajas del Nuevo Sistema

| Antes | Después |
|-------|---------|
| JSON de 2.5MB | JSON de ~50KB |
| Todo en `almacenamiento` | Imágenes en tabla dedicada |
| Base64 en JSON | Binario en BLOB |
| Lento al sincronizar | Rápido al sincronizar |
| Límites de tamaño PHP | Manejo optimizado |

## 🧪 Probar

1. Crear tabla: `mysql -u root -p autocontrol_db < Base_de_datos/agregar_tabla_imagenes.sql`
2. Redesplegar aplicación
3. Subir foto del local
4. Verificar en MySQL: `SELECT tipo_imagen, tamanio_bytes FROM imagenes;`
5. Abrir en otro equipo → debería aparecer

## 📌 Notas

- La API ya está lista en `Backend/api_imagenes.php`
- El SQL está en `Base_de_datos/agregar_tabla_imagenes.sql`
- Se necesita modificar `app.js` manualmente (pasos arriba)
- Compatible con sistema anterior (migración gradual)

## 🔄 Migración de Datos Existentes

Si ya tienes fotos en el sistema antiguo, ejecuta este script:

```php
<?php
// migrar_imagenes.php
require_once 'Backend/db.php';

// Obtener datos del sistema antiguo
$stmt = $pdo->prepare('SELECT valor FROM almacenamiento WHERE clave = "autocontrol_data_v1"');
$stmt->execute();
$data = json_decode($stmt->fetch()['valor'], true);

// Migrar foto del local
if(!empty($data['config']['fotoLocal']) && strpos($data['config']['fotoLocal'], 'data:') === 0){
    // Extraer datos
    preg_match('/^data:(image\/\w+);base64,(.*)$/', $data['config']['fotoLocal'], $matches);
    $mimeType = $matches[1];
    $imagenBinaria = base64_decode($matches[2]);
    
    // Guardar en tabla imagenes
    $stmt = $pdo->prepare('INSERT INTO imagenes (tipo_imagen, referencia_id, imagen_data, mime_type, tamanio_bytes) VALUES ("foto_local", NULL, ?, ?, ?) ON DUPLICATE KEY UPDATE imagen_data = ?, mime_type = ?');
    $stmt->execute([$imagenBinaria, $mimeType, strlen($imagenBinaria), $imagenBinaria, $mimeType]);
    
    echo "Foto del local migrada\n";
}

echo "Migración completada\n";
?>
```

---

**¿Quieres que implemente esto completamente en el código?** 🚀
