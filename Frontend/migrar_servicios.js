// Script para migrar servicios del JSON a MySQL
// Ejecutar una vez en la consola del navegador (F12)

async function migrarServiciosAMySQL() {
  console.log('🔄 Iniciando migración de servicios a MySQL...');

  try {
    // Obtener servicios actuales del JSON
    const raw = localStorage.getItem('autocontrol_data_v1');
    if (!raw) {
      console.error('❌ No hay datos en localStorage');
      return;
    }

    const data = JSON.parse(raw);
    const servicios = data.servicios || [];

    console.log('📦 Total de servicios en JSON:', servicios.length);

    // Obtener servicios existentes en MySQL
    const resMySQL = await fetch('../Backend/api_servicios.php');
    const serviciosMySQL = resMySQL.ok ? await resMySQL.json() : [];
    const idsMySQL = new Set(serviciosMySQL.map(s => 's' + s.id_servicio));

    console.log('🗄️ Servicios ya en MySQL:', serviciosMySQL.length);

    // Filtrar solo los que NO están en MySQL
    const serviciosAMigrar = servicios.filter(s => !idsMySQL.has(s.id));

    console.log('⬆️ Servicios a migrar:', serviciosAMigrar.length);

    if (serviciosAMigrar.length === 0) {
      console.log('✅ No hay servicios nuevos para migrar');
      return;
    }

    // Migrar cada servicio
    let migrados = 0;
    let errores = 0;

    for (const servicio of serviciosAMigrar) {
      try {
        const servicioData = {
          nombre_servicio: servicio.nombre,
          descripcion: servicio.descripcion || '',
          precio: servicio.precio || 0,
          duracion_km: servicio.intervaloKm || 5000,
          duracion_dias: servicio.intervaloDias || 180,
          estado: 'activo'
        };

        const res = await fetch('../Backend/api_servicios.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(servicioData)
        });

        if (res.ok) {
          const result = await res.json();
          migrados++;
          console.log('✅', servicio.nombre, '→ MySQL (ID:', result.id, ')');

          // Si tiene imagen, migrarla también
          if (servicio.imagen && servicio.imagen.startsWith('data:')) {
            try {
              await fetch('../Backend/api_imagenes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  tipo: 'foto_servicio',
                  imagen: servicio.imagen,
                  referencia: 's' + result.id
                })
              });
              console.log('  📷 Imagen migrada');
            } catch (e) {
              console.warn('  ⚠️ No se pudo migrar imagen:', e.message);
            }
          }
        } else {
          errores++;
          console.error('❌', servicio.nombre, '→ Error');
        }

        // Pequeña pausa para no saturar
        await new Promise(resolve => setTimeout(resolve, 100));

      } catch (error) {
        errores++;
        console.error('❌', servicio.nombre, '→', error.message);
      }
    }

    console.log('\n📊 RESUMEN DE MIGRACIÓN:');
    console.log('✅ Migrados exitosamente:', migrados);
    console.log('❌ Errores:', errores);
    console.log('📦 Total en MySQL:', serviciosMySQL.length + migrados);

    if (migrados > 0) {
      console.log('\n🎉 ¡Migración completada!');
      console.log('👉 Ahora recarga la página (Ctrl+Shift+R) para ver todos los servicios');
    }

  } catch (error) {
    console.error('❌ Error en la migración:', error);
  }
}

// Ejecutar
migrarServiciosAMySQL();
