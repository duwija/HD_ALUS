<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>



  $('#transaction_filter').click(function() 
  {
    $('#table-transaction-list').DataTable().ajax.reload();
  });



  var table = $('#table-transaction-list').DataTable({
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
    "lengthMenu": [[50 ,100, 200, 500, 1000], [50 ,100, 200, 500, 1000]],
    processing: true,
    serverSide: true,
    ajax: {
      url: '/suminvoice/table_transaction_list',
      method: 'POST',

      data: function ( d ) {
       return $.extend( {}, d, {
        "dateStart": $(document.querySelector('[name="dateStart"]')).val(),
        "dateEnd": $(document.querySelector('[name="dateEnd"]')).val(),
        "parameter": $(document.querySelector('[name="parameter"]')).val(),
        "updatedBy": $(document.querySelector('[name="updatedBy"]')).val(),   
        "id_merchant": $(document.querySelector('[name="id_merchant"]')).val(), 
        "kasbank": $(document.querySelector('[name="kasbank"]')).val(),               
      } );
     },
     "dataSrc": function(json) {
    // Variabel untuk menyimpan HTML yang akan ditampilkan di dalam div #groupedTransactionsUser
      var groupedTransactionsUserHTML = '';
      var groupedTransactionsMerchantHTML = '';
      var groupedTransactionsKasbankHTML = '';
      var groupedTransactionsTotal = 0;
      var groupedTransactionsTotalMerchant = 0;
      var groupedTransactionsFee = 0;
      var groupedTransactionsPayment = 0;
      var groupedTransactionsPaymentMerchant = 0;
      var groupedTransactionsPaymentKasbank = 0;
      var groupedTransactionsMerchant = json.groupedTransactionsMerchant;
      var groupedTransactionsUser = json.groupedTransactionsUser;
      var groupedTransactionsKasbank = json.groupedTransactionsKasbank;

      groupedTransactionsMerchant.forEach(function(item, rowindex) {
       if (rowindex < groupedTransactionsMerchant.length) {
      item.client = groupedTransactionsMerchant[rowindex].id_merchant; // Menambahkan nama client ke item
      var merchant = json.merchants.find(m => m.id === groupedTransactionsMerchant[rowindex].id_merchant) || { name: 'No Merchant' };
      var totalPaymentMerchant = Number(groupedTransactionsMerchant[rowindex].total_payment) || 0;
      groupedTransactionsPaymentMerchant += totalPaymentMerchant;
      groupedTransactionsMerchantHTML += '<tr>' +
        '<td>' + (rowindex + 1) + '</td>' + // Nomor urut
        // '<td>' + groupedTransactionsMerchant[rowindex].id_merchant + '</td>' + 
        '<td>' + merchant.name + '</td>' +
        '<td class="text-right"><strong>' + (new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.groupedTransactionsMerchant[rowindex].total_payment)) + '</strong></td>' + // Total amount
        '</tr>';

      } 


    });


      groupedTransactionsKasbank.forEach(function(item, rowindex) {
        if (rowindex < groupedTransactionsKasbank.length) {
        item.kasbank = groupedTransactionsKasbank[rowindex].payment_point; // Menambahkan payment_point ke item
        var akun = (json.kasbanks && json.kasbanks.length) ? 
        json.kasbanks.find(a => a.akun_code === groupedTransactionsKasbank[rowindex].payment_point) : 
        { name: 'No Account' };
        var totalPaymentKasbank = Number(groupedTransactionsKasbank[rowindex].total_payment) || 0;
        groupedTransactionsPaymentMerchant
        groupedTransactionsPaymentKasbank += totalPaymentKasbank;

        groupedTransactionsKasbankHTML += '<tr>' +
            '<td>' + (rowindex + 1) + '</td>' + // Nomor urut
            '<td>' + akun.name + '</td>' + // Nama akun dari tabel `akuns`
            '<td class="text-right"><strong>' + (new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.groupedTransactionsKasbank[rowindex].total_payment)) + '</strong></td>' + // Total amount
            '</tr>';
          }
        });

      groupedTransactionsUser.forEach(function(item, rowindex) {





        if (rowindex < groupedTransactionsUser.length) {
      item.client = groupedTransactionsUser[rowindex].updated_by; // Menambahkan nama client ke item
      var totalAmount = Number(groupedTransactionsUser[rowindex].total_amount) || 0;
      groupedTransactionsTotal += totalAmount;
      var totalFee = Number(groupedTransactionsUser[rowindex].payment_fee) || 0;
      groupedTransactionsFee += totalFee;
      var totalPayment = Number(groupedTransactionsUser[rowindex].total_payment) || 0;
      groupedTransactionsPayment += totalPayment;
      groupedTransactionsUserHTML += '<tr>' +
        '<td>' + (rowindex + 1) + '</td>' + // Nomor urut
        '<td>' + groupedTransactionsUser[rowindex].updated_by + '</td>' + // Nama client
        '<td class="text-right">' + (new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.groupedTransactionsUser[rowindex].total_amount)) + '</td>' + // Total amount
        '<td class="text-right">' + (new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.groupedTransactionsUser[rowindex].payment_fee)) + '</td>' + // 
        '<td class="text-right"><strong>' + (new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.groupedTransactionsUser[rowindex].total_payment)) + '</strong></td>' + // Total amount
        '</tr>';

      } 

    });

  // Tampilkan data client ke dalam div #groupedTransactionsUser

      $('#groupedTransactionsMerchant').html(groupedTransactionsMerchantHTML);
      $('#groupedTransactionsKasbank').html(groupedTransactionsKasbankHTML);
      $('#groupedTransactionsUser').html(groupedTransactionsUserHTML);



      $('#total_paid').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.total));
      $('#fee_counter').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.fee_counter));
      $('#total_payment').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(json.fee_counter+json.total));
      $('#totalAmount').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(groupedTransactionsTotal));
      $('#totalPayment').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(groupedTransactionsPayment));
      $('#totalFee').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(groupedTransactionsFee));
      $('#totalPaymentMerchant').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(groupedTransactionsPaymentMerchant));
      $('#totalPaymentKasbank').text(new Intl.NumberFormat('id-ID', { style: 'decimal', minimumFractionDigits: 0 }).format(groupedTransactionsPaymentKasbank));

      return json.data;
    }
  },

  'columnDefs': [

  {
  "targets": 1, // your case first column
  "className": "text-center",

},
{
  "targets": 2, // your case first column
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
  "className": "text-left",

},
{
  "targets": 8, // your case first columnzZxZ
  "className": "text-right font-weight-bold",

},
{
  "targets": 9, // your case first columnzZxZ
  "className": "text-right font-weight-bold",

},
{
  "targets": 10, // your case first columnzZxZ
  "className": "text-center",

},
{
  "targets": 12, // your case first column
  "className": "text-center",

},
],
  columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: true },
    {data: 'date', name: 'date'},
    {data: 'number', name: 'number'},
    {data: 'cid', name: 'cid'},
    {data: 'name', name: 'name'},
    {data: 'merchant', name: 'merchant'},
    {data: 'address', name: 'address'},
    {data: 'note', name: 'note'},
    {data: 'period', name: 'period'},
    {data: 'total_amount', name: 'total_amount'},
    {data: 'payment_fee', name: 'payment_fee'},
    {data: 'status', name: 'status'},
    {data: 'kasbank', name: 'kasbank'},
    {data: 'updated_by', name: 'updated_by'},
    {data: 'payment_date', name: 'payment_date'},


    ],

});






</script>
<!-- <script >

  $('#groupedTransactionsMerchanTable').DataTable({
    "pageLength": 2,
    "paging": true, // Enable pagination
    "pagingType": "full_numbers", // Optional: Customize pagination controls
  });
</script> -->
<script>
  const dailyData = @json($dailyTransactions);
  const labels = dailyData.map(item => item.date);
  const volumes = dailyData.map(item => item.volume);
  const totals  = dailyData.map(item => item.total_paid);

  new Chart(document.getElementById('dailyTransactionChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
      {
        label: 'Jumlah Transaksi',
        data: volumes,
        backgroundColor: 'rgba(54, 162, 235, 0.6)'
      },
      {
        label: 'Total Pembayaran',
        data: totals,
        type: 'line',
        fill: false,
        borderColor: 'rgba(255, 99, 132, 0.8)'
      }
      ]
    },
    options: {
      scales: {
        x: { title: { display: true, text: 'Tanggal' } },
        y: { title: { display: true, text: 'Nilai' }, beginAtZero: true }
      }
    }
  });
</script>

