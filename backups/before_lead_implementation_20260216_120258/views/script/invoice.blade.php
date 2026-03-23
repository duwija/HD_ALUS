

<script>
 $('#invoice_filter').click(function() {
  $('#table-invoice').DataTable().ajax.reload();
});

 var table = $('#table-invoice').DataTable({
  "responsive": true,
  "autoWidth": false,
  "searching": false,
  "language": {
    "processing": "<span class='fa-stack fa-lg'>\n\
    <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
    </span>&emsp;Processing ..."
  },
  dom: 'Bfrtip',
  buttons: [
    'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
    ],
  "lengthMenu": [[100, 200, 500, 1000], [100, 200, 500, 1000]],
  processing: true,
  serverSide: true,
  ajax: {
    url: '/customer/table_invoice',
    method: 'POST',
    data: function (d) {
      return $.extend({}, d, {
        "search_var": $("#search_var").val(),
        "filter": $("#filter").val(),
        "parameter": $("#parameter").val(),
        "id_status": $("#id_status").val(),
        "id_plan": $("#id_plan").val(),
        "has_invoice": $("#has_invoice").val(),
        "id_merchant": $("#id_merchant").val()
      });
    }
  },

  columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false },
    { data: 'customer_id', name: 'customer_id' },
    { data: 'name', name: 'name' },
    { data: 'address', name: 'address' },
    { data: 'plan', name: 'plan' },
    { data: 'billing_start', name: 'billing_start' },
    { data: 'status_cust', name: 'status_cust' },
    { data: 'invoice', name: 'invoice' }
    ],
  columnDefs: [
    { targets: [5, 6], className: 'text-center' }
    ],
  order: [[1, 'asc']]
});




</script>