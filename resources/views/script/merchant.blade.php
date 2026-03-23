<script>
	function formatRupiah(angka, prefix) {
		let numberString = angka.toString().replace(/[^,\d]/g, ''),
		split = numberString.split(','),
		sisa = split[0].length % 3,
		rupiah = split[0].substr(0, sisa),
		ribuan = split[0].substr(sisa).match(/\d{3}/gi);

		if (ribuan) {
			let separator = sisa ? '.' : '';
			rupiah += separator + ribuan.join('.');
		}

		rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
		return prefix === undefined ? rupiah : (rupiah ? 'Rp: ' + rupiah : '');
	}



	$(document).ready(function () {
    // Menampilkan spinner saat data sedang dimuat
		$('#spinner').show();


		$.ajax({
			url: '/gettotalakun/{{$merchant->akun_code}}',
			type: 'GET',
			success: function (response) {
				if (response.success) {
                    // Tampilkan hasil total (Debet - Kredit)
					const formattedTotal = formatRupiah(response.total, 'Rp');
					$('#sum_akun').text(`${formattedTotal}`);
				} else {
					$('#sum_akun').text('Rp. 0');
				}
			},
			error: function () {
				$('#sum_akun').text('Rp. 0');
			}
		});

    // Request pertama untuk mendapatkan informasi OLT
		$.ajax({
			url: '/merchant/getmerchantinfo/{{$merchant->id}}',
			type: 'GET',
			success: function (data) {
				$('#spinner').hide();
				if (data.success) {

          // Menampilkan informasi statistik customer
					$('#merchant-info').html(`
						<div class="row">
						  <div class="col-6 col-md-4 mb-2">
						    <div class="d-flex align-items-center p-2 rounded" style="background:#fff3cd;border:1px solid #ffc107">
						      <i class="fas fa-user-clock fa-lg mr-2" style="color:#ffc107"></i>
						      <div>
						        <div class="font-weight-bold" style="font-size:1.1rem">${data.count_user_potensial}</div>
						        <div class="small text-muted">Potensial</div>
						      </div>
						    </div>
						  </div>
						  <div class="col-6 col-md-4 mb-2">
						    <div class="d-flex align-items-center p-2 rounded" style="background:#d4edda;border:1px solid #28a745">
						      <i class="fas fa-user-check fa-lg mr-2" style="color:#28a745"></i>
						      <div>
						        <div class="font-weight-bold" style="font-size:1.1rem">${data.count_user_active}</div>
						        <div class="small text-muted">Active</div>
						      </div>
						    </div>
						  </div>
						  <div class="col-6 col-md-4 mb-2">
						    <div class="d-flex align-items-center p-2 rounded" style="background:#f8d7da;border:1px solid #dc3545">
						      <i class="fas fa-user-slash fa-lg mr-2" style="color:#dc3545"></i>
						      <div>
						        <div class="font-weight-bold" style="font-size:1.1rem">${data.count_user_block}</div>
						        <div class="small text-muted">Blocked</div>
						      </div>
						    </div>
						  </div>
						  <div class="col-6 col-md-4 mb-2">
						    <div class="d-flex align-items-center p-2 rounded" style="background:#e9ecef;border:1px solid #6c757d">
						      <i class="fas fa-user-minus fa-lg mr-2" style="color:#6c757d"></i>
						      <div>
						        <div class="font-weight-bold" style="font-size:1.1rem">${data.count_user_inactive}</div>
						        <div class="small text-muted">Inactive</div>
						      </div>
						    </div>
						  </div>
						  <div class="col-6 col-md-4 mb-2">
						    <div class="d-flex align-items-center p-2 rounded" style="background:#cce5ff;border:1px solid #007bff">
						      <i class="fas fa-building fa-lg mr-2" style="color:#007bff"></i>
						      <div>
						        <div class="font-weight-bold" style="font-size:1.1rem">${data.count_user_c_properti}</div>
						        <div class="small text-muted">Company Property</div>
						      </div>
						    </div>
						  </div>
						</div>
					`);
				} else {
          // Menampilkan pesan error jika tidak berhasil
					$('#disrouter-info').html('<div class="alert alert-danger">' + data.error + '</div>');
				}
			},
			error: function (xhr, status, error) {
				$('#spinner').hide();
				$('#merchant-info').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data.</div>');
			}
		});
	});



//LIst User Merchant

	
	$('#customer_filter').click(function() 
	{
		$('#table-customer').DataTable().ajax.reload()
		$('#table-plan-group').DataTable().ajax.reload()
	});

	var tables = $('#table-customer').DataTable({
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

    }
    ,
    {
      "targets": 8, // your case first columnzZxZ
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
			// {data: 'select', name: 'select'},
			{data: 'invoice', name: 'invoice'},
			// {data: 'action', name: 'action'}


			],

	});

	var tablePlanGroup = $('#table-plan-group').DataTable({
		"responsive": true,
		"autoWidth": false,
		"searching": false,
		"language": {
			"processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
		},
		// dom: 'lBfrtip',
		// buttons: [
		// 	'copy', 'excel', 'pdf', 'csv', 'print'
		// 	],
		// "lengthMenu": [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
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