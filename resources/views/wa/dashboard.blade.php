@extends('layout.main')
@section('title','WhatsApp Gateway')

@section('content')
<section class="content-header">

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title font-weight-bold">WhatsApp Gateway</h3>
        </div>

        <div class="card-body">

            <!-- Session Control -->
            <div class="row mb-4 align-items-end">
                <div class="col-md-6">
                    <a href="/wa/logs" class="btn btn-info">📬 Log Pesan</a>
                </div>

                <div class="col-md-6 text-right">
                    <form id="addSessionForm" class="form-inline justify-content-end">
                        <input type="text" id="newSession" class="form-control mr-2" placeholder="Nama session baru" required>
                        <button class="btn btn-success">+ Tambah Session</button>
                    </form>
                </div>
            </div>

            <!-- Session Table -->
            <div class="table-responsive">
            <table class="table table-bordered table-hover" id="sessionTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Nomor</th>
                        <th>Nama</th>
                        <th>Platform</th>
                        <th>Pesan Terkirim</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="sessionList"></tbody>
            </table>
            </div>

        </div>
    </div>

</section>

<!-- QR Modal -->
<div class="modal fade" id="qrModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Scan QR WhatsApp</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body text-center">

                <div id="qrLoading">Mengambil QR...</div>
                <img id="qrImage" src="" class="img-fluid" style="display:none;">
                <div id="qrCountdown" class="mt-2 text-muted"></div>

            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation -->
<div class="modal fade" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Hapus Session?</h5>
                <button class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body text-center">
                Anda yakin ingin menghapus session <b id="deleteSessionName"></b>?
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>

        </div>
    </div>
</div>

@endsection

@section('footer-scripts')
<script>
    function hideAllModals() {
    console.log("🔥 hideAllModals() called");

    // 1. Tutup semua modal Bootstrap
    $('.modal').each(function () {
        try { $(this).modal('hide'); } catch (e) {}
    });

    // 2. Hapus semua backdrop
    $('.modal-backdrop').remove();

    // 3. Reset body
    $('body').removeClass('modal-open').css({
        'padding-right': '',
        'overflow': ''
    });

    // 4. Tutup SweetAlert (jika ada)
    if (typeof Swal !== 'undefined') {
        try { Swal.close(); } catch (e) {}
    }

    // 5. Hide overlay loading lain (opsional)
    $('.loading-overlay, .spinner-overlay, .please-wait').hide();

    // 6. Reset tombol yang sedang loading
    $('.btn-loading').each(function () {
        const original = $(this).data('original-text');
        if (original) {
            $(this).prop('disabled', false).html(original);
            $(this).removeClass('btn-loading');
        }
    });
}

</script>


<script>

    let qrInterval = null;
    let qrCountdownInterval = null;
    let deleteTarget = null;

// ====================================================
// 🔄 LOAD SESSION LIST
// ====================================================
    let isLoadingSessions = false;

    async function loadSessions() {
        if (isLoadingSessions) return;
        isLoadingSessions = true;

        try {
            const res = await fetch("/wa/status");
            const data = await res.json();

            if (data.status === 'error') {
                showGatewayOffline();
                return;
            }

            // sessions bisa array ["WA_01"] atau object {WA_01: {...}}
            let sessions = data.sessions ?? [];
            if (!Array.isArray(sessions)) {
                sessions = Object.keys(sessions);
            }

            const tbody = document.getElementById("sessionList");
            tbody.innerHTML = "";

            if (sessions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Belum ada session. Buat session baru di atas.</td></tr>';
                return;
            }

            // Fetch semua status & stats secara paralel
            const statusPromises = sessions.map(s =>
                fetch(`/wa/${s}/status`).then(r => r.json()).catch(() => ({ status: 'error' }))
            );
            const statsPromises = sessions.map(s =>
                fetch(`/wa/${s}/stats`).then(r => r.json()).then(j => j.count).catch(() => "-")
            );

            const [allStatus, allStats] = await Promise.all([
                Promise.all(statusPromises),
                Promise.all(statsPromises)
            ]);

            for (let i = 0; i < sessions.length; i++) {
                const s = sessions[i];
                const st = allStatus[i];
                const messageCount = allStats[i];

                const tr = document.createElement("tr");

                let badge = "secondary";
                let statusText = st.status || "unknown";
                if (statusText === "authenticated") badge = "success";
                else if (statusText === "not_authenticated") badge = "warning";
                else if (statusText === "initializing") badge = "info";
                else if (statusText === "gateway_offline") { badge = "danger"; statusText = "offline"; }
                else if (statusText === "auth_failure" || statusText === "auth_failed_permanent") badge = "danger";
                else if (statusText === "init_failed") badge = "danger";

                tr.innerHTML = `
                <td><a href="/wa/chat?session=${s}" class="btn btn-info btn-sm">${s}</a></td>
                <td><span class="badge badge-${badge}">${statusText}</span></td>
                <td>${st.number ?? "-"}</td>
                <td>${st.name ?? "-"}</td>
                <td>${st.platform ?? "-"}</td>
                <td>${messageCount}</td>
                <td>${makeActions(s, statusText)}</td>
                `;

                tbody.appendChild(tr);
            }
        } catch (e) {
            showGatewayOffline();
        } finally {
            isLoadingSessions = false;
        }
    }

    function showGatewayOffline() {
        const tbody = document.getElementById("sessionList");
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> Gateway offline — tidak bisa terhubung ke WA server</td></tr>';
    }

    function makeActions(session, status) {
        let out = "";

        const deleteBtn = `<button class="btn btn-outline-danger btn-sm ml-2" onclick="deleteSession('${session}')">Hapus</button>`;

        if (status === "authenticated") {
            out += `
            <button class="btn btn-danger btn-sm" onclick="logoutSession('${session}')">Logout</button>
            <button class="btn btn-warning btn-sm ml-1" onclick="restartSession('${session}')">Restart</button>
            `;
        } else if (status === "initializing") {
            out += `<span class="text-info mr-2"><i class="fas fa-spinner fa-spin"></i> Memuat...</span>`;
            out += `<button class="btn btn-warning btn-sm" onclick="restartSession('${session}')">Restart</button>`;
        } else if (status === "offline" || status === "gateway_offline") {
            out += `<span class="text-danger mr-2">Gateway offline</span>`;
        } else if (status === "auth_failed_permanent" || status === "init_failed") {
            out += `<button class="btn btn-warning btn-sm" onclick="restartSession('${session}')">Restart</button>`;
        } else {
            out += `<button class="btn btn-primary btn-sm" onclick="showQr('${session}')">Scan QR</button>`;
            out += `<button class="btn btn-warning btn-sm ml-1" onclick="restartSession('${session}')">Restart</button>`;
        }

        return out + deleteBtn;
    }

// ====================================================
// 🔳 QR MODAL
// ====================================================
    async function showQr(session) {

        $('#qrModal').modal('show');
        document.getElementById("qrImage").style.display = "none";
        document.getElementById("qrLoading").style.display = "block";
        document.getElementById("qrLoading").innerText = "Mengambil QR...";

        let countdown = 60;
        document.getElementById("qrCountdown").innerText = `QR refresh dalam ${countdown}s`;

        qrCountdownInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) countdown = 60;
            document.getElementById("qrCountdown").innerText = `QR refresh dalam ${countdown}s`;
        }, 1000);

        // Poll immediately then every 3s
        const pollQr = async () => {
            try {
                let res = await fetch(`/wa/${session}/status`).then(r => r.json());

                if (res.status === "authenticated") {
                    clearInterval(qrInterval);
                    clearInterval(qrCountdownInterval);
                    $('#qrModal').modal('hide');
                    Swal.fire("Berhasil!", "WhatsApp sudah terhubung", "success");
                    loadSessions();
                    return;
                }

                if (res.status === "gateway_offline") {
                    document.getElementById("qrLoading").innerText = "Gateway offline...";
                    document.getElementById("qrLoading").style.display = "block";
                    document.getElementById("qrImage").style.display = "none";
                    return;
                }

                if ((res.status === "not_authenticated" || res.status === "initializing") && res.qr) {
                    document.getElementById("qrImage").src =
                    "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" + encodeURIComponent(res.qr);

                    document.getElementById("qrLoading").style.display = "none";
                    document.getElementById("qrImage").style.display = "block";
                } else if (!res.qr) {
                    document.getElementById("qrLoading").innerText = "Menunggu QR dari server...";
                    document.getElementById("qrLoading").style.display = "block";
                }
            } catch (e) {
                document.getElementById("qrLoading").innerText = "Gagal terhubung ke gateway";
            }
        };

        await pollQr();
        qrInterval = setInterval(pollQr, 3000);
    }

    $('#qrModal').on('hidden.bs.modal', function () {
        clearInterval(qrInterval);
        clearInterval(qrCountdownInterval);
    });

// ====================================================
// 🗑 DELETE SESSION
// ====================================================
    function deleteSession(session) {
        deleteTarget = session;
        document.getElementById("deleteSessionName").innerText = session;
        $('#deleteModal').modal('show');
    }

    document.getElementById("confirmDelete").addEventListener("click", async () => {
        $('#deleteModal').modal('hide');
        
        try {
            const res = await fetch(`/wa/${deleteTarget}/delete`, {
                method: "DELETE",
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
            });
            
            const data = await res.json();
            
            if (data.status === 'success' || data.status === 'deleted' || res.ok) {
                Swal.fire("Berhasil!", "Session berhasil dihapus", "success");
                loadSessions();
            } else {
                Swal.fire("Error", data.message || "Gagal menghapus session", "error");
            }
        } catch (error) {
            Swal.fire("Error", "Gagal terhubung ke gateway", "error");
        }
    });

// ====================================================
// 🔄 LOGOUT & RESTART SESSION
// ====================================================
    async function logoutSession(session) {
        const result = await Swal.fire({
            title: 'Logout Session?',
            text: `Anda yakin ingin logout session ${session}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Logout!',
            cancelButtonText: 'Batal'
        });
        
        if (!result.isConfirmed) return;
        
        try {
            const res = await fetch(`/wa/${session}/logout`, {
                method: "POST",
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
            });
            
            const data = await res.json();
            
            if (data.status === 'success' || data.status === 'logged_out' || res.ok) {
                Swal.fire("Berhasil!", "Session berhasil logout", "success");
                loadSessions();
            } else {
                Swal.fire("Error", data.message || "Gagal logout session", "error");
            }
        } catch (error) {
            Swal.fire("Error", "Gagal terhubung ke gateway", "error");
        }
    }

    async function restartSession(session) {
        const result = await Swal.fire({
            title: 'Restart Session?',
            text: `Anda yakin ingin restart session ${session}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Restart!',
            cancelButtonText: 'Batal'
        });
        
        if (!result.isConfirmed) return;
        
        try {
            const res = await fetch(`/wa/${session}/restart`, {
                method: "POST",
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
            });
            
            const data = await res.json();
            
            if (data.status === 'success' || data.status === 'restarted' || res.ok) {
                Swal.fire("Berhasil!", "Session berhasil direstart", "success");
                loadSessions();
            } else {
                Swal.fire("Error", data.message || "Gagal restart session", "error");
            }
        } catch (error) {
            Swal.fire("Error", "Gagal terhubung ke gateway", "error");
        }
    }

// ====================================================
// ➕ ADD SESSION
// ====================================================
    document.getElementById("addSessionForm").addEventListener("submit", async e => {
        e.preventDefault();

        const name = document.getElementById("newSession").value.trim();
        if (!name) return;

        try {
            const res = await fetch("/wa/start", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({session: name})
            });

            const data = await res.json();

            if (data.status === 'error') {
                Swal.fire("Error", data.message || "Gagal membuat session", "error");
            } else {
                document.getElementById("newSession").value = "";
                Swal.fire("Info", "Session dibuat. QR akan tampil jika belum login.", "success")
                .then(() => loadSessions());
            }
        } catch (error) {
            Swal.fire("Error", "Gagal terhubung ke gateway", "error");
        }
    });

// ====================================================
// AUTO REFRESH LIST
// ====================================================
    loadSessions();
    setInterval(loadSessions, 15000);

</script>
@endsection
