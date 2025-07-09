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

$(document).ready(function () {
  // --- LÓGICA PARA DASHBOARD DEL SOLICITANTE (MODAL) ---
  const $solicitudModal = $("#solicitud-modal");
  const $btnNuevaSolicitud = $("#btn-nueva-solicitud");
  const $btnCancelarSolicitud = $("#btn-cancelar-solicitud");
  const $closeSolicitudModal = $(".close-modal"); // Corregido de ID a clase

  const closeModal = function () {
    // Resetea el formulario y se asegura que el select esté habilitado para la próxima vez
    // Nota: El formulario se llama #form-nueva-solicitud, no #form-vacaciones
    $("#form-nueva-solicitud")[0].reset();
    $("#modal_periodo_id").prop("disabled", false);
    $solicitudModal.hide();
  };
  if ($solicitudModal.length) {
    // Abre el modal desde el botón principal
    $btnNuevaSolicitud.on("click", function () {
      $("#modal_periodo_id").val(""); // Limpia el periodo por si se abre genéricamente
      $solicitudModal.css("display", "flex"); // Usamos flex para centrar el contenido
    });

    // Abre el modal desde un periodo específico en la barra lateral
    $(".dashboard-sidebar").on("click", ".btn-solicitar-periodo", function () {
      const periodId = $(this).data("periodo-id");
      $("#modal_periodo_id").val(periodId);
      $solicitudModal.css("display", "flex");
    });
    $closeSolicitudModal.on("click", closeModal);
    $btnCancelarSolicitud.on("click", closeModal);
  }
  // --- LÓGICA PARA DASHBOARD DEL APROBADOR (MODAL) ---
  // (El resto del código del aprobador iría aquí)
});
