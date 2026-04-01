
<script>

  //var input = document.getElementById("parameter");

// Execute a function when the user presses a key on the keyboard
  // input.addEventListener("keypress", function(event) {
  // // If the user presses the "Enter" key on the keyboard
  //   if (event.key === "Enter") {
  //   // Cancel the default action, if needed
  //     event.preventDefault();
  //   // Trigger the button element with a click


  //     document.getElementById("invoice_filter").click();
  //   }
  // });
  // $('#updatedBy').hide();
  $('#updatedByLabel').hide();
  $('#paymentStatus').change(function(){
    if($(this).val() == '1'){
      // $('#updatedBy').show();
      $('#updatedByLabel').show();
    } else {
      // $('#updatedBy').hide();
      $('#updatedByLabel').hide();
    }
  });



  $('#invoice_filter').click(function() 
  {
    //document.getElementById("updatedBy").value = "";
    $('#table-invoice-list').DataTable().ajax.reload();

  });

  var table = $('#table-invoice-list').DataTable({
    "responsive": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
    },
    dom: 'Bfrtip',
    buttons: [
     'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
     ],
    "lengthMenu": [[200, 500, 1000], [200, 500, 1000]],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/invoice/table_invoice_list',
      method: 'POST',

      data: function ( d ) {
       return $.extend( {}, d, {
        "dateStart": $(document.querySelector('[name="dateStart"]')).val(),
        "dateEnd": $(document.querySelector('[name="dateEnd"]')).val(),
        "paymentDateStart": $(document.querySelector('[name="paymentDateStart"]')).val(),
        "paymentDateEnd": $(document.querySelector('[name="paymentDateEnd"]')).val(),
        "parameter": $(document.querySelector('[name="parameter"]')).val(),
        "paymentStatus": $(document.querySelector('[name="paymentStatus"]')).val(),
        "id_merchant": $(document.querySelector('[name="id_merchant"]')).val(),
        "updatedBy": $(document.querySelector('[name="updatedBy"]')).val(), 
        "invoicetype": $(document.querySelector('[name="invoicetype"]')).val(),           
      } );
     },

     dataSrc: function(json) {
      var fmt = new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 });
      $('#total').text(fmt.format(json.total));
      $('#total_paid').text(fmt.format(json.total_paid));
      $('#unpaid_payment').text(fmt.format(json.unpaid_payment));
      $('#cancel_payment').text(fmt.format(json.cancel_payment));
      $('#fee_counter').text(json.fee_counter);

      // -- Percentages (by amount) --
      var tot = parseFloat(json.total) || 0;
      var pPaid   = tot > 0 ? Math.round(json.total_paid      / tot * 1000) / 10 : 0;
      var pUnpaid = tot > 0 ? Math.round(json.unpaid_payment  / tot * 1000) / 10 : 0;
      var pCancel = tot > 0 ? Math.round(json.cancel_payment  / tot * 1000) / 10 : 0;

      $('#pct-paid').text(pPaid + '%');
      $('#pct-unpaid').text(pUnpaid + '%');
      $('#pct-cancel').text(pCancel + '%');

      $('#bar-paid').css('width', pPaid + '%').attr('title','Paid '+pPaid+'%');
      $('#bar-unpaid').css('width', pUnpaid + '%').attr('title','Unpaid '+pUnpaid+'%');
      $('#bar-cancel').css('width', pCancel + '%').attr('title','Cancel '+pCancel+'%');

      $('#pct-paid-leg').text(pPaid);
      $('#pct-unpaid-leg').text(pUnpaid);
      $('#pct-cancel-leg').text(pCancel);

      if(tot > 0) $('#inv-progress-wrap').show(); else $('#inv-progress-wrap').hide();

      return json.data;
    }
  },

  'columnDefs': [

  {
      "targets": 1, // your case first column
      "className": "text-center",

    },
    {
      "targets": 3, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 6, // your case first column
      "className": "text-left",

    },
    
    {
      "targets": 7, // your case first columnzZxZ
      "className": "text-center font-weight-bold",

    },
    {
      "targets": 9, // your case first columnzZxZ
      "className": "text-center font-weight-bold",

    },
    {
      "targets": 10, // your case first columnzZxZ
      "className": "text-right font-weight-bold",

    },
    {
      "targets": 11, // your case first columnzZxZ
      "className": "text-center font-weight-bold",

    },
    ],
  columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    {data: 'date', name: 'date'},
    {data: 'number', name: 'number'},
    {data: 'cid', name: 'cid'},
    {data: 'name', name: 'name'},
    {data: 'merchant', name: 'merchant'},
    {data: 'address', name: 'address'},
    {data: 'period', name: 'period'},
    {data: 'due_date', name: 'due_date'},
    {data: 'tax', name: 'tax'},
    {data: 'total_amount', name: 'total_amount'},
    {data: 'status', name: 'status'},
    {data: 'updated_by', name: 'updated_by'},
    {data: 'payment_date', name: 'payment_date'},
    



    ],

});






</script>