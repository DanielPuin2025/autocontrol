# SOLUCIÓN URGENTE - Sincronización de Clientes

## 🔴 PROBLEMA DETECTADO

El frontend está guardando clientes en el JSON local pero **NO usa la API REST** de MySQL. Por eso:
- ✅ Los clientes SE GUARDAN en MySQL (vía api_clientes.php)
- ❌ Pero el frontend NO LOS CARGA desde MySQL
- ❌ Solo muestra los del JSON local

## ✅ SOLUCIÓN RÁPIDA

Hay **DOS opciones**:

### Opción 1: Usar Solo JSON (Sistema Actual)
**Ventaja:** No requiere cambios  
**Desventaja:** Solo funciona en un equipo/navegador

Los clientes se guardan en `localStorage` y en la tabla `almacenamiento` como JSON.

### Opción 2: Migrar a API REST (Recomendado)
**Ventaja:** Sincronización real entre equipos  
**Desventaja:** Requiere modificar el código

---

## 🚀 IMPLEMENTACIÓN OPCIÓN 2 (Recomendada)

### Paso 1: Modificar función de carga (línea ~271)

Buscar donde se carga `state.data` y agregar:

```javascript
async function loadData(){
  let shouldSaveInitialData = false;
  try{
    const raw = await storageGet(STORAGE_KEY);
    if(raw){
      state.data = JSON.parse(raw);
    } else {
      state.data = seedData();
      shouldSaveInitialData = true;
    }
  } catch(e){
    console.error('Error cargando datos:', e);
    state.data = seedData();
  }

  // NUEVO: Cargar clientes desde MySQL
  try {
    const res = await fetch('../Backend/api_clientes.php');
    if(res.ok) {
      const clientesMySQL = await res.json();
      state.data.clientes = clientesMySQL.map(c => ({
        id: 'c' + c.id_cliente,
        nombre: c.nombre + ' ' + c.apellido,
        telefono: c.telefono || '',
        correo: c.correo || '',
        placa: '', // Se obtiene de vehiculos
        vehiculo: 'Automóvil',
        whatsappConsent: false
      }));
      console.log('✓ Clientes cargados desde MySQL:', state.data.clientes.length);
    }
  } catch(e) {
    console.warn('No se pudieron cargar clientes desde MySQL, usando JSON local');
  }

  // Resto del código...
}
```

### Paso 2: Modificar función de guardar cliente (línea ~901)

```javascript
async function saveCliente(ev){
  ev.preventDefault();
  const f = ev.target;
  const nombre = f.nombre.value.trim();
  const telefono = f.telefono.value.trim();
  const placa = f.placa.value.trim();
  const vehiculo = f.vehiculo.value;
  const whatsappConsent = !!f.whatsappConsent?.checked;
  const banner = $('#form-banner');
  
  if(!nombre || !telefono || !placa || !vehiculo){
    banner.textContent = 'Por favor completa nombre, teléfono, tipo de vehículo y placa.';
    banner.classList.add('show');
    banner.scrollIntoView({behavior:'smooth', block:'center'});
    return;
  }

  const id = f.dataset.id;

  // NUEVO: Guardar en MySQL
  try {
    const [nombreCliente, apellido] = nombre.split(' ', 2);
    
    const clienteData = {
      nombre: nombreCliente || nombre,
      apellido: apellido || '',
      telefono: telefono,
      whatsapp: whatsappConsent ? telefono : null,
      correo: f.correo.value.trim()
    };

    let clienteId;
    if(id) {
      // Actualizar
      const idNumerico = id.replace('c', '');
      await fetch(`../Backend/api_clientes.php?id=${idNumerico}`, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(clienteData)
      });
      clienteId = idNumerico;
    } else {
      // Crear nuevo
      const res = await fetch('../Backend/api_clientes.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(clienteData)
      });
      const result = await res.json();
      clienteId = result.id;
    }

    // Guardar vehículo asociado
    const vehiculoData = {
      id_cliente: clienteId,
      placa: placa.toUpperCase(),
      tipo_vehiculo: vehiculo === 'Automóvil' ? 'carro' : 
                     vehiculo === 'Motocicleta' ? 'moto' : 
                     vehiculo === 'Camioneta' ? 'camioneta' : 'otro'
    };

    await fetch('../Backend/api_vehiculos.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(vehiculoData)
    });

    // También guardar en el JSON local para compatibilidad
    const payload = { nombre, telefono, correo: f.correo.value.trim(), placa, vehiculo, whatsappConsent };
    if(id) {
      Object.assign(state.data.clientes.find(x=>x.id===id), payload);
    } else {
      state.data.clientes.push({id: 'c' + clienteId, ...payload});
    }

    await persist();
    closeForm();
    openSuccess(id ? 'Cliente actualizado correctamente.' : 'Cliente creado correctamente.');
    
    // Recargar clientes desde MySQL
    await loadData();
    render();

  } catch(error) {
    console.error('Error guardando cliente:', error);
    banner.textContent = 'Error al guardar: ' + error.message;
    banner.classList.add('show');
  }
}
```

---

## ⚡ SOLUCIÓN TEMPORAL MÁS RÁPIDA

Si no quieres modificar código ahora, puedes:

1. **Exportar clientes de MySQL** y meterlos en el JSON:

```javascript
// En la consola del navegador (F12):
fetch('/Backend/api_clientes.php')
  .then(r => r.json())
  .then(clientes => {
    const clientesFormateados = clientes.map(c => ({
      id: 'c' + c.id_cliente,
      nombre: c.nombre + ' ' + c.apellido,
      telefono: c.telefono || '',
      correo: c.correo || '',
      placa: '', 
      vehiculo: 'Automóvil',
      whatsappConsent: false
    }));
    console.log('Clientes:', clientesFormateados);
    // Copiar y pegar este array manualmente
  });
```

---

## 🎯 RECOMENDACIÓN

**Para que REALMENTE se sincronicen entre equipos**, necesitas implementar la Opción 2.

¿Quieres que modifique el código de `app.js` ahora para que use las APIs REST?
