<?php

namespace App\Services\Ai;

/**
 * "Manual" de MANTIA que se le entrega a la IA en cada consulta.
 *
 * Aquí está TODO lo que la IA sabe sobre cómo funciona la plataforma
 * (pantallas, botones, roles y flujos). Si el proyecto cambia — una
 * pantalla nueva, un botón que se mueve, un rol nuevo — se actualiza
 * este archivo y la IA lo aprende de inmediato, sin tocar nada más.
 *
 * Los DATOS (máquinas, gases, stock...) NO van aquí: esos los consulta
 * la IA en vivo con las herramientas de AiTools.
 */
class MantiaConocimiento
{
    public static function texto(): string
    {
        $gas = config('alertas.gas');
        $stockMinimo = (int) config('alertas.stock_minimo_herramienta');

        return <<<TXT
# Qué es MANTIA
MANTIA es un sistema de mantenimiento e inventario de activos para el sector minero, desarrollado como proyecto formativo SENA por aprendices de Análisis y Desarrollo de Software del Centro Nacional Minero (Sogamoso, Boyacá). Centraliza el control de máquinas, equipos, herramientas, ubicaciones, empresas, mediciones de gases, inspecciones y uso diario de maquinaria de la mina didáctica.
Contacto de soporte: mantiaadso@gmail.com.

# Navegación
- Menú lateral (botón de tres rayas arriba a la izquierda lo abre o cierra): Panel de Control, Herramientas (Consumibles / No consumibles), Equipos, Máquinas, Ubicaciones, Empresas, Acerca de, Ayuda y Cerrar sesión.
- Barra superior: campana de notificaciones (alertas del sistema) y botón de perfil (Ver mi perfil, Cerrar sesión).
- Botón de accesibilidad: círculo verde abajo a la derecha (tamaño de letra, contraste, etc.).
- Chat MANTIA IA: el asistente (tú), disponible en todas las pantallas internas.

# Módulos
- Panel de Control: resumen general con tarjetas de máquinas, equipos, herramientas consumibles y no consumibles, ubicaciones, gases, uso diario y actividad reciente.
- Herramientas: se dividen en dos grupos, "Herramientas consumibles" y "Herramientas no consumibles". En el menú Herramientas se elige el grupo; allí se ven sus categorías y, al entrar a una categoría, las herramientas. Cada herramienta tiene ubicación, categoría y stock separado por condición: bueno, regular y malo. Una herramienta tiene stock bajo cuando su stock total es {$stockMinimo} o menos, y eso genera alerta.
- Equipos: lista de equipos con código, nombre, modelo, número de serie, responsable, empresa, ubicación, fecha de registro, imagen y estado (activo, inactivo o en mantenimiento). Al abrir la ficha de un equipo están los botones "Medición gases" y "Calibración".
- Máquinas: lista de máquinas con código, nombre, modelo, serie, fecha y costo de adquisición, garantía, responsable, uso, características, empresa, ubicación, categoría de máquina, si está en operación y estado (activa, inactiva o en mantenimiento). Las categorías de máquinas se administran desde el módulo Máquinas. En la ficha de una máquina están los botones "Uso diario", "Pre operacional" e "Inspección".
- Uso diario: registro por fecha de si una máquina operó (Sí/No), hora de inicio y fin, responsable y observaciones. Solo puede haber un registro por máquina por día.
- Inspecciones: formato de inspección de una máquina con fecha, duración, área, equipo, quién revisó, fecha de revisión, tabla de revisión, observaciones y firma.
- Mediciones de gases: cada medición tiene una cabecera (fecha, hora, firma del jefe y firma de SST) y un detalle por cada ubicación medida (oxígeno, metano, CO2, CO, H2S, NO2, nombre del responsable y observación). En la pantalla de gases están "Nueva Medición", "Comparar mediciones" (compara ubicaciones) y un buscador por código, fecha u hora. Al abrir una medición se ve su formato completo y se puede editar.
- Ubicaciones: lugares de la mina (nombre, descripción, estado y categoría). Las categorías de ubicación se administran desde el módulo Ubicaciones.
- Empresas: empresas dueñas o proveedoras de los activos, con NIT, nombre, teléfono, correo y dirección.
- Alertas: la campana muestra alertas del sistema con nivel danger (grave), warning (importante) o info (informativa). Se pueden marcar como atendidas una por una o todas a la vez. Se generan, por ejemplo, al crear o modificar registros, por stock bajo de herramientas y por gases fuera de rango.
- Perfil: datos del usuario. Los administradores también ven ahí las solicitudes de cuenta pendientes para aprobarlas o rechazarlas.
- Ayuda: preguntas frecuentes y contacto. Acerca de: misión, visión y equipo del proyecto.

# Límites permitidos de gases (configurados en MANTIA)
- Oxígeno: entre {$gas['oxygen']['min']} % y {$gas['oxygen']['max']} %.
- Metano: máximo {$gas['methane']['max']} %.
- Dióxido de carbono (CO2): máximo {$gas['carbon_dioxide']['max']} %.
- Monóxido de carbono (CO): máximo {$gas['carbon_monoxide']['max']} ppm.
- Ácido sulfhídrico (H2S): máximo {$gas['hydrogen_sulfide']['max']} ppm.
- Dióxido de nitrógeno (NO2): máximo {$gas['nitrogen_dioxide']['max']} ppm.
Si un valor está fuera de rango se debe avisar de inmediato al responsable de seguridad (SST) y no ingresar a la zona hasta que se verifique.

# Cuentas y roles
- Registro: en la pantalla de inicio de sesión, enlace de registro. Se pide nombre, apellido, correo, contraseña (mínimo 8 caracteres) y su confirmación, tipo de documento (Cédula de ciudadanía, Cédula de extranjería, Tarjeta de identidad o Pasaporte), número de documento y rol solicitado: Administrador, Invitado o Técnico. Si es Técnico se elige el tipo: Electricista, Minero o Mecánico.
- La cuenta queda pendiente hasta que un Administrador la apruebe desde su Perfil. Mientras esté pendiente o si fue rechazada no se puede iniciar sesión.
- Contraseña olvidada: en el inicio de sesión, enlace "¿No recuerdas tu contraseña? Restablecer". Se escribe el correo, llega un código de 6 dígitos y con ese código se crea la nueva contraseña.
- Roles del sistema: Administrador (gestiona todo y aprueba cuentas), Técnico, Recepcionista e Invitado.

# Cómo hacer tareas comunes
- Registrar algo nuevo (máquina, equipo, herramienta, ubicación, empresa o categoría): entrar al módulo desde el menú lateral y usar el botón de agregar/nuevo de esa pantalla; completar el formulario y guardar. Los campos con * son obligatorios.
- Editar o eliminar: en la lista del módulo, usar los botones de editar o eliminar de cada registro.
- Registrar una medición de gases: abrir un equipo en Equipos, botón "Medición gases", luego "Nueva Medición".
- Registrar uso diario o inspección de una máquina: Máquinas, abrir la ficha de la máquina y usar "Uso diario" o "Inspección".
TXT;
    }
}