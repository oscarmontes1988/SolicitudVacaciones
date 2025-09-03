// --- 1. Configuración y Selectores Comunes ---
// Usamos jQuery, pero la estructura es modular.
const DOM = {
  fechaInicioInput: $("#fecha_inicio_disfrute"),
  fechaFinDisplay: $("#fecha-fin-display"),
  fechaFinHidden: $("#fecha_fin_hidden"),
  fechaFinVacacionesDisplay: $("#fecha-fin-vacaciones-display"), // Add this line
  formSolicitudDirecta: $("#form-solicitud-directa"),
  loginButton: $("#loginButton"),
  loginBtnText: $(".btn-text"),
  loginSpinner: $(".spinner"),
  // Selectores para el dashboard del solicitante que se actualizan dinámicamente
  diasDisponiblesContainer: $(".hero-days"),
  historialTableBody: $(".dashboard-main tbody"),
  periodListContainer: $(".period-list"), // Add this for the periods sidebar
  periodoIdInput: $('input[name="periodo_causacion_id"]'), // Add this for the hidden input
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
  urlEliminarSolicitud: "ajax/eliminar_solicitud.php", // New URL for deleting requests
};

// --- Funciones para el cálculo de festivos en Colombia (Ley Emiliani) ---

/**
 * Calcula la fecha del Domingo de Pascua para un año dado (Algoritmo de Butcher).
 * @param {number} year - El año para el que se desea calcular la Pascua.
 * @returns {Date} La fecha del Domingo de Pascua.
 */
function getEasterSunday(year) {
    const a = year % 19;
    const b = Math.floor(year / 100);
    const c = year % 100;
    const d = Math.floor(b / 4);
    const e = b % 4;
    const f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3);
    const h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4);
    const k = c % 4;
    const l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);
    const month = Math.floor((h + l - 7 * m + 114) / 31);
    const day = ((h + l - 7 * m + 114) % 31) + 1;
    return new Date(year, month - 1, day); // month - 1 porque los meses en Date son 0-indexados
}

/**
 * Aplica la Ley Emiliani (Ley 51 de 1983) para mover festivos al siguiente lunes.
 * @param {Date} date - La fecha original del festivo.
 * @param {boolean} isMovable - Indica si el festivo es de los que se mueven por Ley Emiliani.
 * @returns {Date} La fecha ajustada del festivo.
 */
function applyLeyEmiliani(date, isMovable) {
    if (!isMovable) {
        return date;
    }
    const dayOfWeek = date.getDay(); // 0=Domingo, 1=Lunes, ..., 6=Sábado
    if (dayOfWeek === 1) { // Ya es lunes
        return date;
    }
    // Si es domingo, se mueve al lunes siguiente.
    // Si es cualquier otro día (martes a sábado), se mueve al lunes siguiente.
    const daysUntilMonday = (1 - dayOfWeek + 7) % 7;
    const newDate = new Date(date.getTime());
    newDate.setDate(date.getDate() + daysUntilMonday);
    return newDate;
}

/**
 * Genera la lista de festivos para un año dado, aplicando la Ley Emiliani.
 * @param {number} year - El año para el que se desean generar los festivos.
 * @returns {string[]} Un array de fechas de festivos en formato 'YYYY-MM-DD'.
 */
function generateColombianHolidays(year) {
    const holidays = [];

    // Festivos fijos que NO se mueven por Ley Emiliani
    holidays.push(new Date(year, 0, 1));   // Enero 1 - Año Nuevo
    holidays.push(new Date(year, 4, 1));   // Mayo 1 - Día del Trabajo
    holidays.push(new Date(year, 6, 20));  // Julio 20 - Día de la Independencia
    holidays.push(new Date(year, 7, 7));   // Agosto 7 - Batalla de Boyacá
    holidays.push(new Date(year, 11, 25)); // Diciembre 25 - Navidad

    // Festivos fijos que SÍ se mueven por Ley Emiliani
    holidays.push(applyLeyEmiliani(new Date(year, 0, 6), true));   // Enero 6 - Día de Reyes Magos
    holidays.push(applyLeyEmiliani(new Date(year, 2, 19), true));  // Marzo 19 - Día de San José
    holidays.push(applyLeyEmiliani(new Date(year, 5, 29), true));  // Junio 29 - San Pedro y San Pablo
    holidays.push(applyLeyEmiliani(new Date(year, 7, 15), true));  // Agosto 15 - Asunción de la Virgen
    holidays.push(applyLeyEmiliani(new Date(year, 9, 12), true));  // Octubre 12 - Día de la Raza
    holidays.push(applyLeyEmiliani(new Date(year, 10, 1), true)); // Noviembre 1 - Todos los Santos
    holidays.push(applyLeyEmiliani(new Date(year, 10, 11), true)); // Noviembre 11 - Independencia de Cartagena
    holidays.push(applyLeyEmiliani(new Date(year, 11, 8), true));  // Diciembre 8 - Día de la Inmaculada Concepción

    // Festivos movibles (dependientes de la Pascua)
    const easterSunday = getEasterSunday(year);
    const holyThursday = new Date(easterSunday.getTime());
    holyThursday.setDate(easterSunday.getDate() - 3); // Jueves Santo
    holidays.push(holyThursday);

    const goodFriday = new Date(easterSunday.getTime());
    goodFriday.setDate(easterSunday.getDate() - 2); // Viernes Santo
    holidays.push(goodFriday);

    const ascensionDay = new Date(easterSunday.getTime());
    ascensionDay.setDate(easterSunday.getDate() + 43); // Día de la Ascensión (43 días después de Pascua)
    holidays.push(applyLeyEmiliani(ascensionDay, true));

    const corpusChristi = new Date(easterSunday.getTime());
    corpusChristi.setDate(easterSunday.getDate() + 63); // Corpus Christi (63 días después de Pascua)
    holidays.push(applyLeyEmiliani(corpusChristi, true));

    const sacredHeart = new Date(easterSunday.getTime());
    sacredHeart.setDate(easterSunday.getDate() + 71); // Sagrado Corazón (71 días después de Pascua)
    holidays.push(applyLeyEmiliani(sacredHeart, true));

    // Formatear todas las fechas a 'YYYY-MM-DD' y devolver
    return holidays.map(date => date.toISOString().slice(0, 10));
}

// --- Cache para los festivos generados ---
const HOLIDAYS_CACHE = {};

/**
 * Obtiene la lista de festivos para un año dado, usando caché.
 * @param {number} year - El año.
 * @returns {string[]} Array de festivos en formato 'YYYY-MM-DD'.
 */
function getHolidaysForYear(year) {
    if (!HOLIDAYS_CACHE[year]) {
        HOLIDAYS_CACHE[year] = generateColombianHolidays(year);
    }
    return HOLIDAYS_CACHE[year];
}

/**
 * Genera y cachea los festivos para un rango de años.
 * @param {number} startYear - Año de inicio.
 * @param {number} endYear - Año de fin.
 */
function preGenerateHolidays(startYear, endYear) {
    for (let year = startYear; year <= endYear; year++) {
        getHolidaysForYear(year);
    }
}

// Pre-generar festivos desde el año actual hasta 2060 al cargar la página
const currentYear = new Date().getFullYear();
preGenerateHolidays(currentYear, 2060);

// --- Fin de funciones para el cálculo de festivos ---

// Resto del código de script.js


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
 * Formats a date string (YYYY-MM-DD) to DD/MM/YYYY.
 * @param {string} dateString - The date string to format.
 * @returns {string} The formatted date.
 */
function formatDate(dateString) {
    if (!dateString) return '';
    const parts = dateString.split('-');
    if (parts.length !== 3) return dateString;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

/**
 * Re-renders the list of available periods in the sidebar.
 * @param {Array} periodos - The array of period objects from the server.
 */
function renderPeriodos(periodos) {
    const periodList = DOM.periodListContainer;
    if (!periodList.length) return;

    periodList.empty();
    if (!periodos || periodos.length === 0) {
        periodList.html(`
            <div class="period-card-empty">
                <p>No hay periodos de causación disponibles actualmente.</p>
                <p class="form-help-text">Si crees que esto es un error, por favor contacta a RRHH.</p>
            </div>
        `);
    } else {
        periodos.forEach(periodo => {
            const periodCard = `
                <div class="period-card ${periodo.disponible == 0 ? 'period-card-unavailable' : ''}">
                    <div class="period-card-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="period-card-info">
                        <span class="period-title">Período de Causación</span>
                        <span class="period-dates">${formatDate(periodo.fecha_inicio)} al ${formatDate(periodo.fecha_fin)}</span>
                    </div>
                </div>
            `;
            periodList.append(periodCard);
        });
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

  // 1. Encontrar el último día de vacaciones.
  // El bucle cuenta los días hábiles y avanza la fecha.
  // Se detiene cuando se han contado todos los días de vacaciones.
  while (businessDaysCounted < daysToAdd) {
    let dayOfWeek = currentDate.getDay(); // 0=Domingo, 6=Sábado
    // Normalizamos la fecha a formato YYYY-MM-DD para compararla con los festivos.
    let dateStr = currentDate.toISOString().slice(0, 10);

    // Si no es fin de semana y no es festivo, es un día hábil.
    if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(dateStr)) {
      businessDaysCounted++;
    }

    // Si aún no hemos completado los días, avanzamos al día siguiente.
    if (businessDaysCounted < daysToAdd) {
      currentDate.setDate(currentDate.getDate() + 1);
    }
  }

  const lastDayOfVacation = new Date(currentDate.getTime());

  // 2. Encontrar el día de regreso (el siguiente día hábil).
  let returnDay = new Date(lastDayOfVacation.getTime());
  do {
    returnDay.setDate(returnDay.getDate() + 1);
    let dayOfWeek = returnDay.getDay();
    let dateStr = returnDay.toISOString().slice(0, 10);

    // Si es un día hábil, hemos encontrado la fecha de regreso.
    if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(dateStr)) {
      break;
    }
  } while (true);

  return {
    lastDay: lastDayOfVacation, // El último día que el empleado está de vacaciones.
    returnDay: returnDay,       // El día que el empleado debe regresar a trabajar.
  };
}

// --- 3. Lógica para la Solicitud de Vacaciones ---

/**
 * Inicializa la funcionalidad de cálculo de fecha de regreso de vacaciones.
 */
function initVacationDateCalculator() {
  if (DOM.fechaInicioInput.length) {
    DOM.fechaInicioInput.on("change", function () {
      const startDateVal = $(this).val();
      if (!startDateVal) {
        DOM.fechaFinDisplay.text("--/--/----");
        DOM.fechaFinHidden.val("");
        return;
      }

      // Se añade T00:00:00 para evitar problemas de zona horaria con new Date().
      const startDate = new Date(startDateVal + "T00:00:00");
      if (isNaN(startDate.getTime())) return;

      // Obtener los festivos para el año de la fecha de inicio
      const year = startDate.getFullYear();
      const holidaysForYear = getHolidaysForYear(year);

      // Obtenemos tanto el último día de vacaciones como el día de regreso.
      const { lastDay, returnDay } = calculateVacationPeriod(
        startDate,
        CONFIG.diasPorDefecto,
        holidaysForYear // Usar los festivos generados
      );

      // Formateamos la fecha de REGRESO para MOSTRARLA al usuario.
      const returnDayFormatted = ("0" + returnDay.getDate()).slice(-2);
      const returnMonthFormatted = ("0" + (returnDay.getMonth() + 1)).slice(-2);
      const returnYear = returnDay.getFullYear();
      DOM.fechaFinDisplay.text(`${returnDayFormatted}/${returnMonthFormatted}/${returnYear}`);

      // Formateamos el ÚLTIMO DÍA de disfrute para ENVIARLO al backend.
      const lastDayYear = lastDay.getFullYear();
      const lastDayMonth = ("0" + (lastDay.getMonth() + 1)).slice(-2);
      const lastDayDate = ("0" + lastDay.getDate()).slice(-2);
      DOM.fechaFinHidden.val(`${lastDayYear}-${lastDayMonth}-${lastDayDate}`);

      // Populate the new "Fecha de Fin de Vacaciones" display field
      DOM.fechaFinVacacionesDisplay.val(`${lastDayDate}/${lastDayMonth}/${lastDayYear}`);
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
            // Primero, limpia el formulario.
            DOM.formSolicitudDirecta[0].reset();
            DOM.fechaFinDisplay.text("--/--/----");
            DOM.fechaFinHidden.val("");

            // Luego, actualiza toda la UI con la nueva información.
            DOM.diasDisponiblesContainer.text(response.diasDisponibles);
            renderPeriodos(response.periodos);
            DOM.periodoIdInput.val(response.nuevoPeriodoId); // Actualiza el ID para la siguiente solicitud.

            if (
              DOM.historialTableBody.find(".table-empty-message").length > 0
            ) {
              DOM.historialTableBody.empty();
            }

            // Finalmente, añade la nueva fila al historial.
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
                  <div style="display: flex; gap: 5px; justify-content: center;">
                    <button class="btn btn-success btn-sm btn-ver-detalle" data-solicitud-id="${data.id}">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    ${data.estado === 'Esperando Aprobación Coordinador' ? `
                    <button class="btn btn-danger btn-sm btn-eliminar-solicitud" data-solicitud-id="${data.id}">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                    ` : ''}
                  </div>
                </td>
              </tr>
            `;
            DOM.historialTableBody.prepend(newRow);
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
                    <span class="historial-actor">${evento.nombre_completo_fmt}</span> ${evento.accion_fmt} la solicitud. Estado: <strong>${evento.estado_resultante_fmt}</strong>.
                    ${evento.justificacion !== 'N/A' ? `<div class="historial-justificacion">Justificación: <em>${evento.justificacion}</em></div>` : ''}
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
  initEliminarSolicitud(); // Inicializamos la funcionalidad de eliminar solicitud
});

// --- 6. Lógica para Eliminar Solicitud ---

/**
 * Inicializa la lógica para eliminar una solicitud.
 */
function initEliminarSolicitud() {
  DOM.historialContainer.on("click", ".btn-eliminar-solicitud", function () {
    const solicitudId = $(this).data("solicitud-id");
    const $rowToDelete = $(this).closest("tr"); // Guarda la referencia a la fila

    Swal.fire({
      title: '¿Estás seguro?',
      text: "¡No podrás revertir esto!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: CONFIG.urlEliminarSolicitud,
          type: "POST",
          data: { id: solicitudId },
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              showAlert(response.message, "success");
              $rowToDelete.remove(); // Elimina la fila de la tabla
              DOM.diasDisponiblesContainer.text(response.diasDisponibles);
              renderPeriodos(response.periodos);
              DOM.periodoIdInput.val(response.nuevoPeriodoId); // Update hidden input

              // Opcional: Si la tabla queda vacía, mostrar el mensaje de "No hay solicitudes"
              if (DOM.historialTableBody.children().length === 0) {
                DOM.historialTableBody.html(`
                  <tr>
                    <td colspan="5" class="table-empty-message">No has realizado ninguna solicitud todavía.</td>
                  </tr>
                `);
              }
            } else {
              showAlert(response.message, "error");
            }
          },
          error: function (jqXHR, textStatus, errorThrown) {
            showAlert(
              `Ocurrió un error al intentar eliminar la solicitud. Inténtalo de nuevo.`,
              "error"
            );
            console.error(
              "Error AJAX al eliminar:",
              textStatus,
              errorThrown,
              jqXHR.responseText
            );
          },
        });
      }
    });
  });
}