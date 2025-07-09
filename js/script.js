// // Archivo: js/script.js
// $(document).ready(function () {
//   $("#btn-nueva-solicitud").on("click", function () {
//     $("#form-container").slideToggle();
//   });
//   $("#btn-cancelar").on("click", function () {
//     $("#form-container").slideUp();
//   });
//   $("#form-vacaciones").on("submit", function (e) {
//     e.preventDefault();
//     if (new Date($("#fecha_fin").val()) <= new Date($("#fecha_inicio").val())) {
//       alert("La fecha final debe ser posterior a la fecha de inicio.");
//       return;
//     }
//     $.ajax({
//       url: "ajax/guardar_solicitud.php",
//       type: "POST",
//       data: $(this).serialize(),
//       dataType: "json",
//       success: function (response) {
//         alert(response.message);
//         if (response.status === "success") window.location.reload();
//       },
//       error: function () {
//         alert("Ocurrió un error de conexión.");
//       },
//     });
//   });
//   var modal = $("#decision-modal");
//   $(".btn-decision").on("click", function () {
//     var solicitudId = $(this).closest("tr").data("id");
//     var decision = $(this).data("decision");
//     $("#modal_solicitud_id").val(solicitudId);
//     $("#modal_decision").val(decision);
//     $("#modal-title").text("Justificar " + decision);
//     $("#justificacion")
//       .val(decision === "Aprobada" ? "Cumple con los requisitos." : "")
//       .prop("required", decision === "Rechazada");
//     modal.show();
//   });
//   $(".close-modal").on("click", function () {
//     modal.hide();
//   });
//   $("#form-decision").on("submit", function (e) {
//     e.preventDefault();
//     $.ajax({
//       url: "ajax/procesar_aprobacion.php",
//       type: "POST",
//       data: $(this).serialize(),
//       dataType: "json",
//       success: function (response) {
//         alert(response.message);
//         if (response.status === "success") window.location.reload();
//       },
//       error: function () {
//         alert("Ocurrió un error de conexión.");
//       },
//     });
//   });
// });

const $btnCancelarSolicitud = $("#btn-cancelar-solicitud");
const $closeSolicitudModal = $("#close-solicitud-modal");

const closeModal = function () {
  // Resetea el formulario y se asegura que el select esté habilitado para la próxima vez
  $("#form-vacaciones")[0].reset();
  $("#periodo_causacion_id").prop("disabled", false);
  $solicitudModal.hide();
};

if ($solicitudModal.length) {
  // Abre el modal de forma genérica (el usuario debe elegir el periodo)
  $btnNuevaSolicitud.on("click", function () {
    $("#periodo_causacion_id").prop("disabled", false).val(""); // Habilita y limpia el select
    $solicitudModal.css("display", "flex"); // Usamos flex para centrar el contenido
  });

  // Abre el modal de forma contextual (el periodo ya viene seleccionado)
  $(".dashboard-sidebar").on("click", ".btn-solicitar-periodo", function () {
    const periodId = $(this).data("periodo-id");
    const $periodoSelect = $("#periodo_causacion_id");

    // Pre-selecciona el periodo en el dropdown y lo deshabilita
    $periodoSelect.val(periodId).prop("disabled", true);

    // Muestra el modal
    $solicitudModal.css("display", "flex");
  });

  $closeSolicitudModal.on("click", closeModal);
  $btnCancelarSolicitud.on("click", closeModal);
}

// --- LÓGICA PARA DASHBOARD DEL APROBADOR (MODAL) ---
