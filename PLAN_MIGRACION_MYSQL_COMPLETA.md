# PLAN DE MIGRACIÓN COMPLETA A MYSQL

## 🎯 OBJETIVO
Que TODO se guarde y cargue desde MySQL, sin perder funcionalidad.

## 📊 ESTADO ACTUAL

### ✅ YA EN MYSQL (Backend listo):
- Clientes (api_clientes.php) ✅
- Vehículos (api_vehiculos.php) ✅
- Servicios (api_servicios.php) ✅ 9 servicios
- Mantenimientos (api_mantenimientos.php) ✅
- Facturas (api_facturas.php) ✅
- Usuarios (api_usuarios.php) ✅
- Imágenes (api_imagenes.php) ✅

### ❌ PROBLEMA EN FRONTEND:
- state.data.servicios usado 35 veces
- state.data.clientes usado en múltiples lugares
- state.data.mantenimientos, facturas, etc.
- TODO sigue leyendo del JSON local

## 🔧 SOLUCIÓN

### Fase 1: Modificar loadData() (línea 268)
```javascript
async function loadData(){
  // 1. Cargar configuración desde JSON (auth, config, alertas)
  const raw = await storageGet(STORAGE_KEY);
  state.data = raw ? JSON.parse(raw) : seedData();
  
  // 2. Cargar TODOS los datos desde MySQL
  await cargarDatosDesdeMySQL();
  
  // 3. Renderizar
  render();
}

async function cargarDatosDesdeMySQL(){
  try {
    // Cargar clientes
    const resClientes = await fetch('../Backend/api_clientes.php');
    if(resClientes.ok) {
      const clientesDB = await resClientes.json();
      state.data.clientes = convertirClientesDesdeMySQL(clientesDB);
    }

    // Cargar vehículos
    const resVehiculos = await fetch('../Backend/api_vehiculos.php');
    if(resVehiculos.ok) {
      const vehiculosDB = await resVehiculos.json();
      // Asociar vehículos a clientes
      asociarVehiculosAClientes(vehiculosDB);
    }

    // Cargar servicios
    const resServicios = await fetch('../Backend/api_servicios.php');
    if(resServicios.ok) {
      const serviciosDB = await resServicios.json();
      state.data.servicios = convertirServiciosDesdeMySQL(serviciosDB);
    }

    // Cargar mantenimientos
    const resMant = await fetch('../Backend/api_mantenimientos.php');
    if(resMant.ok) {
      const mantDB = await resMant.json();
      state.data.mantenimientos = convertirMantenimientosDesdeMySQL(mantDB);
    }

    // Cargar facturas
    const resFacturas = await fetch('../Backend/api_facturas.php');
    if(resFacturas.ok) {
      const facturasDB = await resFacturas.json();
      state.data.facturas = convertirFacturasDesdeMySQL(facturasDB);
    }

    console.log('✅ Todos los datos cargados desde MySQL');
  } catch(e) {
    console.error('Error cargando datos desde MySQL:', e);
  }
}
```

### Fase 2: Modificar funciones de guardado

#### saveServicio() - Guardar en MySQL primero
```javascript
async function saveServicio(ev){
  // ... validaciones ...
  
  // NUEVO: Guardar en MySQL
  const servicioData = {
    nombre_servicio: nombre,
    descripcion: f.descripcion.value,
    precio: precio,
    duracion_km: intervaloKm,
    duracion_dias: intervaloDias
  };

  let servicioId;
  if(id) {
    // Actualizar en MySQL
    await fetch(`../Backend/api_servicios.php?id=${idNumerico}`, {
      method: 'PUT',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(servicioData)
    });
    servicioId = id;
  } else {
    // Crear en MySQL
    const res = await fetch('../Backend/api_servicios.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(servicioData)
    });
    const result = await res.json();
    servicioId = 's' + result.id;
  }

  // Guardar imagen si hay
  if(state.servicioImagenTemp && state.servicioImagenTemp !== '__REMOVE__') {
    await guardarImagenServicio(servicioId, state.servicioImagenTemp);
  }

  // Actualizar caché local
  await cargarDatosDesdeMySQL();
  
  closeForm();
  openSuccess('Servicio guardado correctamente');
}
```

#### saveCliente() - Guardar en MySQL primero
```javascript
async function saveCliente(ev){
  // ... validaciones ...

  const clienteData = {
    nombre: nombreParte,
    apellido: apellidoParte,
    documento: f.documento?.value || '',
    telefono: telefono,
    whatsapp: whatsappConsent ? telefono : null,
    correo: f.correo.value
  };

  let clienteId;
  if(id) {
    // Actualizar
    await fetch(`../Backend/api_clientes.php?id=${idNumerico}`, {
      method: 'PUT',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(clienteData)
    });
    clienteId = idNumerico;
  } else {
    // Crear
    const res = await fetch('../Backend/api_clientes.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(clienteData)
    });
    const result = await res.json();
    clienteId = result.id;
  }

  // Guardar vehículo
  const vehiculoData = {
    id_cliente: clienteId,
    placa: placa,
    tipo_vehiculo: convertirTipoVehiculo(vehiculo)
  };
  await fetch('../Backend/api_vehiculos.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(vehiculoData)
  });

  // Recargar datos
  await cargarDatosDesdeMySQL();
  
  closeForm();
  openSuccess('Cliente guardado correctamente');
}
```

### Fase 3: Funciones auxiliares de conversión

```javascript
function convertirClientesDesdeMySQL(clientesDB){
  return clientesDB.map(c => ({
    id: 'c' + c.id_cliente,
    nombre: `${c.nombre} ${c.apellido || ''}`.trim(),
    telefono: c.telefono || '',
    correo: c.correo || '',
    whatsappConsent: !!c.whatsapp,
    // placa y vehiculo se obtienen de la tabla vehiculos
  }));
}

function convertirServiciosDesdeMySQL(serviciosDB){
  return serviciosDB.map(s => ({
    id: 's' + s.id_servicio,
    nombre: s.nombre_servicio,
    descripcion: s.descripcion || '',
    precio: parseFloat(s.precio),
    intervaloKm: s.duracion_km || 5000,
    intervaloDias: s.duracion_dias || 180,
    imagen: 'DB', // Marcador para cargar desde api_imagenes
    categoria: detectarCategoria(s.nombre_servicio),
    vehiculos: ['Automóvil', 'Camioneta', 'Motocicleta']
  }));
}

function detectarCategoria(nombre){
  const n = nombre.toLowerCase();
  if(n.includes('aceite')) return 'Cambio de aceite';
  if(n.includes('filtro')) return 'Filtros';
  if(n.includes('freno')) return 'Frenos';
  if(n.includes('llanta')) return 'Cambio de llantas';
  return 'Mantenimiento';
}
```

## ⚠️ PROBLEMA CON IMÁGENES

El problema de las imágenes que desaparecen es porque:
1. Se guarda en tabla imagenes con referencia = s3, s4
2. Pero al recargar, los IDs pueden cambiar
3. Solución: Usar id_servicio numérico de MySQL directamente

## 🚀 IMPLEMENTACIÓN

1. ✅ Crear funciones de conversión
2. ✅ Modificar loadData() para cargar desde MySQL
3. ✅ Modificar saveServicio() para guardar en MySQL
4. ✅ Modificar saveCliente() para guardar en MySQL
5. ✅ Modificar todas las funciones de guardado
6. ✅ Mantener state.data como caché
7. ✅ Usar IDs numéricos de MySQL como referencia

## 📝 ARCHIVOS A MODIFICAR

- Frontend/app.js (principal)
- Backend/* (ya están listos)

## 🎯 RESULTADO ESPERADO

- ✅ Cliente creado → guardado en MySQL → visible en todos los equipos
- ✅ Servicio creado → guardado en MySQL → visible en todos los equipos
- ✅ Imagen subida → guardada en MySQL → visible siempre
- ✅ Factura creada → guardada en MySQL → visible en todos los equipos
- ✅ TODO sincronizado en tiempo real
- ✅ NO más problemas con caché o imágenes que desaparecen
