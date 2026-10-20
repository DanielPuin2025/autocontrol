// Script de migración AUTOMÁTICA a tabla relacional servicios_vehiculos
// Ejecutar DESPUÉS de crear la tabla servicios_vehiculos en MySQL
// Paso 1: Analizar servicios actuales
// Paso 2: Consolidar duplicados
// Paso 3: Crear relaciones por tipo de vehículo

async function migrarATablaRelacional() {
  console.log('🚀 Iniciando migración a tabla relacional...');
  console.log('⚠️ ADVERTENCIA: Este proceso es IRREVERSIBLE');
  console.log('⏳ Analizando servicios actuales...\n');

  try {
    // Obtener todos los servicios actuales
    const res = await fetch('../Backend/api_servicios.php');
    if (!res.ok) {
      console.error('❌ Error al obtener servicios');
      return;
    }

    const servicios = await res.json();
    console.log('📦 Total de servicios en MySQL:', servicios.length);

    // Agrupar servicios por nombre base (sin palabras como "moto", "carro", etc.)
    const grupos = {};
    const palabrasClave = ['moto', 'motocicleta', 'carro', 'automovil', 'auto', 'camioneta', 'camión'];

    servicios.forEach(s => {
      let nombreBase = s.nombre_servicio.toLowerCase();

      // Remover palabras clave de tipo de vehículo
      palabrasClave.forEach(palabra => {
        const regex = new RegExp(`\\b${palabra}\\b`, 'gi');
        nombreBase = nombreBase.replace(regex, '');
      });

      // Limpiar espacios múltiples y trim
      nombreBase = nombreBase.replace(/\s+/g, ' ').trim();

      // Detectar tipo de vehículo del nombre original
      const nombreOriginal = s.nombre_servicio.toLowerCase();
      let tipoDetectado = 'carro'; // Por defecto

      if (nombreOriginal.includes('moto')) {
        tipoDetectado = 'moto';
      } else if (nombreOriginal.includes('camioneta') || nombreOriginal.includes('camión')) {
        tipoDetectado = 'camioneta';
      }

      if (!grupos[nombreBase]) {
        grupos[nombreBase] = [];
      }

      grupos[nombreBase].push({
        ...s,
        tipo_detectado: tipoDetectado,
        nombre_base: nombreBase
      });
    });

    console.log('\n📊 ANÁLISIS DE CONSOLIDACIÓN:');
    let serviciosBase = 0;
    let serviciosDuplicados = 0;

    for (const nombreBase in grupos) {
      const grupo = grupos[nombreBase];
      serviciosBase++;
      if (grupo.length > 1) {
        console.log(`🔍 "${grupo[0].nombre_servicio}" → ${grupo.length} variantes`);
        serviciosDuplicados += (grupo.length - 1);
      }
    }

    console.log(`\n✅ Servicios base únicos: ${serviciosBase}`);
    console.log(`🔄 Servicios a consolidar: ${serviciosDuplicados}`);
    console.log(`📦 Total relaciones a crear: ${servicios.length}`);

    console.log('\n⏳ Iniciando consolidación...\n');

    let consolidados = 0;
    let relacionesCreadas = 0;
    let errores = 0;

    for (const nombreBase in grupos) {
      const grupo = grupos[nombreBase];

      try {
        // Paso 1: Usar el servicio con ID más bajo como base
        grupo.sort((a, b) => a.id_servicio - b.id_servicio);
        const servicioBase = grupo[0];

        console.log(`📝 Consolidando: "${servicioBase.nombre_servicio}"`);

        // Paso 2: Actualizar nombre del servicio base (sin tipo de vehículo)
        const nombreLimpio = servicioBase.nombre_servicio
          .replace(/\b(moto|motocicleta|carro|automovil|auto|camioneta|camión)\b/gi, '')
          .replace(/\s+/g, ' ')
          .trim();

        await fetch(`../Backend/api_servicios.php?id=${servicioBase.id_servicio}`, {
          method: 'PUT',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            nombre_servicio: nombreLimpio.charAt(0).toUpperCase() + nombreLimpio.slice(1),
            descripcion: servicioBase.descripcion,
            precio: servicioBase.precio,
            duracion_km: servicioBase.duracion_km,
            duracion_dias: servicioBase.duracion_dias,
            estado: 'activo'
          })
        });

        // Paso 3: Crear relaciones para cada variante
        for (const variante of grupo) {
          try {
            await fetch('../Backend/api_servicios_vehiculos.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({
                id_servicio: servicioBase.id_servicio,
                tipo_vehiculo: variante.tipo_detectado,
                precio_especifico: variante.precio,
                duracion_km_especifica: variante.duracion_km,
                duracion_dias_especifica: variante.duracion_dias,
                activo: true
              })
            });

            console.log(`  ✅ Relación creada: ${variante.tipo_detectado} → $${variante.precio}`);
            relacionesCreadas++;
          } catch (e) {
            console.warn(`  ⚠️ Error creando relación para ${variante.tipo_detectado}`);
          }
        }

        // Paso 4: Eliminar servicios duplicados (mantener solo el base)
        for (let i = 1; i < grupo.length; i++) {
          try {
            await fetch(`../Backend/api_servicios.php?id=${grupo[i].id_servicio}`, {
              method: 'DELETE'
            });
            console.log(`  🗑️ Duplicado eliminado: ID ${grupo[i].id_servicio}`);
          } catch (e) {
            console.warn(`  ⚠️ Error eliminando duplicado ${grupo[i].id_servicio}`);
          }
        }

        consolidados++;
        await new Promise(resolve => setTimeout(resolve, 200));

      } catch (error) {
        errores++;
        console.error(`❌ Error consolidando "${nombreBase}":`, error.message);
      }
    }

    console.log('\n📊 RESUMEN DE MIGRACIÓN:');
    console.log('✅ Servicios consolidados:', consolidados);
    console.log('✅ Relaciones creadas:', relacionesCreadas);
    console.log('❌ Errores:', errores);
    console.log(`📦 Servicios finales: ~${serviciosBase}`);

    console.log('\n🎉 ¡Migración completada!');
    console.log('👉 Recarga la página (Ctrl+Shift+R) para ver los cambios');
    console.log('📝 Verifica que los servicios se muestren correctamente');

  } catch (error) {
    console.error('❌ Error en la migración:', error);
  }
}

// Ejecutar
migrarATablaRelacional();
