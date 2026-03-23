
<script>

  $('#myticket_filter').click(function() {
    $('#table-myticket-list').DataTable().ajax.reload();
  });

  $('#myticket_reset').on('click', function() {
    $('[name="date_from"]').val('{{ date("Y-m-01") }}');
    $('[name="date_end"]').val('{{ date("Y-m-d") }}');
    $('#id_status').val('');
    $('#table-myticket-list').DataTable().ajax.reload();
  });

  var table = $('#table-myticket-list').DataTable({
    "responsive": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
    },
    dom: 'lBfrtip',
    buttons: [
      'copy', 'excel', 'pdf', 'csv', 'print'
    ],
    "lengthMenu": [[200, 500, 1000], [200, 500, 1000]],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/ticket/table_myticket_list',
      method: 'POST',
      data: function(d) {
        return $.extend({}, d, {
          "date_from": $('[name="date_from"]').val(),
          "date_end":  $('[name="date_end"]').val(),
          "id_status": $('[name="id_status"]').val(),
        });
      },
      dataSrc: function(json) {
        $('#total').text(json.total || 0);
        $('#open').text(json.open || 0);
        $('#close').text(json.close || 0);
        $('#inprogress').text(json.inprogress || 0);
        $('#solve').text(json.solve || 0);
        $('#pending').text(json.pending || 0);

        // MTTR
        var mttrVal = parseFloat(json.mttr || 0);
        var $badge  = $('#mttr_badge');
        $('#mttr').text(mttrVal.toFixed(1));
        $('#mttr_count').text(json.mttr_count || 0);
        if (mttrVal === 0 || !json.mttr_count) {
          $badge.attr('class', 'badge badge-light').html('<i class="fas fa-circle mr-1 text-secondary"></i> No Data');
        } else if (mttrVal < 4) {
          $badge.attr('class', 'badge badge-success').html('<i class="fas fa-check-circle mr-1"></i> Avg ' + mttrVal.toFixed(1) + ' h');
        } else if (mttrVal < 8) {
          $badge.attr('class', 'badge badge-warning').html('<i class="fas fa-check-circle mr-1"></i> Avg ' + mttrVal.toFixed(1) + ' h');
        } else {
          $badge.attr('class', 'badge badge-danger').html('<i class="fas fa-exclamation-circle mr-1"></i> Avg ' + mttrVal.toFixed(1) + ' h');
        }
        return json.data;
      }
    },
    'columnDefs': [
      { "targets": 1, "className": "text-center" },
      { "targets": 2, "className": "text-left" },
      { "targets": 3, "className": "text-center" },
      { "targets": 4, "className": "text-left" },
    ],
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'id', name: 'id' },
      { data: 'id_customer', name: 'id_customer' },
      { data: 'status', name: 'status' },
      { data: 'id_categori', name: 'id_categori' },
      { data: 'tittle', name: 'tittle' },
      { data: 'assign_to', name: 'assign_to' },
      { data: 'date', name: 'date' },
      { data: 'mttr', name: 'mttr', orderable: false, searchable: false, defaultContent: '-' },
    ],
  });

</script>


</script>