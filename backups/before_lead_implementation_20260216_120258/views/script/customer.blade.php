
<script>

  var input = document.getElementById("parameter");
  var customerChart = null;
// Execute a function when the user presses a key on the keyboard
  input.addEventListener("keypress", function(event) {
  // If the user presses the "Enter" key on the keyboard
    if (event.key === "Enter") {
    // Cancel the default action, if needed
      event.preventDefault();
    // Trigger the button element with a click


      document.getElementById("customer_filter").click();
    }
  });


  $('#customer_filter').click(function() 
  {
    $('#table-customer').DataTable().ajax.reload()
    $('#table-plan-group').DataTable().ajax.reload()
  });

  var table = $('#table-customer').DataTable({
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
    "lengthMenu": [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
    processing: true,
    serverSide: true,
    pageLength: 50,
    ajax: {
      url: '/customer/table_customer',
      method: 'POST',
        // },
      data: function ( d ) {
       return $.extend( {}, d, {
         "filter": $("#filter").val(),
         "parameter": $("#parameter").val(),
         "id_status": $("#id_status").val(),
         "id_plan": $("#id_plan").val(),  
         "id_merchant": $("#id_merchant").val(),            
       } );
     },

     dataSrc: function(json) {

      let total = json.potensial + json.active + 
      json.inactive + json.block + 
      json.company_Properti + json.unknown;
      
      $('#potensial').text(json.potensial);
      $('#active').text(json.active);
      $('#inactive').text(json.inactive);
      $('#block').text(json.block);
      $('#company_Properti').text(json.company_Properti);

       updateChart(json, total); // Perbarui chart

       return json.data;
     }
   },
   'columnDefs': [
   {
      "targets": 5, // your case first column
      "className": "text-center",

    },
    {
      "targets": 6, // your case first column
      "className": "text-center",

    },
    {
      "targets": 7, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 8, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 9, // your case first columnzZxZ
      "className": "text-center",

    },
    {
      "targets": 10, // your case first columnzZxZ
      "className": "text-center",

    }

    ],
   columns: [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    {data: 'customer_id', name: 'customer_id'},
    {data: 'name', name: 'name'},
    {data: 'address', name: 'address'},
    {data: 'id_merchant', name: 'id_merchant'},
    {data: 'plan', name: 'plan'},
    {data: 'billing_start', name: 'billing_start'},
    {data: 'isolir_date', name: 'isolir_date'},
    {data: 'status_cust', name: 'status_cust'},

    {data: 'invoice', name: 'invoice'},
    {data: 'notification', name: 'notification'},
    {data: 'action', name: 'action'}


    ],

 });

  var tablePlanGroup = $('#table-plan-group').DataTable({
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
     'pageLength',
     ],
    "lengthMenu": [[5, 10, 20, 50, 100], [5, 10, 20, 50, 100]],
    processing: true,
    serverSide: true,
    // pageLength: 50,
    ajax: {
      url: '/customer/table_plan_group',
      method: 'POST',
        // },
      data: function ( d ) {
        return $.extend( {}, d, {
          "filter": $("#filter").val(),
          "parameter": $("#parameter").val(),
          "id_status": $("#id_status").val(),
          "id_plan": $("#id_plan").val(),  
          "id_merchant": $("#id_merchant").val(),            
        } );
      }
    },
    'columnDefs': [
    {
      "targets": 1, // your case first column
      "className": "text-left",

    },
    {
      "targets": 2, // your case first column
      "className": "text-center",

    },

    
    ],
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {data: 'id_plan', name: 'id_plan'},
      {data: 'count', name: 'count'},
      


      ],

  });





</script>
<script>

  function updateChart(data, total) {
    let percentages = {
      potensial: ((data.potensial / total) * 100).toFixed(2),
      active: ((data.active / total) * 100).toFixed(2),
      inactive: ((data.inactive / total) * 100).toFixed(2),
      block: ((data.block / total) * 100).toFixed(2),
      company_Properti: ((data.company_Properti / total) * 100).toFixed(2),
    };

    let ctx = document.getElementById('customerStatusChart').getContext('2d');

    if (window.customerChart) {
        window.customerChart.destroy(); // Hapus chart lama sebelum membuat yang baru
      }

      window.customerChart = new Chart(ctx, {
        type: 'bar',  // Menggunakan chart batang
        data: {
          labels: [
            `Potensial (${percentages.potensial}% | ${data.potensial})`, 
            `Active (${percentages.active}% | ${data.active})`, 
            `Inactive (${percentages.inactive}% | ${data.inactive})`, 
            `Block (${percentages.block}% | ${data.block})`, 
            `C Properti (${percentages.company_Properti}% | ${data.company_Properti})`
            ],
          datasets: [{
            label: 'Jumlah Customer',
            data: [data.potensial, data.active, data.inactive, data.block, data.company_Properti],
            backgroundColor: ['#FFCC00', '#28A745', '#6C757D', '#DC3545', '#007BFF'],
            borderColor: '#ddd',
            borderWidth: 1
          }]
        },
        options: {
           // indexAxis: 'y', // Membuat chart horizontal
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
                    display: false, // Menyembunyikan legend karena label sudah ada
                  },
                  tooltip: {
                    callbacks: {
                      label: function (tooltipItem) {
                        return ` ${tooltipItem.raw} (${percentages[tooltipItem.dataIndex]}%)`;
                      }
                    }
                  }
                },
                scales: {
                  x: {
                    beginAtZero: true
                  },
                  y: {
                    ticks: {
                      font: {
                            size: 14 // Ukuran font label
                          }
                        }
                      }
                    }
                  }
                });
    }


  </script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const dailyNewCustomers = @json($dailyNewCustomers);
    const ncLabels = dailyNewCustomers.map(i => i.date);
    const ncCount = dailyNewCustomers.map(i => i.new_count);

    const ctxNc = document.getElementById('dailyNewCustomersChart').getContext('2d');
    new Chart(ctxNc, {
      type: 'line',
      data: {
        labels: ncLabels,
        datasets: [{
          label: 'New Customers',
          data: ncCount,
          fill: false,
          tension: 0.1
        }]
      },
      options: {
        scales: {
          x: { title: { display: true, text: 'Date' } },
          y: { beginAtZero: true, title: { display: true, text: 'Count' } }
        }
      }
    });
  </script>