// (function($) { // Encapsular con jQuery para evitar conflictos de $ si es necesario
//     "use strict"; // Activar modo estricto para mejor manejo de errores

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
  diasVacacionesDefecto: 14, // 14 días a sumar al día de inicio (total 15 días)
  formatoFechaDisplay: "DD/MM/YYYY",
  formatoFechaHidden: "YYYY-MM-DD",
  urlCrearSolicitud: "ajax/crear_solicitud.php",
};

// ----------------------------------------------------
// 2. Funciones Utilitarias (Helpers)
// ----------------------------------------------------

/**
 * Calcula la fecha de fin sumando días hábiles a una fecha de inicio.
 * @param {Date} startDate - La fecha de inicio.
 * @param {number} daysToAdd - El número de días hábiles a sumar.
 * @returns {Date} La fecha de fin calculada.
 */
function calculateEndDate(startDate, daysToAdd) {
  let currentDate = new Date(startDate);
  let businessDaysAdded = 0;

  while (businessDaysAdded < daysToAdd) {
    currentDate.setDate(currentDate.getDate() + 1); // Avanza un día
    let dayOfWeek = currentDate.getDay(); // 0 = Domingo, 6 = Sábado

    // Si no es sábado (6) ni domingo (0), cuenta como día hábil
    if (dayOfWeek !== 0 && dayOfWeek !== 6) {
      businessDaysAdded++;
    }
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
 * Formatea una fecha a un string YYYY-MM-DD.
 * @param {Date} date - La fecha a formatear.
 * @returns {string} Fecha formateada para input hidden.
 */
function formatHiddenDate(date) {
  const day = ("0" + date.getDate()).slice(-2);
  const month = ("0" + (date.getMonth() + 1)).slice(-2);
  const year = date.getFullYear();
  return `${year}-${month}-${day}`;
}

/**
 * Muestra una alerta amigable al usuario.
 * @param {string} message - Mensaje a mostrar.
 * @param {string} type - Tipo de alerta ('success', 'error', 'info').
 */
function showAlert(message, type = "info") {
  // Implementación simple de alerta.
  // En un proyecto real, se usaría un componente UI más sofisticado (toast, modal).
  alert(`[${type.toUpperCase()}] ${message}`);
}

// ----------------------------------------------------
// 3. Lógica para la Solicitud de Vacaciones (Interfaz Solicitante)
// ----------------------------------------------------

/**
 * Inicializa la funcionalidad de cálculo de fecha de fin de vacaciones.
 */
function initVacationDateCalculator() {
  // Solo si el input de fecha de inicio existe en la página
  if (DOM.fechaInicioInput.length) {
    DOM.fechaInicioInput.on("change", function () {
      const startDateVal = $(this).val();

      if (!startDateVal) {
        DOM.fechaFinDisplay.text("--/--/----");
        DOM.fechaFinHidden.val("");
        return;
      }

      // Importante: Tratar la fecha como local para evitar problemas de zona horaria con input type="date"
      const startDate = new Date(startDateVal + "T00:00:00");

      if (isNaN(startDate.getTime())) {
        // Valida que la fecha sea válida
        showAlert("La fecha de inicio seleccionada no es válida.", "error");
        return;
      }

      const endDate = calculateEndDate(startDate, CONFIG.diasVacacionesDefecto);

      DOM.fechaFinDisplay.text(formatDisplayDate(endDate));
      DOM.fechaFinHidden.val(formatHiddenDate(endDate));
    });

    // Disparar el evento change al cargar la página si ya hay una fecha pre-seleccionada
    // Esto asegura que la fecha de fin se calcule al cargar
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
      e.preventDefault(); // Previene el envío por defecto del formulario

      // Deshabilitar botón y mostrar spinner (si aplicable)
      const submitButton = $(this).find('button[type="submit"]');
      const originalButtonText = submitButton.html(); // Guardar el HTML original del botón

      submitButton.prop("disabled", true);
      // Si el botón tiene elementos de spinner/texto, actualiza
      const btnTextSpan = submitButton.find(".btn-text");
      const spinnerSpan = submitButton.find(".spinner");
      if (btnTextSpan.length && spinnerSpan.length) {
        btnTextSpan.hide();
        spinnerSpan.show();
      } else {
        // Fallback si no hay span de texto/spinner: cambiar el texto del botón
        submitButton.text("Enviando...");
      }

      $.ajax({
        url: CONFIG.urlCrearSolicitud,
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          showAlert(response.message, response.status);
          if (response.status === "success") {
            window.location.reload(); // Recargar la página para actualizar el historial y días disponibles
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          showAlert(
            `Ocurrió un error al conectar con el servidor: ${textStatus} - ${errorThrown}. Inténtalo de nuevo.`,
            "error"
          );
          console.error(
            "AJAX Error:",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
        },
        complete: function () {
          // Habilitar botón y ocultar spinner
          submitButton.prop("disabled", false);
          if (btnTextSpan.length && spinnerSpan.length) {
            btnTextSpan.show();
            spinnerSpan.hide();
          } else {
            submitButton.html(originalButtonText); // Restaurar el HTML original
          }
        },
      });
    });
  }
}

// ----------------------------------------------------
// 4. Lógica para el Login (Spinner en botón de login)
// ----------------------------------------------------

/**
 * Inicializa la funcionalidad de spinner en el botón de login al enviar el formulario.
 */
function initLoginSpinner() {
  // Suponiendo que el formulario de login es '.login-form'
  const loginForm = $(".login-form"); // Asegúrate de que esta clase sea correcta

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
  initLoginSpinner(); // Asegúrate de que esta función se llame solo si jQuery se carga antes del script del login

  // Puedes añadir aquí otras funciones de inicialización
  // Por ejemplo, para el dashboard del aprobador si todo el JS está en un solo archivo:
  // initAprovalModalLogic();
});

// })(jQuery); // Cierre de la encapsulación jQuery
