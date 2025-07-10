$(document).ready(function () {
  const $fechaInicioInput = $("#fecha_inicio_disfrute");

  if ($fechaInicioInput.length) {
    $fechaInicioInput.on("change", function () {
      const startDateVal = $(this).val();
      if (!startDateVal) {
        $("#fecha-fin-display").text("--/--/----");
        $("#fecha_fin_hidden").val("");
        return;
      }

      // Evita problemas de zona horaria tratando la fecha como local
      const startDate = new Date(startDateVal + "T00:00:00");
      if (isNaN(startDate.getTime())) return;

      let businessDaysToAdd = 14; // 14 días a sumar al día de inicio para un total de 15
      let currentDate = new Date(startDate);

      while (businessDaysToAdd > 0) {
        currentDate.setDate(currentDate.getDate() + 1);
        let dayOfWeek = currentDate.getDay();
        // 0 = Domingo, 6 = Sábado. Solo contamos días de Lunes a Viernes.
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
          businessDaysToAdd--;
        }
      }

      // Formatear la fecha para mostrar (DD/MM/YYYY)
      const day = ("0" + currentDate.getDate()).slice(-2);
      const month = ("0" + (currentDate.getMonth() + 1)).slice(-2);
      const year = currentDate.getFullYear();
      const displayDate = `${day}/${month}/${year}`;

      // Formatear la fecha para el input hidden (YYYY-MM-DD)
      const hiddenDate = `${year}-${month}-${day}`;

      $("#fecha-fin-display").text(displayDate);
      $("#fecha_fin_hidden").val(hiddenDate);
    });

    $("#form-solicitud-directa").on("submit", function (e) {
      e.preventDefault();
      $.ajax({
        url: "ajax/crear_solicitud.php", // Endpoint para procesar la nueva solicitud
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          alert(response.message);
          if (response.status === "success") {
            window.location.reload(); // Recargar para ver la nueva solicitud en el historial
          }
        },
        error: function () {
          alert(
            "Ocurrió un error al conectar con el servidor. Inténtalo de nuevo."
          );
        },
      });
    });
  }
});
// --- LÓGICA PARA DASHBOARD DEL APROBADOR (MODAL) ---
// (El resto del código del aprobador iría aquí)
