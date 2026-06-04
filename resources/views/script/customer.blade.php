
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

  if ($.fn.select2) {
    $('#id_plan').select2({
      width: '100%',
      allowClear: true,
      placeholder: $('#id_plan').data('placeholder') || 'All'
    });
  }


  $('#customer_filter').click(function() 
  {
    $('#table-customer').DataTable().ajax.reload()
    $('#table-plan-group').DataTable().ajax.reload()
  });

  var table = $('#table-customer').DataTable({
    "responsive": false,
    "scrollX": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
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
         "id_tag": $("#id_tag").val(),
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
    {data: 'app_status', name: 'app_status', orderable: false, searchable: false}


    ],

 });

  var tablePlanGroup = $('#table-plan-group').DataTable({
    "responsive": false,
    "scrollX": true,
    "autoWidth": false,
    "searching": false,
    "language": {
      "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
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
    if (typeof Chart === 'undefined') {
      console.warn('Chart.js belum dimuat, chart dilewati.');
      return;
    }
    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#9ba3b2' : '#6b7280';
    const gridColor = isDark ? '#333845' : '#e5e7eb';

    const safeTotal = total > 0 ? total : 1;
    const labels = ['Potensial', 'Active', 'Inactive', 'Block', 'C Properti'];
    const values = [
      Number(data.potensial || 0),
      Number(data.active || 0),
      Number(data.inactive || 0),
      Number(data.block || 0),
      Number(data.company_Properti || 0)
    ];
    const percentages = values.map(function (value) {
      return ((value / safeTotal) * 100).toFixed(2);
    });

    let ctx = document.getElementById('customerStatusChart').getContext('2d');

    if (window.customerChart) {
        window.customerChart.destroy(); // Hapus chart lama sebelum membuat yang baru
      }

      window.customerChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Jumlah Customer',
            data: values,
            backgroundColor: ['#FFCC00', '#28A745', '#6C757D', '#DC3545', '#007BFF'],
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.65,
            categoryPercentage: 0.7
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
                    display: false, // Menyembunyikan legend karena label sudah ada
                  },
                  tooltip: {
                    callbacks: {
                      label: function (tooltipItem) {
                        const index = tooltipItem.dataIndex;
                        return ` ${tooltipItem.raw} pelanggan (${percentages[index]}%)`;
                      }
                    }
                  }
                },
                scales: {
                  x: {
                    grid: {
                      display: false
                    },
                    ticks: {
                      color: textColor,
                      maxRotation: 0,
                      minRotation: 0
                    }
                  },
                  y: {
                    beginAtZero: true,
                    grid: {
                      color: gridColor
                    },
                    ticks: {
                      color: textColor,
                      precision: 0,
                      maxTicksLimit: 8,
                      font: {
                            size: 14 // Ukuran font label
                          },
                      callback: function(value) {
                        return Number(value).toLocaleString('id-ID');
                      }
                        }
                      }
                    }
                  }
                });
    }


  </script>