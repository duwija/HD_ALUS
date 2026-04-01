



<script>
  function confirmSubmit(event, message) {
    event.preventDefault(); // Cegah pengiriman form secara langsung

    Swal.fire({
      title: 'Are You Sure?',
      text: message,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Sure!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
            // Tampilkan loading custom SweetAlert tanpa tombol
        Swal.fire({
          title: 'Loading...',
          html: '<div class="loading-spinner" style="margin-top: 20px;"><i class="fas fa-spinner fa-spin fa-3x"></i></div>',
          showConfirmButton: false,
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

            // Kirim form setelah loader muncul
        event.target.submit();
      }
    });
  }



  $(document).ready(function () {


    // Menampilkan spinner saat data sedang dimuat
    $('#spinner').show();

    // Request pertama untuk mendapatkan informasi OLT
    $.ajax({
      url: '/olt/getoltinfo/{{$olt->id}}',
      type: 'GET',
      success: function (data) {
        $('#spinner').hide();
        if (data.success) {
          // Menampilkan informasi OLT jika berhasil
          $('#olt-info').html(`
           <p><strong>OLT Name:</strong> ${data.oltInfo.oltName}</p>
           <p><strong>OLT Uptime:</strong> ${data.oltInfo.oltUptime}</p>
           <p><strong>OLT Version:</strong> ${data.oltInfo.oltVersion}</p>
           <p><strong>OLT Description:</strong> ${data.oltInfo.oltDesc}</p>

           <!--  <p><strong>Onu Unconfig :</strong> ${data.oltInfo.onuUnConfg}</p>
           <p><strong>Onu Total Onu:</strong> ${data.oltInfo.onuCount}</p>
           <p><strong>Onu Logging Onu :</strong> ${data.oltInfo.logging}</p>
           <p><strong>Onu Onu Loss :</strong> ${data.oltInfo.los}</p>
           <p><strong>Onu Onu Working :</strong> ${data.oltInfo.working}</p>
           <p><strong>Onu Onu DyingGaps :</strong> ${data.oltInfo.dyinggasp}</p>
           <p><strong>Onu Onu Auth Failed :</strong> ${data.oltInfo.authFailed}</p>
           <p><strong>Onu Onu Failed :</strong> ${data.oltInfo.offline}</p> -->



           <!-- Main content -->
           <section class="content">
           <div class="container-fluid">
           <div class="row">
           <div class="col-lg-2 col-6">
           <!-- small box -->
           <div class="small-box bg-primary">
           <div class="inner">
           <h4>${data.oltInfo.onuCount}</h4>
           <p>Registered Onu</p>
           </div>
           <div class="icon">
           <i class="fas fa-wallet"></i>
           </div>
           </div>
           </div>
           <!-- ./col -->
           <a data-toggle="modal" href="#unconfigonu" class="col-lg-2 col-6"> 

           <div >
           <!-- small box -->
           <div class="small-box bg-secondary">
           <div class="inner">
           <h4>${data.oltInfo.onuUnConfg}</h4>
           <p>Unconfig Onu</p>
           </div>
           <div class="icon">
           <i class="fas fa-university"></i>
           </div>

           </div>
           </div></a>
           <!-- ./col -->
           <div class="col-lg-2 col-6">
           <!-- small box -->
           <div class="small-box bg-success">
           <div class="inner">
           <h4>${data.oltInfo.working}</h4>
           <p>Online Onu</p>
           </div>
           <div class="icon">
           <i class="fas fa-chart-line"></i>
           </div>
           </div>
           </div>
           <!-- ./col -->
           <a data-toggle="modal" href="#loslist" class="col-lg-2 col-6 " >

           <div >
           <!-- small box -->
           <div class="small-box bg-danger">
           <div class="inner">
           <h4>${data.oltInfo.los}</h4>
           <p>Los Onu</p>
           </div>
           <div class="icon">
           <i class="fas fa-chart-bar"></i>
           </div>
           </div>
           </div>
           </a>



           <a data-toggle="modal" href="#dyinggasp" class="col-lg-2 col-6" >

           <div >
           <!-- small box -->
           <div class="small-box bg-warning">
           <div class="inner">
           <h4>${data.oltInfo.dyinggasp}</h4>
           <p>Dyinggasp</p>
           </div>
           <div class="icon">
           <i class="fas fa-chart-simple"></i>
           </div>
           </div>
           </div>
           </a>
           <a data-toggle="modal" href="#offline" class="col-lg-2 col-6" >
           <!-- small box -->
           <div class="small-box bg-info">
           <div class="inner">
           <h4>${data.oltInfo.offline}</h4>
           <p>Offline Onu</p>
           </div>
           <div class="icon">
           <i class="fas fa-chart-line"></i>
           </div>
           </div>
           </div>


           <!-- ./col -->
           </div>
           <!-- /.row -->
           </div><!-- /.container-fluid -->
           </section>









           `);




        } else {
          // Menampilkan pesan error jika tidak berhasil
          $('#olt-info').html('<div class="alert alert-danger">' + data.error + '</div>');
        }


    // Initialize the HTML variable
        let dyinggaspListHtml = '';

// Check if dyinggasplist exists and has elements
        if (data.dyinggasplist && data.dyinggasplist.length > 0) {
          data.dyinggasplist.forEach(function (onu) {
            dyinggaspListHtml += `
            <div class="onu-item">
            <p><strong>ONU Name:</strong> ${onu.onuName}</br><strong>ID:</strong> ${onu.Id.replace(/\\/g, '')}</p>
            </div>
            <hr>
            `;
          });
        } else {
          dyinggaspListHtml += `<p>No ONUs with status 'dyinggasp' found.</p>`;
        }

// Update the HTML content of the element with ID 'dyinggasp_list'
        $('#dyinggasp_list').html(dyinggaspListHtml);




    // Initialize the HTML variable
        let loslistHtml = '';

// Check if loslist exists and has elements
        if (data.loslist && data.loslist.length > 0) {
          data.loslist.forEach(function (onu) {
            loslistHtml += `
            <div class="onu-item">
            <p><strong>ONU Name:</strong> ${onu.onuName}</br>
            <strong>ID:</strong> ${onu.Id.replace(/\\/g, '')}</p>
            </div>
            <hr>
            `;
          });
        } else {
          loslistHtml += `<p>No ONUs with status 'Los' found.</p>`;
        }

// Update the HTML content of the element with ID 'dyinggasp_list'
        $('#los_list').html(loslistHtml);



    // Initialize the HTML variable
        let offlinelistHtml = '';

// Check if offlinelist exists and has elements
        if (data.offlinelist && data.offlinelist.length > 0) {
          data.offlinelist.forEach(function (onu) {
            offlinelistHtml += `
            <div class="onu-item">
            <p><strong>ONU Name:</strong> ${onu.onuName}</br>
            <strong>ID:</strong> ${onu.Id.replace(/\\/g, '')}</p>
            </div>
            <hr>
            `;
          });
        } else {
          offlinelistHtml += `<p>No ONUs with status 'offline' found.</p>`;
        }

// Update the HTML content of the element with ID 'dyinggasp_list'
        $('#offline_list').html(offlinelistHtml);



      },
      error: function (xhr, status, error) {
        $('#spinner').hide();
        $('#olt-info').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data.</div>');
      }
    });


$('#oltPonComboBox').on('change', function () {
    // Ambil nilai dari combobox
  var selectedValue = $(this).val();

    // Set nilai tersebut pada input teks
  $('#oltPonInput').val(selectedValue);
});



    // Request kedua untuk mendapatkan daftar OLT PON dan mengisi combobox
$.ajax({
  url: '/olt/getoltpon/{{$olt->id}}',
  type: 'GET',
  success: function (response) {
    console.log('getOltPon response:', response);
    if (response.data && response.data.length > 0) {
      var selectBox = $('#oltPonComboBox');
      selectBox.empty();
      selectBox.append('<option value="">Pilih OLT PON</option>');
      $.each(response.data, function (index, item) {
        console.log('Adding option:', item.olt_pon, 'suffix:', item.suffix);
        selectBox.append('<option value="' + item.suffix + '">' + item.olt_pon + '</option>');
      });
    } else {
      console.log('No data found in response');
      alert('Data tidak ditemukan');
    }
  },
  error: function (xhr, status, error) {
    console.error('Error loading OLT PON:', error);
    alert('Terjadi kesalahan saat mengambil data');
  }
});

    // Request untuk mendapatkan data ONU saat tombol getOnu diklik



$('#getOnu').click(function() {
  var selectedOltPon = $('#oltPonComboBox').val();
  var oltId = $('#olt_id').val();
  
  console.log('OLT ID:', oltId);
  console.log('Selected PON:', selectedOltPon);
  
  if (selectedOltPon === "") {
    alert('Silakan pilih OLT PON terlebih dahulu.');
    return;
  }

      //$('#spinnerx').show(); // Tampilkan spinner saat data sedang dimuat

  if ($.fn.DataTable.isDataTable('#onu-table')) {
          $('#onu-table').DataTable().clear().destroy(); // Hancurkan tabel dan hapus data sebelumnya
        }

        var table = $('#onu-table').DataTable({
          "responsive": false,
          "autoWidth": false,
          "searching": true,
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
            url: '/olt/getolt/onu',
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: function ( d ) {
             return $.extend( {}, d, {
              "olt_id": $(document.querySelector('[name="olt_id"]')).val(),
              "olt_pon": $(document.querySelector('[name="oltPonComboBox"]')).val(),
              "_token": $('meta[name="csrf-token"]').attr('content')
            } );
           },
           error: function(xhr, error, code) {
             console.log('AJAX Error:', xhr.responseText);
             alert('Error loading ONU data: ' + xhr.responseText);
           }
         },

    //console.log(data),
         'columnDefs': [

         {
          "targets": 1, // your case first column
          "className": "text-center",
      // "render": function (data, type, row) {
      //           return data.replace(/\"/g, ''); // Remove double quotes
      //         }

        },

    //         {
    //   "targets": 2, // your case first column
    //   "className": "text-center",

    // },

        ],
         columns: [
          { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
          {data: 'onuId', name: 'onuId'},
          {data: 'onuSn', name: 'onuSn'},
          {data: 'onuModel', name: 'onuModel'},
          {data: 'name', name: 'name'},
          {data: 'status', name: 'status'},
          {data: 'distance', name: 'distance'},     
          {data: 'onuLastOffline', name: 'onuLastOffline'},
          {data: 'onuLastOnline', name: 'onuLastOnline'},
          {data: 'onuUptime', name: 'onuUptime'},
          {data: 'onuDelete', name: 'onuDelete'},
        // { 
        //   data: 'onuDelete', 
        //   name: 'onuDelete',
        //   orderable: false, 
        //   searchable: false,
        //   render: function(data, type, row, meta) {
        //     return '<button type="button" class="btn btn-danger btn-sm m-1" title="Delete"><i class="fas fa-trash-alt"></i></button><button type="button" class="btn btn-warning btn-sm m-1" title="Reboot"><i class="fas fa-sync-alt"></i></button><button type="button" class="btn btn-info btn-sm m-1" title="Reset Factory Default "><i class="fas fa-redo-alt"></i></button>';
        //   }
        // }

          ],


       });



        $('#spinnerx').hide();
      });



});

















  // var table = $('#onu-table').DataTable({
  //   "responsive": true,
  //   "autoWidth": false,
  //   "searching": false,
  //   "language": {
  //     "processing": "<span class='fa-stack fa-lg'>\n\
  //     <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
  //     </span>&emsp;Processing ..."
  //   },
  //   dom: 'lBfrtip',
  //   buttons: [
  //     'copy', 'excel', 'pdf', 'csv', 'print'
  //     ],
  //   "lengthMenu": [[200, 500, 1000], [200, 500, 1000]],
  //   processing: true,
  //   serverSide: true,
  //   ajax: {
  //     url: '/olt/getoltonu',
  //     method: 'GET',


  //   },

  //   //console.log(data),
  //   'columnDefs': [

  //   {
  //     "targets": 1, // your case first column
  //     "className": "text-center",
  //     // "render": function (data, type, row) {
  //     //           return data.replace(/\"/g, ''); // Remove double quotes
  //     //         }

  //           },
  //   //         {
  //   //   "targets": 2, // your case first column
  //   //   "className": "text-center",

  //   // },

  //           ],
  //   columns: [
  //     { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
  //     {data: 'name', name: 'name'},
  //     {data: 'status', name: 'status'},
  //     {data: 'rx_power', name: 'rx_power'},


  //     ],


  // });







var table = $('#table-onu-unconfig').DataTable({
  "responsive": true,
  "autoWidth": false,
  "searching": false,
  "language": {
    "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
  },
  // dom: 'lBfrtip',
  // buttons: [
  //   'copy', 'excel', 'pdf', 'csv', 'print'
  //   ],
  // "lengthMenu": [[200, 500, 1000], [200, 500, 1000]],
  processing: true,
  serverSide: true,
  ajax: {
    url: '/olt/table_onu_unconfig',
    method: 'POST',
    data: function ( d ) {
     return $.extend( {}, d, {
      "olt" : $("#olt").val(),
      "community": $("#community").val(),

    } );
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
    // {
    //   "targets": 4, // your case first columnzZxZ
    //   "className": "text-center",

    // },
    // {
    //   "targets": 7, // your case first column
    //   "className": "text-center",

    // },

    // {
    //   "targets": 7, // your case first columnzZxZ
    //   "className": "text-center font-weight-bold",

    // },
  ],
 columns: [
  { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
  {data: 'oltName', name: 'oltName'},
  {data: 'oid', name: 'oid'},
  {data: 'identifier', name: 'identifier'},
  {data: 'value', name: 'value'},
  // {data: 'action', name: 'action'},


  ],

});





</script>