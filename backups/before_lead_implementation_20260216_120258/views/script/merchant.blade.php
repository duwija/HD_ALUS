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

          // Menampilkan informasi OLT jika berhasil
					$('#merchant-info').html(`



						<section class="content">
						<div class="container-fluid">
						<div class="row">

						<div class="col-lg-4 col-4">
						<!-- small box -->
						<div class="small-box bg-warning">
						<div class="inner">
						<h4> ${data.count_user_potensial}</h4>
						<p>Potensial</p>
						</div>
						<div class="icon">
						<i class="fas fa-user"></i>
						</div>
						</div>
						</div>
						<!-- ./col -->
						<div class="col-lg-4 col-4">
						<!-- small box -->
						<div class="small-box bg-success">
						<div class="inner">
						<h4> ${data.count_user_active}</h4>
						<p>Active</p>
						</div>
						<div class="icon">
						<i class="fas fa-user"></i>
						</div>
						</div>
						</div>

						<div class="col-lg-4 col-4">
						<!-- small box -->
						<div class="small-box bg-danger">
						<div class="inner">
						<h4> ${data.count_user_block}</h4>
						<p>Blocked</p>
						</div>
						<div class="icon">
						<i class="fas fa-user"></i>
						</div>
						</div>
						</div>

						<div class="col-lg-4 col-4">
						<!-- small box -->
						<div class="small-box bg-secondary">
						<div class="inner">
						<h4> ${data.count_user_inactive}</h4>
						<p>Inactive</p>
						</div>
						<div class="icon">
						<i class="fas fa-user"></i>
						</div>
						</div>
						</div>
						<div class="col-lg-4 col-4">
						<!-- small box -->
						<div class="small-box bg-primary">
						<div class="inner">
						<h4> ${data.count_user_c_properti}</h4>
						<p>Company Property</p>
						</div>
						<div class="icon">
						<i class="fas fa-user"></i>
						</div>
						</div>
						</div>


						</div>
						</div>
						</section


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
			"processing": "<span class='fa-stack fa-lg'>\n\
			<i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
			</span>&emsp;Processing ..."
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
			"processing": "<span class='fa-stack fa-lg'>\n\
			<i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
			</span>&emsp;Processing ..."
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