// --- 1. Configuración y Selectores Comunes ---
// Usamos jQuery, pero la estructura es modular.
const DOM = {
  fechaInicioInput: $("#fecha_inicio_disfrute"),
  fechaFinDisplay: $("#fecha-fin-display"),
  fechaFinHidden: $("#fecha_fin_hidden"),
  formSolicitudDirecta: $("#form-solicitud-directa"),
  loginButton: $("#loginButton"),
  loginBtnText: $(".btn-text"),
  loginSpinner: $(".spinner"),
  // Selectores para el dashboard del solicitante que se actualizan dinámicamente
  diasDisponiblesContainer: $(".hero-days"),
  historialTableBody: $(".dashboard-main tbody"),
};

const CONFIG = {
  diasPorDefecto: 15, // Días de vacaciones que se toman
  urlCrearSolicitud: "ajax/guardar_solicitud.php",
};

// --- 2. Funciones Utilitarias (Helpers) ---

/**
 * Muestra una notificación. Usa SweetAlert2 si está disponible, de lo contrario, usa un alert nativo.
 * Para una mejor experiencia, añade esta línea a tu footer.php antes de cerrar </body>:
 * <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 * @param {string} message - Mensaje a mostrar.
 * @param {string} type - Tipo de alerta ('success', 'error', 'info', 'warning').
 */
function showAlert(message, type = "info") {
  if (typeof Swal !== "undefined") {
    Swal.fire({
      icon: type,
      title: message,
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
    });
  } else {
    alert(`[${type.toUpperCase()}] ${message}`);
  }
}

/**
 * Calcula la fecha de regreso a partir de una fecha de inicio,
 * un número de días y una lista de festivos.
 * @param {Date} startDate - La fecha de inicio.
 * @param {number} daysToAdd - El número de días hábiles a tomar.
 * @param {string[]} holidays - Array de fechas festivas en formato 'YYYY-MM-DD'.
 * @returns {{lastDay: Date, returnDay: Date}} Un objeto con el último día de vacaciones y el día de regreso.
 */
function calculateVacationPeriod(startDate, daysToAdd, holidays = []) {
  let currentDate = new Date(startDate.getTime());
  let businessDaysCounted = 0;

  // Contamos los días hábiles para encontrar el último día de vacaciones
  while (businessDaysCounted < daysToAdd) {
    let dayOfWeek = currentDate.getDay(); // 0=Domingo, 6=Sábado
    let dateStr = currentDate.toISOString().slice(0, 10);

    if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(dateStr)) {
      businessDaysCounted++;
    }

    // Si aún no hemos completado los días, avanzamos la fecha
    if (businessDaysCounted < daysToAdd) {
      currentDate.setDate(currentDate.getDate() + 1);
    }
  }

  const lastDayOfVacation = new Date(currentDate.getTime());

  // Ahora buscamos el siguiente día hábil para el regreso
  do {
    currentDate.setDate(currentDate.getDate() + 1);
    let dayOfWeek = currentDate.getDay();
    let dateStr = currentDate.toISOString().slice(0, 10);
    if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(dateStr)) {
      return {
        lastDay: lastDayOfVacation, // El último día que está de vacaciones
        returnDay: currentDate, // El día que debe regresar a trabajar
      };
    }
  } while (true);
}

// --- 3. Lógica para la Solicitud de Vacaciones ---

/**
 * Inicializa la funcionalidad de cálculo de fecha de regreso de vacaciones.
 */
function initVacationDateCalculator() {
  if (DOM.fechaInicioInput.length) {
    const festivos_js = JSON.parse(
      DOM.formSolicitudDirecta.attr("data-festivos") || "[]"
    );

    DOM.fechaInicioInput.on("change", function () {
      const startDateVal = $(this).val();
      if (!startDateVal) {
        DOM.fechaFinDisplay.text("--/--/----");
        DOM.fechaFinHidden.val("");
        return;
      }

      const startDate = new Date(startDateVal + "T00:00:00");
      if (isNaN(startDate.getTime())) return;

      const vacation = calculateVacationPeriod(
        startDate,
        CONFIG.diasPorDefecto,
        festivos_js
      );
      const lastDay = vacation.lastDay;

      // Formatear la fecha para mostrarla y enviarla
      const day = ("0" + lastDay.getDate()).slice(-2);
      const month = ("0" + (lastDay.getMonth() + 1)).slice(-2);
      const year = lastDay.getFullYear();

      DOM.fechaFinDisplay.text(`${day}/${month}/${year}`);
      DOM.fechaFinHidden.val(`${year}-${month}-${day}`);
    });
  }
}

/**
 * Inicializa la funcionalidad de envío del formulario de solicitud de vacaciones
 * con actualización dinámica del dashboard.
 */
function initVacationFormSubmission() {
  if (DOM.formSolicitudDirecta.length) {
    DOM.formSolicitudDirecta.on("submit", function (e) {
      e.preventDefault();

      const submitButton = $(this).find('button[type="submit"]');

      if (!DOM.fechaInicioInput.val() || !DOM.fechaFinHidden.val()) {
        showAlert("Por favor, selecciona una fecha de inicio válida.", "error");
        return;
      }

      submitButton
        .prop("disabled", true)
        .html(
          '<span class="spinner" style="display:inline-block; vertical-align:middle; margin-right:5px;"></span> Enviando...'
        );

      $.ajax({
        url: CONFIG.urlCrearSolicitud,
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          showAlert(response.message, response.status);

          if (response.status === "success") {
            DOM.diasDisponiblesContainer.text(response.diasDisponibles);
            if (
              DOM.historialTableBody.find(".table-empty-message").length > 0
            ) {
              DOM.historialTableBody.empty();
            }
            DOM.historialTableBody.prepend(response.newRowHtml);
            DOM.formSolicitudDirecta[0].reset();
            DOM.fechaFinDisplay.text("--/--/----");
            DOM.fechaFinHidden.val("");
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          showAlert(
            `Ocurrió un error de conexión. Inténtalo de nuevo.`,
            "error"
          );
          console.error(
            "Error AJAX:",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
        },
        complete: function () {
          submitButton
            .prop("disabled", false)
            .html('<i class="fas fa-paper-plane"></i> Solicitar');
        },
      });
    });
  }
}

// --- 4. Inicialización Global ---
$(document).ready(function () {
  initVacationDateCalculator();
  initVacationFormSubmission();
});
