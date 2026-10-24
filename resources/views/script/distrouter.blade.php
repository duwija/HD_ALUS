<script>
    $(document).ready(function () {
        $('#spinner').show();
        fetchLogs();
        function fetchRouterInfo() {
            $.ajax({
                url: '/distrouter/getrouterinfo/{{$distrouter->id}}',
                type: 'GET',
                success: function (data) {
                    $('#spinner').hide();
                    if (data.success) {
                        $('#distrouter-info').html(`
                            <p><strong>Router Board-name:</strong> ${data.routerInfo[0]["board-name"]}</p>
                            <p><strong>Router uptime:</strong> ${data.routerInfo[0].uptime}</p>
                            <p><strong>Router Platform:</strong> ${data.routerInfo[0].platform}</p>
                            <p><strong>Router Version:</strong> ${data.routerInfo[0].version}</p>
                            <p><strong>CPU Count:</strong> ${data.routerInfo[0]["cpu-count"]}</p>
                            <p><strong>CPU Load:</strong> ${data.routerInfo[0]["cpu-load"]} %</p>

                            <section class="content">
                            <div class="container-fluid">
                            <div class="row">
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-info">
                            <div class="inner">
                            <h4>${data.pppUserCount}</h4>
                            <p>PPP User</p>
                            </div>
                            <div class="icon"><i class="fas fa-user"></i></div>
                            </div>
                            </div>
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-success">
                            <div class="inner">
                            <h4>${data.pppActiveCount}</h4>
                            <p>PPP Online</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            </div>
                            </div>
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-danger">
                            <div class="inner">
                            <h4>${data.pppOfflineCount}</h4>
                            <p>PPP Offline</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            </div>
                            </div>
                            <div class="col-lg-3 col-3">
                            <div class="small-box bg-secondary">
                            <div class="inner">
                            <h4>${data.pppDisabledCount}</h4>
                            <p>PPP Disabled</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            </div>
                            </div>
                            </div>
                            </div>
                            </section>


                            `);


                    } else {
                        $('#distrouter-info').html('<div class="alert alert-danger">' + data.error + '</div>');
                    }
                },
                error: function () {
                    $('#spinner').hide();
                    $('#distrouter-info').html('<div class="alert alert-danger">Terjadi kesalahan saat mengambil data.</div>');
                }
            });
        }




        function loadData(status) {
          $('#spinnerpppoe').show();
          $.ajax({
            url: `/distrouter/getPppoeUsers/{{$distrouter->id}}/${status}`,
            type: 'GET',
            success: function(data) {
                if (data.success) {
                    $('#spinnerpppoe').hide();
                    var pppoeData = data.data.map(function(user) {
                        // Tentukan warna badge berdasarkan status
                        let badgeClass = '';
                        switch (user.status) {
                        case 'Online': badgeClass = 'badge-success'; break;
                        case 'Offline': badgeClass = 'badge-danger'; break;
                        case 'Disabled': badgeClass = 'badge-secondary'; break;
                        }

                        return [
                            user.name,
                            user.description,
                            user.profile,
                            user.last_logout,
                            user.address,
                            user.uptime,
                            `<span class="badge ${badgeClass}">${user.status}</span>` // Tambahkan badge
                            ];
                    });

                    // Inisialisasi DataTable dan masukkan data
                    $('#pppoeTable').DataTable({
                        data: pppoeData,
                        columns: [
                            { title: "Name" },
                            { title: "Description" },
                            { title: "profile" },
                            { title: "last logout" },
                            { title: "Address" },
                            { title: "Uptime" },
                            { title: "Status" }
                            ],
                        dom: 'Bfrtip',
                        buttons: [
                            'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
                            ],
                        "lengthMenu": [[50, 100, 200, 500, 1000], [50, 100, 200, 500, 1000]],
                        destroy: true,
                        paging: true,
                        searching: true,
                        ordering: true
                    });
                } else {
                    alert('Error fetching data!');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching PPPoE users:', error);
            }
        });
      }
      function fetchLogs() {
        $.ajax({
        url: `/distrouter/logs/{{$distrouter->id}}/`, // Sesuaikan dengan route Laravel
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if ($.fn.DataTable.isDataTable('#logTable')) {
                $('#logTable').DataTable().destroy(); // Hapus instance DataTables jika sudah ada
            }

            let logTableBody = $('#logTable tbody');
            logTableBody.empty(); // Kosongkan tabel sebelum menampilkan data baru

            if (response.success) {
                let filteredLogs = response.logs.filter(log => 
                    !['system,info,account'].includes(log.topics)
                    );

                if (filteredLogs.length > 0) {
                    filteredLogs.forEach(log => {
                        logTableBody.append(`<tr>
                            <td>${log.time ?? 'N/A'}</td>
                            <td>${log.topics ?? 'N/A'}</td>
                            <td>${log.message ?? 'N/A'}</td>
                            </tr>`);
                    });

                    // Inisialisasi DataTables
                    $('#logTable').DataTable({
                        ordering: true,
                        pageLength: 10,
                        order: [[0, 'desc']],
                        dom: 'Bfrtip',
                        buttons: [
                            'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
                            ],
                        "lengthMenu": [[200, 500, 1000], [200, 500, 1000]],
                         // Urutkan berdasarkan waktu terbaru
                    });
                } else {
                    logTableBody.html(`<tr><td colspan="3">No logs available.</td></tr>`);
                }
            } else {
                logTableBody.html(`<tr><td colspan="3">${response.message}</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching logs:', error);
            $('#logTable tbody').html(`<tr><td colspan="3">Failed to fetch logs.</td></tr>`);
        }
    });
    }

    function fetchInterfaceMonitor() {
        $.ajax({
            url: '/distrouter/getrouterinterfaces/{{$distrouter->id}}',
            type: 'GET',
            success: function (response) {
                const interfaces = response.routerInterfaces;

                if (Array.isArray(interfaces)) {
                    var rows = '';
                    var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];

                    interfaces.forEach(function (interface) {
                        var name = interface.name || 'N/A';
                        var comment = interface.comment || 'N/A';
                        var running = interface.running || 'N/A';
                        var speed = interface.speed || 'N/A';
                        rows += '<tr>';
                        rows += '<td>' + name + '</td>';
                        rows += '<td>' + comment + '</td>';
                        rows += '<td>' + running + '</td>';
                        rows += '<td>' + speed + '</td>';
                        rows += '<td id="' + name + 'tx"></td>';
                        rows += '<td id="' + name + 'rx"></td>';
                        rows += '</tr>';
                    });

                    $('#interface-table tbody').html(rows);

                    interfaces.forEach(function (interface) {
                        var name = interface.name || 'N/A';

                        $.ajax({
                            url: '/distrouter/interface_monitor/{{$distrouter->id}}',
                            type: 'GET',
                            data: { interface: name },
                            success: function (trafficResponse) {
                                var tx = trafficResponse[0].data[0] || 0;
                                var rx = trafficResponse[1].data[0] || 0;

                                var txBytes = parseInt(tx) || 0;
                                var rxBytes = parseInt(rx) || 0;

                                if (txBytes == 0) tx = '0 bps';
                                else {
                                    var iTx = parseInt(Math.floor(Math.log(txBytes) / Math.log(1024)));
                                    tx = parseFloat((txBytes / Math.pow(1024, iTx)).toFixed(2)) + ' ' + sizes[iTx];
                                }

                                if (rxBytes == 0) rx = '0 bps';
                                else {
                                    var iRx = parseInt(Math.floor(Math.log(rxBytes) / Math.log(1024)));
                                    rx = parseFloat((rxBytes / Math.pow(1024, iRx)).toFixed(2)) + ' ' + sizes[iRx];
                                }

                                $('#' + name + 'tx').text(tx);
                                $('#' + name + 'rx').text(rx);
                            },
                            error: function (xhr, status, error) {
                                console.error('Error in interface monitor request:', error);
                            }
                        });
                    });
                } else {
                    console.error('Expected an array but got:', interfaces);
                    alert('Error: Data returned is not in the expected format.');
                }
            },
            error: function (xhr, status, error) {
                alert('Error: ' + xhr.responseText);
            }
        });
    }
  // Change event for status filter
    $('#statusFilter').on('change', function() {
        let selectedStatus = $(this).val();
        loadData(selectedStatus);
    });
    
    // Handle register PPPoE user as customer
    $(document).on('click', '.register-pppoe', function() {
        const button = $(this);
        const pppoe = button.data('pppoe');
        const profile = button.data('profile');
        const comment = button.data('comment');
        const password = button.data('password');
        const routerId = button.data('router-id');
        
        // Determine name: use comment if available, otherwise use pppoe
        const displayName = comment ? comment : pppoe;
        
        Swal.fire({
            title: 'Import PPPoE User',
            html: `
                <div class="text-left">
                    <p><strong>PPPoE Username:</strong> ${pppoe}</p>
                    <p><strong>Customer Name:</strong> ${displayName}</p>
                    <p><strong>Profile:</strong> ${profile || 'N/A'}</p>
                    <p class="text-muted mt-3">Data will be pre-filled in the customer creation form for your review.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-user-plus"></i> Yes, Import',
            cancelButtonText: '<i class="fas fa-times"></i> Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: '/distrouter/prepare-pppoe',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        pppoe: pppoe,
                        profile: profile,
                        comment: comment,
                        password: password,
                        router_id: routerId
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Redirecting to customer creation form...',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = response.redirect_url;
                            });
                        } else {
                            Swal.fire({
                                title: 'Failed!',
                                text: response.message || 'Failed to prepare data',
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                            button.prop('disabled', false).html('<i class="fas fa-user-plus"></i>');
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error occurred';
                        Swal.fire({
                            title: 'Error!',
                            text: error,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                        button.prop('disabled', false).html('<i class="fas fa-user-plus"></i>');
                    }
                });
            }
        });
    });

    fetchRouterInfo();
    fetchInterfaceMonitor();
    setInterval(fetchRouterInfo, 10000);
    setInterval(fetchInterfaceMonitor, 10000);
    loadData('all');

});
</script>
<script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.colVis.min.js"></script>
<script>
// ── MikroTik Commands (perintah baca dari App\Services\MikrotikCommandCatalog) ──
(function () {
    var routerId = {{ (int) $distrouter->id }};
    var current = null;   // key perintah yang sedang ditampilkan
    var dt = null;        // instance DataTable hasil
    var FLAG_COLS = ['disabled', 'dynamic', 'running', 'active', 'invalid', 'complete', 'established', 'slave', 'inactive'];
    var SECRET_RE = /pass|secret|pre-shared|private-key|authentication-key/i;

    // Escape HTML termasuk kutip (nilai juga dipakai di atribut data-secret).
    var esc = function (s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    };

    function run(key, label) {
        current = key;
        $('.cmd-item').removeClass('active').filter('[data-key="' + key + '"]').addClass('active');
        $('#cmdLabel').text(label || key);
        $('#cmdPath').text('');
        $('#cmdMeta').text('');
        $('#cmdStatus').show();
        $('#spinnermk').show();
        $('#cmdRefresh').prop('disabled', true);
        if (dt) { dt.destroy(); dt = null; }
        $('#commandOutput').empty();

        $.ajax({
            url: '/distrouter/executeCommand',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', key: key, id: routerId }
        }).done(function (res) {
            if (key !== current) return; // sudah pindah ke perintah lain
            $('#cmdLabel').text(res.label);
            $('#cmdPath').text(res.command);
            $('#cmdMeta').text(res.output.length + ' baris · ' + res.took_ms + ' ms · ' + new Date().toLocaleTimeString('id-ID'));
            render(res.output, res.columns || []);
        }).fail(function (xhr) {
            if (key !== current) return;
            var msg = (xhr.responseJSON && xhr.responseJSON.error) || ('HTTP ' + xhr.status + ' ' + (xhr.statusText || ''));
            $('#commandOutput').html('<div class="cmd-error"><i class="fas fa-exclamation-triangle mr-1"></i> ' + esc(msg) + '</div>');
        }).always(function () {
            if (key !== current) return;
            $('#spinnermk').hide();
            $('#cmdRefresh').prop('disabled', false);
        });
    }

    function render(rows, preferred) {
        if (!rows.length) {
            $('#commandOutput').html('<div class="cmd-empty">Tidak ada data.</div>');
            return;
        }

        // Kolom = gabungan semua baris (RouterOS tidak selalu mengirim kolom yang sama per baris);
        // kolom utama di depan, '.id' & kolom lain disembunyikan (bisa dimunculkan lewat "Kolom").
        var all = [];
        rows.forEach(function (r) { Object.keys(r).forEach(function (k) { if (all.indexOf(k) < 0) all.push(k); }); });
        var main = preferred.filter(function (c) { return all.indexOf(c) >= 0; });
        var flags = main.filter(function (c) { return FLAG_COLS.indexOf(c) >= 0; });
        main = main.filter(function (c) { return FLAG_COLS.indexOf(c) < 0; });
        if (!main.length) main = all.filter(function (c) { return c !== '.id' && FLAG_COLS.indexOf(c) < 0; });
        var rest = all.filter(function (c) { return main.indexOf(c) < 0 && flags.indexOf(c) < 0; });
        var cols = main.concat(rest);
        var hasFlags = all.some(function (c) { return FLAG_COLS.indexOf(c) >= 0; });

        var html = '<table id="outputTable" class="table table-bordered table-hover table-sm"><thead><tr><th>#</th>';
        if (hasFlags) html += '<th>Status</th>';
        cols.forEach(function (c) { html += '<th>' + esc(c) + '</th>'; });
        html += '</tr></thead><tbody>';

        rows.forEach(function (r, i) {
            var cls = (r.disabled === 'true' ? ' row-disabled' : '') + (r.dynamic === 'true' ? ' row-dynamic' : '');
            html += '<tr class="' + cls + '"><td>' + (i + 1) + '</td>';
            if (hasFlags) html += '<td class="row-flags">' + flagBadges(r) + '</td>';
            cols.forEach(function (c) { html += '<td>' + cell(c, r[c]) + '</td>'; });
            html += '</tr>';
        });
        html += '</tbody></table>';
        $('#commandOutput').html('<div class="table-responsive">' + html + '</div>');

        var offset = hasFlags ? 2 : 1;
        var hidden = rest.map(function (c) { return cols.indexOf(c) + offset; });
        dt = $('#outputTable').DataTable({
            dom: 'Bfrtip',
            pageLength: 50,
            order: [],
            columnDefs: [{ targets: hidden, visible: false }],
            buttons: [
                { extend: 'colvis', text: '<i class="fas fa-columns"></i> Kolom' },
                'pageLength',
                { extend: 'copy', exportOptions: { columns: ':visible' } },
                { extend: 'excel', exportOptions: { columns: ':visible' }, title: $('#cmdLabel').text() },
                { extend: 'csv', exportOptions: { columns: ':visible' }, title: $('#cmdLabel').text() }
            ],
            lengthMenu: [[50, 100, 500, -1], [50, 100, 500, 'Semua']],
            language: { search: 'Cari:', info: '_START_–_END_ dari _TOTAL_', infoEmpty: '0 baris', infoFiltered: '(dari _MAX_)', zeroRecords: 'Tidak ada yang cocok', paginate: { previous: '‹', next: '›' } }
        });
    }

    function flagBadges(r) {
        var b = '';
        if (r.disabled === 'true') b += '<span class="badge badge-secondary">disabled</span>';
        if (r.invalid === 'true') b += '<span class="badge badge-danger">invalid</span>';
        if (r.running === 'true' || r.active === 'true' || r.established === 'true') b += '<span class="badge badge-success">' + (r.running === 'true' ? 'running' : (r.established === 'true' ? 'established' : 'active')) + '</span>';
        else if (r.running === 'false') b += '<span class="badge badge-light">down</span>';
        if (r.dynamic === 'true') b += '<span class="badge badge-info">dynamic</span>';
        return b || '<span class="flag-false">–</span>';
    }

    function cell(col, val) {
        if (val === undefined || val === null || val === '') return '<span class="flag-false">–</span>';
        if (SECRET_RE.test(col)) {
            return '<span class="mk-secret" title="Klik untuk menampilkan" data-secret="' + esc(val) + '">••••••</span>';
        }
        if (val === 'true') return '<span class="flag-true">✓</span>';
        if (val === 'false') return '<span class="flag-false">✗</span>';
        return esc(val);
    }

    // Klik menu / hasil pencarian
    $('#mkCommands').on('click', '.cmd-item', function (e) {
        e.preventDefault();
        run($(this).data('key'), $(this).data('label'));
    });
    $('#cmdRefresh').on('click', function () { if (current) run(current, $('#cmdLabel').text()); });

    // Password disamarkan, klik untuk menampilkan/menyembunyikan
    $('#commandOutput').on('click', '.mk-secret', function () {
        var $s = $(this), shown = $s.data('shown');
        $s.text(shown ? '••••••' : $s.data('secret')).data('shown', !shown).css('letter-spacing', shown ? '2px' : 'normal');
    });

    // Cari perintah di semua grup
    $('#cmdSearch').on('input', function () {
        var q = $.trim($(this).val()).toLowerCase();
        var $box = $('#cmdSearchResults').empty();
        if (!q) { $box.hide(); return; }
        $('.cmd-nav .cmd-item').each(function () {
            var hay = ($(this).data('label') + ' ' + $(this).attr('title')).toLowerCase();
            if (hay.indexOf(q) >= 0) $box.append($(this).clone().removeClass('dropdown-item'));
        });
        if (!$box.children().length) $box.append('<span class="text-muted small">Tidak ada perintah yang cocok.</span>');
        $box.css('display', 'flex');
    }).on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#cmdSearchResults .cmd-item').first().trigger('click'); }
    });
})();
</script>
