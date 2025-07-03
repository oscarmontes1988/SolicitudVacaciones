// Archivo: js/script.js
$(document).ready(function() {
    $('#btn-nueva-solicitud').on('click', function() { $('#form-container').slideToggle(); });
    $('#btn-cancelar').on('click', function() { $('#form-container').slideUp(); });
    $('#form-vacaciones').on('submit', function(e) {
        e.preventDefault();
        if (new Date($('#fecha_fin').val()) <= new Date($('#fecha_inicio').val())) {
            alert('La fecha final debe ser posterior a la fecha de inicio.');
            return;
        }
        $.ajax({
            url: 'ajax/guardar_solicitud.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(response) {
                alert(response.message);
                if (response.status === 'success') window.location.reload();
            },
            error: function() { alert('Ocurrió un error de conexión.'); }
        });
    });
    var modal = $('#decision-modal');
    $('.btn-decision').on('click', function() {
        var solicitudId = $(this).closest('tr').data('id');
        var decision = $(this).data('decision');
        $('#modal_solicitud_id').val(solicitudId);
        $('#modal_decision').val(decision);
        $('#modal-title').text('Justificar ' + decision);
        $('#justificacion').val(decision === 'Aprobada' ? 'Cumple con los requisitos.' : '').prop('required', decision === 'Rechazada');
        modal.show();
    });
    $('.close-modal').on('click', function() { modal.hide(); });
    $('#form-decision').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/procesar_aprobacion.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(response) {
                alert(response.message);
                if (response.status === 'success') window.location.reload();
            },
            error: function() { alert('Ocurrió un error de conexión.'); }
        });
    });
});