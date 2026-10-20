/* Funciones auxiliares para manejar imágenes con la nueva API */

// Guardar imagen en la tabla imagenes
async function guardarImagen(tipo, imagenDataUrl, referencia = null, dimensiones = {}) {
  try {
    const res = await fetch(API_IMAGENES_URL, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        tipo: tipo,
        imagen: imagenDataUrl,
        referencia: referencia,
        width: dimensiones.width,
        height: dimensiones.height
      })
    });

    if (!res.ok) {
      const error = await res.json();
      throw new Error(error.error || 'Error al guardar imagen');
    }

    return await res.json();
  } catch (error) {
    console.error('Error guardando imagen:', error);
    throw error;
  }
}

// Cargar imagen desde la tabla imagenes
async function cargarImagen(tipo, referencia = null) {
  try {
    let url = `${API_IMAGENES_URL}?tipo=${tipo}`;
    if (referencia) url += `&ref=${referencia}`;

    const res = await fetch(url);

    if (res.status === 404) return null; // No existe la imagen

    if (!res.ok) {
      const error = await res.json();
      throw new Error(error.error || 'Error al cargar imagen');
    }

    const data = await res.json();
    return data.data_url;
  } catch (error) {
    console.error('Error cargando imagen:', error);
    return null;
  }
}

// Eliminar imagen de la tabla imagenes
async function eliminarImagen(tipo, referencia = null) {
  try {
    let url = `${API_IMAGENES_URL}?tipo=${tipo}`;
    if (referencia) url += `&ref=${referencia}`;

    const res = await fetch(url, { method: 'DELETE' });

    if (!res.ok) {
      const error = await res.json();
      throw new Error(error.error || 'Error al eliminar imagen');
    }

    return await res.json();
  } catch (error) {
    console.error('Error eliminando imagen:', error);
    throw error;
  }
}
