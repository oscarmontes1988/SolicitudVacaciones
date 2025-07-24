// (function($) { // Encapsular con jQuery para evitar conflictos de $ si es necesario
//   "use strict"; // Activar modo estricto para mejor manejo de errores

// ----------------------------------------------------
// 1. Configuración y Selectores Comunes
// ----------------------------------------------------

const DOM = {
  fechaInicioInput: $("#fecha_inicio_disfrute"),
  fechaFinDisplay: $("#fecha-fin-display"),
  fechaFinHidden: $("#fecha_fin_hidden"),
  formSolicitudDirecta: $("#form-solicitud-directa"),
  // Selectores para el botón de login si se usan spinners/deshabilitación
  loginButton: $("#loginButton"),
  loginBtnText: $(".btn-text"),
  loginSpinner: $(".spinner"),
};

const CONFIG = {
  diasVacacionesDefecto: 15,
  formatoFechaDisplay: "DD/MM/YYYY",
  formatoFechaHidden: "YYYY-MM-DD",
  urlCrearSolicitud: "ajax/guardar_solicitud.php",
};

// ----------------------------------------------------
// 2. Funciones Utilitarias (Helpers)
// ----------------------------------------------------

/**
 * Formatea una fecha a un string YYYY-MM-DD para comparaciones.
 * @param {Date} date - La fecha a formatear.
 * @returns {string} Fecha formateada.
 */
function formatISODate(date) {
  const year = date.getFullYear();
  const month = ("0" + (date.getMonth() + 1)).slice(-2);
  const day = ("0" + date.getDate()).slice(-2);
  return `${year}-${month}-${day}`;
}

/**
 * Calcula la fecha de regreso sumando días hábiles, excluyendo festivos.
 * @param {Date} startDate - La fecha de inicio.
 * @param {number} businessDays - El número de días hábiles a tomar (ej. 15).
 * @param {string[]} holidays - Un array de fechas festivas en formato 'YYYY-MM-DD'.
 * @returns {Date} La fecha de regreso.
 */
function calculateReturnDate(startDate, businessDays, holidays) {
  let currentDate = new Date(startDate);
  let daysCounted = 0;

  // Primero, encontrar el último día de vacaciones
  while (daysCounted < businessDays) {
    let dayOfWeek = currentDate.getDay(); // 0=Dom, 1=Lun, ..., 6=Sáb
    let isoDate = formatISODate(currentDate);

    // Si no es domingo (0) ni sábado (6) Y no es un festivo, es un día hábil.
    if (
      dayOfWeek !== 0 &&
      dayOfWeek !== 6 &&
      holidays.indexOf(isoDate) === -1
    ) {
      daysCounted++;
    }

    // Solo avanzar la fecha si aún no hemos encontrado el último día.
    if (daysCounted < businessDays) {
      currentDate.setDate(currentDate.getDate() + 1);
    }
  }

  // Ahora, currentDate contiene el último día de vacaciones.
  // La fecha de regreso es el siguiente día hábil.
  currentDate.setDate(currentDate.getDate() + 1);

  while (true) {
    let returnDayOfWeek = currentDate.getDay();
    let returnIsoDate = formatISODate(currentDate);

    if (
      returnDayOfWeek !== 0 &&
      returnDayOfWeek !== 6 &&
      holidays.indexOf(returnIsoDate) === -1
    ) {
      break; // Es un día hábil, podemos regresar.
    }
    // Si no, avanzamos al siguiente día.
    currentDate.setDate(currentDate.getDate() + 1);
  }

  return currentDate;
}

/**
 * Formatea una fecha a un string DD/MM/YYYY.
 * @param {Date} date - La fecha a formatear.
 * @returns {string} Fecha formateada.
 */
function formatDisplayDate(date) {
  const day = ("0" + date.getDate()).slice(-2);
  const month = ("0" + (date.getMonth() + 1)).slice(-2);
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
}

/**
 * Muestra una alerta amigable al usuario.
 */
function showAlert(message, type = "info") {
  alert(`[${type.toUpperCase()}] ${message}`);
}

// ----------------------------------------------------
// 3. Lógica para la Solicitud de Vacaciones (Interfaz Solicitante)
// ----------------------------------------------------

/**
 * Inicializa la funcionalidad de cálculo de fecha de fin de vacaciones.
 */
function initVacationDateCalculator() {
  if (DOM.fechaInicioInput.length) {
    // CAMBIO: Leer la lista de festivos desde el atributo data-* del formulario.
    // Esto es más robusto que usar una variable global.
    const holidaysData = DOM.formSolicitudDirecta.attr("data-festivos");
    const holidays = holidaysData ? JSON.parse(holidaysData) : [];

    DOM.fechaInicioInput.on("change", function () {
      const startDateVal = $(this).val();

      if (!startDateVal) {
        DOM.fechaFinDisplay.text("--/--/----");
        DOM.fechaFinHidden.val("");
        return;
      }

      const startDate = new Date(startDateVal + "T00:00:00");

      if (isNaN(startDate.getTime())) {
        showAlert("La fecha de inicio seleccionada no es válida.", "error");
        return;
      }

      // La lógica de cálculo ahora usará la lista de festivos leída anteriormente.
      const returnDate = calculateReturnDate(
        startDate,
        CONFIG.diasVacacionesDefecto,
        holidays
      );

      // La fecha final para la base de datos es un día antes de la fecha de regreso.
      let endDate = new Date(returnDate);
      endDate.setDate(endDate.getDate() - 1);

      DOM.fechaFinDisplay.text(formatDisplayDate(returnDate));
      DOM.fechaFinHidden.val(formatISODate(endDate));
    });

    if (DOM.fechaInicioInput.val()) {
      DOM.fechaInicioInput.trigger("change");
    }
  }
}

/**
 * Inicializa la funcionalidad de envío del formulario de solicitud de vacaciones.
 */
function initVacationFormSubmission() {
  if (DOM.formSolicitudDirecta.length) {
    DOM.formSolicitudDirecta.on("submit", function (e) {
      e.preventDefault();

      const submitButton = $(this).find('button[type="submit"]');
      submitButton.prop("disabled", true).text("Enviando...");

      $.ajax({
        url: CONFIG.urlCrearSolicitud,
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          showAlert(response.message, response.status);
          if (response.status === "success") {
            window.location.reload();
          } else {
            submitButton.prop("disabled", false).text("Solicitar");
          }
        },
        error: function () {
          showAlert("Ocurrió un error al conectar con el servidor.", "error");
          submitButton.prop("disabled", false).text("Solicitar");
        },
      });
    });
  }
}

// ----------------------------------------------------
// 4. Lógica para el Login (Spinner en botón de login)
// ----------------------------------------------------

function initLoginSpinner() {
  const loginForm = $(".login-form");

  if (loginForm.length && DOM.loginButton.length) {
    loginForm.on("submit", function () {
      DOM.loginBtnText.hide();
      DOM.loginSpinner.show();
      DOM.loginButton.prop("disabled", true);
    });
  }
}

// ----------------------------------------------------
// 5. Inicialización Global (Ejecutar al cargar el DOM)
// ----------------------------------------------------

$(document).ready(function () {
  initVacationDateCalculator();
  initVacationFormSubmission();
  initLoginSpinner();
});

// })(jQuery); // Cierre de la encapsulación jQuery
