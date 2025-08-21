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
  // MEJORA: Selectores para el modal de detalle
  detalleModal: $("#detalle-solicitud-modal"),
  detalleModalContent: $("#detalle-solicitud-contenido"),
  historialContainer: $(".dashboard-main"), // Contenedor de la tabla de historial
};

const CONFIG = {
  diasPorDefecto: 15, // Días de vacaciones que se toman
  urlCrearSolicitud: "ajax/guardar_solicitud.php",
  // MEJORA: URL para obtener detalle de solicitud
  urlGetDetalle: "ajax/get_solicitud_detalle.php",
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

            // REFACTORIZACIÓN: Construir el HTML de la nueva fila dinámicamente
            const data = response.newRequestData;
            const newRow = `
              <tr data-id="${data.id}">
                <td>${data.fechaDisfrute}</td>
                <td>${data.fechaCausacion}</td>
                <td>${data.fechaSolicitud}</td>
                <td>
                  <span class="status-badge status-${data.estadoClass}">
                    ${data.estado}
                  </span>
                </td>
                <td>
                  <button class="btn btn-secondary btn-sm btn-ver-detalle" data-solicitud-id="${data.id}">
                    <i class="fas fa-eye"></i> Ver
                  </button>
                </td>
              </tr>
            `;

            DOM.historialTableBody.prepend(newRow);
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

// --- 4. Lógica para el Modal de Detalles (CORRECCIÓN) ---

/**
 * Inicializa la lógica para mostrar el modal con los detalles de la solicitud.
 */
function initVerDetalleModal() {
  if (!DOM.detalleModal.length) return;

  // Usamos delegación de eventos para que funcione con las filas añadidas dinámicamente
  DOM.historialContainer.on("click", ".btn-ver-detalle", function () {
    const solicitudId = $(this).data("solicitud-id");

    // 1. Mostrar modal y estado de carga
    DOM.detalleModal.css("display", "flex");
    DOM.detalleModalContent.html(
      '<div class="spinner-container"><div class="spinner"></div><p>Cargando detalles...</p></div>'
    );

    // 2. Hacer la llamada AJAX para obtener los detalles
    $.ajax({
      url: `${CONFIG.urlGetDetalle}?id=${solicitudId}`,
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const solicitud = response.data;
          // 3. Construir el HTML con los datos recibidos
          let historialHtml = "";
          if (solicitud.historial && solicitud.historial.length > 0) {
            solicitud.historial.forEach((evento) => {
              historialHtml += `
                <div class="historial-item">
                  <div class="historial-fecha">${evento.fecha_accion_fmt}</div>
                  <div class="historial-info">
                    <span class="historial-actor">${evento.nombre_completo_fmt}</span>
                    realizó la acción: <span class="historial-accion">${evento.accion_fmt}</span>.
                    <div class="historial-estado">Estado resultante: <strong>${evento.estado_resultante_fmt}</strong></div>
                    <div class="historial-justificacion">Justificación: <em>${evento.justificacion}</em></div>
                  </div>
                </div>`;
            });
          } else {
            historialHtml = "<p>No hay un historial de acciones para esta solicitud.</p>";
          }

          const contentHtml = `
            <div class="detalle-grid">
              <div><strong>Fecha de Solicitud:</strong> ${solicitud.fecha_solicitud_fmt}</div>
              <div><strong>Estado Actual:</strong> <span class="status-badge status-${solicitud.estado.toLowerCase().replace(/ /g, '-')}">${solicitud.estado}</span></div>
              <div><strong>Periodo de Disfrute:</strong> ${solicitud.periodo_disfrute_fmt}</div>
              <div><strong>Periodo de Causación:</strong> ${solicitud.periodo_causacion_fmt}</div>
            </div>
            <hr>
            <h4>Historial de la Solicitud</h4>
            <div class="historial-timeline">
              ${historialHtml}
            </div>
          `;
          DOM.detalleModalContent.html(contentHtml);
        } else {
          DOM.detalleModalContent.html(
            `<p class="alert alert-danger">Error: ${response.message}</p>`
          );
        }
      },
      error: function () {
        DOM.detalleModalContent.html(
          '<p class="alert alert-danger">No se pudo conectar con el servidor para obtener los detalles.</p>'
        );
      },
    });
  });

  // Lógica para cerrar el modal
  function closeModal() {
    DOM.detalleModal.hide();
  }

  DOM.detalleModal.on("click", ".close-modal, .btn-cancel-modal", closeModal);

  $(window).on("click", function (event) {
    if ($(event.target).is(DOM.detalleModal)) {
      closeModal();
    }
  });
}


// --- 5. Inicialización Global ---
$(document).ready(function () {
  initVacationDateCalculator();
  initVacationFormSubmission();
  initVerDetalleModal(); // Añadimos la inicialización del nuevo modal
});
