



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
          data.dyinggasplist.forEach(function (onu, i) {
            var idStr = String(onu.Id || '').replace(/\\/g, '');
            var parts = idStr.split(':');
            var pon   = parts[0] || '-';
            var onuId = parts[1] || '-';
            var sn    = onu.sn ? '<code>' + onu.sn + '</code>' : '<span class="text-muted">—</span>';
            var model = onu.model ? onu.model : '<span class="text-muted">—</span>';
            dyinggaspListHtml += '<tr>'
              + '<td>' + (i + 1) + '</td>'
              + '<td>' + (onu.onuName || '') + '</td>'
              + '<td><code>' + pon + '</code></td>'
              + '<td>' + onuId + '</td>'
              + '<td>' + sn + '</td>'
              + '<td>' + model + '</td>'
              + '</tr>';
          });
        } else {
          dyinggaspListHtml = '<tr><td colspan="6" class="text-center text-muted">No ONUs with status \'dyinggasp\' found.</td></tr>';
        }

// Update the HTML content of the element with ID 'dyinggasp_list'
        $('#dyinggasp_list').html(dyinggaspListHtml);





    // LOS ONU table
        let loslistHtml = '';
        if (data.loslist && data.loslist.length > 0) {
          data.loslist.forEach(function (onu, i) {
            var idStr = String(onu.Id || '').replace(/\\/g, '');
            var parts = idStr.split(':');
            var pon   = parts[0] || '-';
            var onuId = parts[1] || '-';
            var sn    = onu.sn ? '<code>' + onu.sn + '</code>' : '<span class="text-muted">—</span>';
            var model = onu.model ? onu.model : '<span class="text-muted">—</span>';
            loslistHtml += '<tr>'
              + '<td>' + (i + 1) + '</td>'
              + '<td>' + (onu.onuName || '') + '</td>'
              + '<td><code>' + pon + '</code></td>'
              + '<td>' + onuId + '</td>'
              + '<td>' + sn + '</td>'
              + '<td>' + model + '</td>'
              + '</tr>';
          });
        } else {
          loslistHtml = '<tr><td colspan="6" class="text-center text-muted">No ONUs with status \'Los\' found.</td></tr>';
        }
        $('#los_list').html(loslistHtml);




    // Offline ONU table
        let offlinelistHtml = '';
        if (data.offlinelist && data.offlinelist.length > 0) {
          data.offlinelist.forEach(function (onu, i) {
            var idStr = String(onu.Id || '').replace(/\\/g, '');
            var parts = idStr.split(':');
            var pon   = parts[0] || '-';
            var onuId = parts[1] || '-';
            var sn    = onu.sn ? '<code>' + onu.sn + '</code>' : '<span class="text-muted">—</span>';
            var model = onu.model ? onu.model : '<span class="text-muted">—</span>';
            offlinelistHtml += '<tr>'
              + '<td>' + (i + 1) + '</td>'
              + '<td>' + (onu.onuName || '') + '</td>'
              + '<td><code>' + pon + '</code></td>'
              + '<td>' + onuId + '</td>'
              + '<td>' + sn + '</td>'
              + '<td>' + model + '</td>'
              + '</tr>';
          });
        } else {
          offlinelistHtml = '<tr><td colspan="6" class="text-center text-muted">No ONUs with status \'offline\' found.</td></tr>';
        }
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

  // ──────────────────────────────────────────────
  // Search ONU by Name/SN across all PONs of this OLT
  // (Backend: POST /olt/onu-search, supports ZTE C600/C620/C650)
  // ──────────────────────────────────────────────
  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function runOnuSearch() {
    var q = ($('#onuSearchInput').val() || '').trim();
    var oltId = $('#olt_id').val();

    if (q.length < 2) {
      Swal.fire({ icon: 'warning', title: 'Keyword minimal 2 karakter', timer: 1800, showConfirmButton: false });
      return;
    }

    var $btn  = $('#btnSearchOnu');
    var $meta = $('#onuSearchResultMeta');
    var $body = $('#onu-search-table tbody');

    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Searching…');
    $('#onuSearchEmpty').hide();
    $('#onuSearchResult').show();
    $meta.text('Walking SNMP…');
    $body.html('<tr><td colspan="8" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Loading…</td></tr>');

    $.ajax({
      url: '/olt/onu-search',
      type: 'POST',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      data: { olt_id: oltId, q: q },
      dataType: 'json'
    })
    .done(function(res) {
      if (!res.success) {
        $meta.text('');
        $body.html('<tr><td colspan="8" class="text-center text-danger">' + escapeHtml(res.message || 'Search gagal') + '</td></tr>');
        return;
      }
      var rows = res.data || [];
      $meta.text(res.message || (rows.length + ' result'));
      if (rows.length === 0) {
        $body.html('<tr><td colspan="8" class="text-center text-muted">Tidak ada ONU yang cocok.</td></tr>');
        return;
      }
      var html = '';
      rows.forEach(function(r, i) {
        var custCell = r.customer
          ? (r.customer_id
              ? '<a href="/customer/' + encodeURIComponent(r.customer_id) + '">' + escapeHtml(r.customer) + '</a>'
              : escapeHtml(r.customer))
          : '<span class="text-muted">—</span>';

        // Build status cell — when working, append RX/TX badges (color-coded by RX threshold).
        var statusLower = (r.status || '').toLowerCase();
        var isWorking = statusLower.indexOf('working') !== -1 || statusLower.indexOf('online') !== -1;
        var statusBadge = isWorking
          ? '<span class="badge badge-success">' + escapeHtml(r.status) + '</span>'
          : '<span class="badge badge-secondary">' + escapeHtml(r.status) + '</span>';
        var rxTxHtml = '';
        if (isWorking && (r.rx_dbm != null || r.tx_dbm != null)) {
          var rxCls = 'badge-success';
          if (r.rx_dbm != null) {
            if (r.rx_dbm <= -27) rxCls = 'badge-danger';
            else if (r.rx_dbm <= -25) rxCls = 'badge-warning';
          }
          var rxTxt = (r.rx_dbm != null) ? (r.rx_dbm.toFixed(2) + ' dBm') : 'n/a';
          var txTxt = (r.tx_dbm != null) ? (r.tx_dbm.toFixed(2) + ' dBm') : 'n/a';
          rxTxHtml = '<div class="small mt-1">'
                   +   '<span class="badge ' + rxCls + '" title="RX Power">RX ' + rxTxt + '</span> '
                   +   '<span class="badge badge-info" title="TX Power">TX ' + txTxt + '</span>'
                   + '</div>';
        }

        html += '<tr>'
              +   '<td>' + (i + 1) + '</td>'
              +   '<td><code>' + escapeHtml(r.pon) + '</code></td>'
              +   '<td>' + escapeHtml(r.onu_id) + '</td>'
              +   '<td><code>' + escapeHtml(r.sn) + '</code></td>'
              +   '<td>' + escapeHtml(r.name) + '</td>'
              +   '<td>' + statusBadge + rxTxHtml + '</td>'
              +   '<td>' + custCell + '</td>'
              +   '<td>'
              +     '<button type="button" class="btn btn-xs btn-primary onu-jump-pon" '
              +       'data-pon="' + escapeHtml(r.pon) + '" title="Tampilkan PON ini">'
              +       '<i class="fas fa-eye"></i> Show PON'
              +     '</button>'
              +   '</td>'
              + '</tr>';
      });
      $body.html(html);
    })
    .fail(function(xhr) {
      var msg = 'HTTP ' + xhr.status;
      try { msg = (JSON.parse(xhr.responseText).message) || msg; } catch (e) {}
      $meta.text('');
      $body.html('<tr><td colspan="8" class="text-center text-danger">' + escapeHtml(msg) + '</td></tr>');
    })
    .always(function() {
      $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Search');
      $('#btnClearSearchOnu').show();
    });
  }

  $(document).on('click', '#btnSearchOnu', runOnuSearch);

  $(document).on('keypress', '#onuSearchInput', function(e) {
    if (e.which === 13) { e.preventDefault(); runOnuSearch(); }
  });

  $(document).on('click', '#btnClearSearchOnu', function() {
    $('#onuSearchInput').val('');
    $('#onuSearchResult').hide();
    $('#onu-search-table tbody').empty();
    $('#onuSearchResultMeta').text('');
    $('#onuSearchEmpty').show();
    $(this).hide();
  });

  // Resize chart when Distance Map tab becomes visible (canvas needs reflow after hidden init)
  $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
    if (e.target && e.target.id === 'tab-distmap-tab' && typeof distanceMapChart !== 'undefined' && distanceMapChart) {
      try { distanceMapChart.resize(); distanceMapChart.update('none'); } catch (err) {}
    }
  });

  // Quick jump: when "Show PON" clicked, set dropdown & trigger existing Show
  $(document).on('click', '.onu-jump-pon', function() {
    var pon = String($(this).data('pon'));
    var $sel = $('#oltPonComboBox');

    // Find option by display text (e.g. "1/2/1"); the option value is the
    // encoded suffix (e.g. "268566784" for C300) which differs from the PON label.
    var $opt = $sel.find('option').filter(function() {
      return $.trim($(this).text()) === pon;
    }).first();

    if ($opt.length === 0) {
      alert('PON ' + pon + ' belum tersedia di dropdown. Mohon tunggu data PON selesai dimuat lalu coba lagi.');
      return;
    }

    $sel.val($opt.val()).trigger('change');
    $('#getOnu').click();
    // Scroll to ONU table
    if ($('#onu-table').length) {
      $('html, body').animate({ scrollTop: $('#onu-table').offset().top - 80 }, 400);
    }
  });

  // ───────────────────────── Health Dashboard: Top RX Worst ─────────────────────────
  function rxBadge(rx) {
    var val = rx.toFixed(2);
    if (rx <= -27) return '<span class="badge badge-danger">' + val + ' dBm</span>';
    if (rx <= -25) return '<span class="badge badge-warning">' + val + ' dBm</span>';
    return '<span class="badge badge-success">' + val + ' dBm</span>';
  }

  function loadRxHealth() {
    var oltId = $('#olt_id').val();
    var $tbody = $('#rx-health-table tbody');
    var $meta  = $('#rxHealthMeta');
    var $btn   = $('#btnRefreshRxHealth');

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading…');
    $('#rxHealthSpinner').show();
    $tbody.html('');
    $meta.text('');

    $.ajax({
      url: '/olt/health/top-rx/' + oltId + '?limit=10',
      type: 'GET',
      timeout: 120000
    })
    .done(function(res) {
      $('#rxHealthSpinner').hide();
      if (!res.success) {
        $tbody.html('<tr><td colspan="8" class="text-center text-danger">'
          + (res.message || 'Gagal memuat') + '</td></tr>');
        return;
      }
      var rows = res.data || [];
      if (rows.length === 0) {
        $tbody.html('<tr><td colspan="8" class="text-center text-muted">'
          + 'Tidak ada data RX power yang valid.</td></tr>');
        $meta.text('Scanned ' + (res.total_scanned || 0) + ' ONU · ' + (res.generated_at || ''));
        return;
      }
      var html = '';
      rows.forEach(function(r, i) {
        var custCell = r.customer
          ? (r.customer_id
              ? '<a href="/customer/' + encodeURIComponent(r.customer_id) + '">' + escapeHtml(r.customer) + '</a>'
              : escapeHtml(r.customer))
          : '<span class="text-muted">—</span>';
        html += '<tr>'
              +   '<td>' + (i + 1) + '</td>'
              +   '<td>' + rxBadge(r.rx_dbm) + '</td>'
              +   '<td><code>' + escapeHtml(r.pon) + '</code></td>'
              +   '<td>' + escapeHtml(String(r.onu_id)) + '</td>'
              +   '<td><code>' + escapeHtml(r.sn || '') + '</code></td>'
              +   '<td>' + escapeHtml(r.name || '') + '</td>'
              +   '<td>' + custCell + '</td>'
              +   '<td>'
              +     '<button type="button" class="btn btn-xs btn-primary onu-jump-pon" '
              +       'data-pon="' + escapeHtml(r.pon) + '" title="Tampilkan PON ini">'
              +       '<i class="fas fa-eye"></i> PON'
              +     '</button>'
              +   '</td>'
              + '</tr>';
      });
      $tbody.html(html);
      $meta.text('Scanned ' + res.total_scanned + ' ONU · ' + res.generated_at);
    })
    .fail(function(xhr) {
      $('#rxHealthSpinner').hide();
      var msg = 'HTTP ' + xhr.status;
      try { msg = (JSON.parse(xhr.responseText).message) || msg; } catch (e) {}
      $tbody.html('<tr><td colspan="8" class="text-center text-danger">' + escapeHtml(msg) + '</td></tr>');
    })
    .always(function() {
      $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh');
    });
  }

  $(document).on('click', '#btnRefreshRxHealth', loadRxHealth);

  // ============================================================
  // ONU Distance Map — scatter chart distance vs RX power
  // ============================================================
  var distanceMapChart = null;
  var distMapSelected  = null; // last clicked ONU point

  function ensureChartJs(cb) {
    if (window.Chart) { cb(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
    s.onload = cb;
    s.onerror = function() { alert('Gagal memuat Chart.js dari CDN.'); };
    document.head.appendChild(s);
  }

  function colorFor(rx) {
    if (rx <= -27) return '#ff6b6b';
    if (rx <= -25) return '#ffd43b';
    return '#51cf66';
  }

  function isDarkMode() {
    return document.body.classList.contains('dark-mode');
  }

  // Return color set for chart based on current theme
  function dmThemeColors() {
    if (isDarkMode()) {
      return { text:'#e4e6eb', muted:'#8a8d91', grid:'#3a3b3c' };
    }
    return { text:'#212529', muted:'#6c757d', grid:'#dee2e6' };
  }

  function applyChartTheme() {
    if (!distanceMapChart) return;
    var t = dmThemeColors();
    var opts = distanceMapChart.options;
    if (opts.plugins && opts.plugins.legend && opts.plugins.legend.labels) {
      opts.plugins.legend.labels.color = t.text;
    }
    ['x','y'].forEach(function(k){
      if (!opts.scales || !opts.scales[k]) return;
      opts.scales[k].title.color = t.text;
      opts.scales[k].grid.color  = t.grid;
      opts.scales[k].ticks.color = t.muted;
    });
    distanceMapChart.update('none');
  }

  function linearRegression(points) {
    var n = points.length;
    if (n < 2) return null;
    var sx = 0, sy = 0, sxy = 0, sxx = 0;
    for (var i = 0; i < n; i++) {
      sx  += points[i].x;
      sy  += points[i].y;
      sxy += points[i].x * points[i].y;
      sxx += points[i].x * points[i].x;
    }
    var denom = (n * sxx - sx * sx);
    if (denom === 0) return null;
    var m = (n * sxy - sx * sy) / denom;
    var b = (sy - m * sx) / n;
    return { m: m, b: b };
  }

  function loadDistanceMap() {
    var oltId  = $('#olt_id').val();
    if (!oltId) { alert('OLT ID tidak ditemukan.'); return; }
    var $btn   = $('#btnRefreshDistMap');
    var $meta  = $('#distMapMeta');
    var $spin  = $('#distMapSpinner');
    var $stats = $('#distMapStats');
    var $empty = $('#distMapEmpty');
    var $box   = $('#distMapChartBox');
    var $ins   = $('#distMapInsights');

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memuat...');
    $spin.show();
    $empty.hide();
    $box.hide();
    $ins.hide();
    $meta.text('');

    ensureChartJs(function() {
      $.ajax({
        url: '/olt/health/distance-map/' + oltId,
        method: 'GET',
        dataType: 'json',
        timeout: 180000
      })
      .done(function(res) {
        if (!res || !res.success) {
          $empty.show().html('<span style="color:#ff6b6b;">' + escapeHtml((res && res.message) || 'Gagal memuat data.') + '</span>');
          return;
        }
        var data = res.data || [];
        if (!data.length) {
          $empty.show().text('Tidak ada ONU dengan data distance + RX valid.');
          return;
        }

        // Stats
        $stats.css('display','grid');
        $('#dmsTotal').text(res.total);
        $('#dmsAvgRx').text(res.avg_rx != null ? res.avg_rx : '—');
        $('#dmsAvgDist').text(res.avg_dist != null
            ? (res.avg_dist >= 1000 ? (res.avg_dist/1000).toFixed(1) + ' km' : res.avg_dist + ' m')
            : '—');
        $('#dmsHealthy').text(res.counts.healthy);
        $('#dmsWarn').text(res.counts.warning);
        $('#dmsCrit').text(res.counts.critical);
        $meta.text('Updated: ' + (res.generated_at || ''));

        // Points
        var points = data.map(function(d){
          return {
            x: d.distance_m, y: d.rx_dbm,
            pon: d.pon, onu_id: d.onu_id,
            name: d.name || ('onu_'+d.onu_id),
            sn: d.sn || '-', customer: d.customer || ''
          };
        });
        var colors = points.map(function(p){ return colorFor(p.y); });

        // Trend
        var reg = linearRegression(points);
        var trendData = [];
        if (reg) {
          var xs = points.map(function(p){return p.x;});
          var minX = Math.min.apply(null, xs);
          var maxX = Math.max.apply(null, xs);
          trendData = [
            { x: minX, y: reg.m * minX + reg.b },
            { x: maxX, y: reg.m * maxX + reg.b }
          ];
        }

        // Build chart
        if (distanceMapChart) { distanceMapChart.destroy(); distanceMapChart = null; }
        $box.show();
        var ctx = document.getElementById('distanceMapChart').getContext('2d');
        distanceMapChart = new Chart(ctx, {
          type: 'scatter',
          data: {
            datasets: [
              {
                label: 'ONU',
                data: points,
                pointBackgroundColor: colors,
                pointBorderColor: colors,
                pointRadius: 5,
                pointHoverRadius: 8
              },
              {
                label: 'Trend (regresi linear)',
                type: 'line',
                data: trendData,
                borderColor: '#4dabf7',
                borderWidth: 2,
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
                tension: 0
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { labels: { color: dmThemeColors().text } },
              tooltip: {
                callbacks: {
                  label: function(ctx) {
                    if (ctx.datasetIndex === 1) return null;
                    var d = ctx.raw;
                    return [
                      'Customer: ' + (d.customer || d.name),
                      'PON: ' + d.pon + '  ONU#' + d.onu_id,
                      'SN: ' + d.sn,
                      'Distance: ' + Math.round(d.x) + ' m',
                      'RX: ' + d.y.toFixed(2) + ' dBm'
                    ];
                  }
                }
              }
            },
            scales: {
              x: {
                title: { display: true, text: 'Distance from OLT (meter)', color: dmThemeColors().text },
                grid:  { color: dmThemeColors().grid },
                ticks: { color: dmThemeColors().muted, callback: function(v){ return v + ' m'; } }
              },
              y: {
                title: { display: true, text: 'RX Power (dBm)', color: dmThemeColors().text },
                grid:  { color: dmThemeColors().grid },
                ticks: { color: dmThemeColors().muted, callback: function(v){ return v + ' dBm'; } },
                suggestedMin: -32,
                suggestedMax: -16
              }
            },
            onClick: function(evt, elements, chart) {
              // Support both Chart.js v2 (el._index/_datasetIndex) and v3/v4 (el.index/datasetIndex)
              var c = chart || distanceMapChart;
              var els = elements && elements.length ? elements : [];
              if (!els.length && c.getElementsAtEventForMode) {
                try {
                  els = c.getElementsAtEventForMode(evt, 'nearest', { intersect: false }, true);
                } catch (e) {}
              }
              if (!els.length && c.getElementsAtEvent) {
                try { els = c.getElementsAtEvent(evt); } catch (e) {}
              }
              if (!els.length) return;
              var el  = els[0];
              var di  = (el.datasetIndex != null) ? el.datasetIndex : el._datasetIndex;
              var idx = (el.index != null)        ? el.index        : el._index;
              if (di !== 0) return;
              var p = c.data.datasets[0].data[idx];
              if (!p) return;
              showOnuInfo(p);
            }
          }
        });

        // Auto-insights
        renderDistanceInsights(points, reg);
      })
      .fail(function(xhr) {
        var msg = 'HTTP ' + xhr.status;
        try { msg = (JSON.parse(xhr.responseText).message) || msg; } catch (e) {}
        $empty.show().html('<span style="color:#ff6b6b;">' + escapeHtml(msg) + '</span>');
      })
      .always(function() {
        $spin.hide();
        $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh');
      });
    });
  }

  // Generate auto-insights based on PON anomalies, extreme outliers, LOS cluster, short-distance bad
  function renderDistanceInsights(points, reg) {
    var items = [];

    // 1) PON-level anomaly: groups with >=3 ONU and avg residual <= -2 dB
    if (reg) {
      var byPon = {};
      points.forEach(function(p){
        var expected = reg.m * p.x + reg.b;
        var resid = p.y - expected;
        if (!byPon[p.pon]) byPon[p.pon] = { sum:0, n:0, bad:0 };
        byPon[p.pon].sum += resid;
        byPon[p.pon].n   += 1;
        if (resid <= -2) byPon[p.pon].bad += 1;
      });
      Object.keys(byPon).forEach(function(pon){
        var g = byPon[pon];
        if (g.n >= 3 && (g.sum / g.n) <= -2) {
          var avg = (g.sum / g.n).toFixed(1);
          items.push('<span class="dm-anomaly">PON ' + escapeHtml(pon) + '</span> &mdash; ' +
            g.n + ' ONU rata-rata <strong>' + avg + ' dB</strong> di bawah trend line ' +
            '→ kemungkinan <strong>splitter rusak / connector kotor di feeder</strong>.');
        }
      });
    }

    // 2) Extreme individual outliers: residual <= -4 dB
    if (reg) {
      var outliers = points.map(function(p){
        var expected = reg.m * p.x + reg.b;
        return { p: p, resid: p.y - expected, expected: expected };
      }).filter(function(o){ return o.resid <= -4; })
        .sort(function(a,b){ return a.resid - b.resid; })
        .slice(0, 3);
      outliers.forEach(function(o){
        items.push('<span class="dm-anomaly">' + escapeHtml(o.p.customer || o.p.name) + '</span> ' +
          '@ ' + Math.round(o.p.x) + 'm, RX <strong>' + o.p.y.toFixed(2) + ' dBm</strong> &mdash; ' +
          'anomali ekstrem, seharusnya ~' + o.expected.toFixed(1) + ' dBm. ' +
          '<strong>Cek dropcore + connector ONU.</strong>');
      });
    }

    // 3) LOS cluster: ONU > 4km and RX < -26
    var losCluster = points.filter(function(p){ return p.x > 4000 && p.y < -26; });
    if (losCluster.length >= 3) {
      items.push(losCluster.length + ' ONU di jarak &gt;4km mendekati batas LOS &mdash; ' +
        'pertimbangkan <strong>menambah splitter level kedua</strong> atau pindah ke port PON dengan TX lebih tinggi.');
    }

    // 4) Short-distance bad: < 1km and RX < -23
    var shortBad = points.filter(function(p){ return p.x < 1000 && p.y < -23; });
    if (shortBad.length >= 2) {
      items.push(shortBad.length + ' ONU di jarak &lt;1km dengan RX &lt; -23 dBm (seharusnya &gt; -20 dBm) ' +
        '→ kemungkinan <strong>ONU lama / dropcore aging / connector kotor</strong>.');
    }

    var $ins = $('#distMapInsights');
    var $list = $('#distMapInsightsList');
    if (items.length === 0) {
      $list.html('<li><span class="dm-ok">Tidak ada anomali signifikan terdeteksi.</span> ' +
        'Seluruh ONU berada dalam rentang RX normal terhadap jarak.</li>');
    } else {
      $list.html(items.map(function(it){ return '<li>' + it + '</li>'; }).join(''));
    }
    $ins.show();
  }

  $(document).on('click', '#btnRefreshDistMap', loadDistanceMap);

  // Show ONU details panel (Name / SN / PON / RX) when a point is clicked
  function showOnuInfo(p) {
    distMapSelected = p;
    var sev = (p.y <= -27) ? 'Kritis' : (p.y <= -25) ? 'Warning' : 'Sehat';
    var sevColor = colorFor(p.y);
    $('#dmInfoTitle').html('Detail ONU — <span style="color:' + sevColor + ';">' + escapeHtml(sev) + '</span>');
    $('#dmInfoName').text(p.name || '-');
    $('#dmInfoSn').text(p.sn || '-');
    $('#dmInfoPon').text(p.pon + '  /  ONU #' + p.onu_id);
    $('#dmInfoRx').html('<span style="color:' + sevColor + ';">' + p.y.toFixed(2) + ' dBm</span>');
    $('#dmInfoDist').text(Math.round(p.x) + ' m'
      + (p.x >= 1000 ? '  (' + (p.x/1000).toFixed(2) + ' km)' : ''));
    $('#dmInfoCustomer').text(p.customer || '—');
    $('#distMapOnuInfo').show();
    // Scroll info into view smoothly
    var top = $('#distMapOnuInfo').offset().top - 80;
    if (top > 0) $('html, body').animate({ scrollTop: top }, 300);
  }

  function jumpToPon(pon) {
    if (!pon) return;
    var $sel = $('#oltPonComboBox');
    var matched = null;
    $sel.find('option').each(function(){
      if ($(this).text().indexOf(pon) !== -1) { matched = $(this).val(); return false; }
    });
    if (matched !== null) {
      $sel.val(matched).trigger('change');
      $('#getOnu').trigger('click');
      $('html, body').animate({ scrollTop: $('#getOnu').offset().top - 80 }, 400);
    }
  }

  $(document).on('click', '#dmInfoClose', function(){
    $('#distMapOnuInfo').hide();
    distMapSelected = null;
  });
  $(document).on('click', '#dmInfoJump', function(){
    if (distMapSelected) jumpToPon(distMapSelected.pon);
  });

  // Re-tint chart when dark-mode class on body toggles
  if (window.MutationObserver) {
    var dmObs = new MutationObserver(function(){ applyChartTheme(); });
    dmObs.observe(document.body, { attributes:true, attributeFilter:['class'] });
  }

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
      "olt_id" : $("#olt_id_uncfg").val(),
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