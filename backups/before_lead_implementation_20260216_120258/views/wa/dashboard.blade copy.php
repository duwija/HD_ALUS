@extends('layout.main')
@section('title',' Wa Gateway')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title font-weight-bold"> Whatsapp Gateway </h3>
    </div>

    <div class="card-body">

      <div class="row mb-4 align-items-end">
        <div class="col-md-6">
          <a href="{{ url('/wa/logs') }}" class="btn btn-info">
            📬 Log Pesan
          </a>
        </div>
        <div class="col-md-6 text-right">
          <form id="addSessionForm" class="form-inline justify-content-end">
            <div class="form-group mr-2">
              <input type="text" id="newSession" class="form-control" placeholder="Nama session baru" required>
            </div>
            <button type="submit" class="btn btn-success">+ Tambah Session</button>
          </form>
        </div>
      </div>

      <table class="table table-bordered" id="sessionTable">
        <thead>
          <tr>
            <th>Session</th>
            <th>Status</th>
            <th>Auth Data</th>
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

    <!-- Modal QR -->
    <div class="modal fade" id="qrModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Scan QR WhatsApp</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- Wrapper untuk center-kan QR -->
            <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 200px;">
              <div id="qrLoading" class="text-center">Loading QR...</div>
              <img id="qrImage" src="" alt="QR Code" class="img-fluid mt-2" style="display:none">
              <div id="qrCountdown" class="mt-2 text-muted"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Konfirmasi Hapus Session</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Yakin ingin menghapus session <strong id="sessionToDelete"></strong>?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Force Reset -->
    <div class="modal fade" id="forceResetModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">⚠️ Konfirmasi Force Reset</h5>
            <button type="button" class="close" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p><strong>PERHATIAN:</strong> Force Reset akan:</p>
            <ul>
              <li>✅ Menghapus SEMUA data autentikasi</li>
              <li>✅ Menghapus cache session</li>
              <li>✅ Memaksa scan QR ulang untuk login berikutnya</li>
              <li>❌ Session akan logout dari WhatsApp</li>
            </ul>
            <p>Session <strong id="sessionToReset"></strong> akan benar-benar ter-reset.</p>
            <p><em>Gunakan ini jika session macet atau perlu reset authentication.</em></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-warning" id="confirmResetBtn">Ya, Force Reset</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('footer-scripts')
<script>
    // Utility functions to handle external loading modals
  function hideAllModals() {
        // Hide any Bootstrap modals
    $('.modal').modal('hide');

        // Remove modal backdrops
    $('.modal-backdrop').remove();

        // Reset body classes
    $('body').removeClass('modal-open').css('padding-right', '');

        // Hide common loading overlays (adjust selectors based on your loading library)
    if (typeof Swal !== 'undefined') {
      Swal.close();
    }

        // Hide other common loading elements
    $('.loading-overlay, .spinner-overlay, .please-wait').hide();
    $('.btn-loading').removeClass('btn-loading');
  }

  function showButtonLoading(button, text = 'Memuat...') {
    const $btn = $(button);
    if (!$btn.data('original-text')) {
      $btn.data('original-text', $btn.html());
    }
    $btn.prop('disabled', true).html(`
      <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
      ${text}
      `);
  }

  function hideButtonLoading(button) {
    const $btn = $(button);
    const originalText = $btn.data('original-text');
    if (originalText) {
      $btn.prop('disabled', false).html(originalText);
    }
  }

    // Global function to hide external loading modals (can be called by external scripts)
  window.hideLoadingModal = function() {
    hideAllModals();
  };
</script>
<script>
  let deleteTarget = null;
  let resetTarget = null;

  async function fetchSessions() {
    try {
      console.log('📡 Memuat dashboard WA...');
      const health = await fetch('/wa/status').then(r => r.json());
      console.log('✅ Data /wa/status:', health);
      const sessions = health.sessions || [];
      console.log('📋 Daftar session:', sessions);

      const tbody = document.getElementById('sessionList');
      tbody.innerHTML = '';

      for (const session of sessions) {
        console.log(`🔍 Mengecek session: ${session}`);
        const row = document.createElement('tr');
        let status = 'loading';
        let number = '-', name = '-', platform = '-';
        let messageCount = '-';
        let hasAuthData = false;

        try {
          const stats = await fetch(`/wa/${session}/stats`).then(r => r.json());
          messageCount = stats.count ?? '-';
        } catch (e) {
          console.warn(`⚠️ Gagal ambil statistik untuk ${session}`);
        }

        try {
                    // Gunakan endpoint session-status yang lebih detail
          const response = await fetch(`/wa/${session}/session-status`);
          const data = await response.json();
          console.log(`✅ Session Status ${session}:`, data);

                    status = data.status; // ready, not_ready, not_created
                    number = data.number ?? '-';
                    name = data.name ?? '-';
                    platform = data.platform ?? '-';
                    hasAuthData = data.hasAuthData ?? false;
                    
                    const reconnectAttempts = data.reconnectAttempts || 0;
                    
                    let badgeClass = 'badge-secondary';
                    if (status === 'ready') badgeClass = 'badge-success';
                    else if (status === 'not_ready') badgeClass = 'badge-warning';
                    else if (status === 'not_created') badgeClass = 'badge-info';

                    // Auth data badge
                    const authBadge = hasAuthData ? 'badge-success' : 'badge-secondary';
                    const authText = hasAuthData ? 'Ada' : 'Tidak Ada';

                    const deleteBtn = `<button class="btn btn-outline-danger btn-sm ml-1" onclick="openDeleteModal('${session}')">Hapus</button>`;

                    let actionBtn = '';
                    if (status === 'ready') {
                      actionBtn = `
                      <button class="btn btn-danger btn-sm" onclick="logoutSession('${session}')">Logout</button>

                      <button class="btn btn-warning btn-sm ml-1" onclick="forceResetSession('${session}')">Force Reset</button>
                      <button class="btn btn-info btn-sm ml-1" onclick="restartSession('${session}')">Restart</button>
                      
                      ${deleteBtn}
                      `;
                    } else if (status === 'not_ready' || status === 'not_created' || status === 'disconnected') {
                      actionBtn = `
                      <button class="btn btn-primary btn-sm" onclick="showQr('${session}')">Scan QR</button>


                      <button class="btn btn-secondary btn-sm ml-1" onclick="cleanSession('${session}')">Clean</button>
                      ${deleteBtn}
                      `;
                    } else {
                      actionBtn = `<span class="text-muted">${status}</span>${deleteBtn}`;
                    }

                    row.innerHTML = `
                    <td><a href="/wa/chat?session=${session}" class="btn btn-info btn-sm mr-1">${session}</a></td>
                    <td><span class="badge ${badgeClass}">${status}</span></td>
                    <td><span class="badge ${authBadge}">${authText}</span></td>
                    <td>${number}</td>
                    <td>${name}</td>
                    <td>${platform}</td>
                    <td>${messageCount}</td>
                    <td>${actionBtn}</td>
                    `;
                  } catch (err) {
                    console.error(`❌ Gagal ambil status untuk ${session}:`, err);
                    row.innerHTML = `
                    <td>${session}</td>
                    <td colspan="6" class="text-danger">Gagal mengambil status</td>
                    <td><button class="btn btn-outline-danger btn-sm" onclick="openDeleteModal('${session}')">Hapus</button></td>
                    `;
                  }

                  tbody.appendChild(row);
                }
              } catch (error) {
                console.error('❌ Gagal memuat dashboard:', error);
              }
            }

            function openDeleteModal(session) {
              deleteTarget = session;
              document.getElementById('sessionToDelete').textContent = session;
              $('#confirmDeleteModal').modal('show');
            }

            function openForceResetModal(session) {
              resetTarget = session;
              document.getElementById('sessionToReset').textContent = session;
              $('#forceResetModal').modal('show');
            }

            document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
              if (!deleteTarget) return;

              try {
                const response = await fetch(`/wa/${deleteTarget}/delete`, {
                  method: 'DELETE',
                  headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });

                const result = await response.json();

                $('#confirmDeleteModal').modal('hide');
                if (result.status === 'deleted' || result.status === 'success') {
                  await Swal.fire({
                    title: 'Berhasil!',
                    text: 'Session berhasil dihapus.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                  });
                  
  // 🔁 Refresh halaman agar tabel langsung update & spinner tidak muncul
                  window.location.reload();
                } else {
                  Swal.fire({
                    title: 'Gagal',
                    text: result.message || 'Terjadi kesalahan saat menghapus session.',
                    icon: 'error'
                  });
                }


              } catch (error) {
                $('#confirmDeleteModal').modal('hide');
                Swal.fire({
                  title: 'Gagal',
                  text: 'Terjadi kesalahan saat menghapus session',
                  icon: 'error'
                });
              }
            });

            document.getElementById('confirmResetBtn').addEventListener('click', async function () {
              if (!resetTarget) return;

              try {
                const response = await fetch(`/wa/${resetTarget}/force-logout`, {
                  method: 'POST',
                  headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });

                $('#forceResetModal').modal('hide');

                Swal.fire({
                  title: 'Force Reset Berhasil!',
                  text: `Session "${resetTarget}" telah direset lengkap. QR scan diperlukan untuk login.`,
                  icon: 'success',
                  confirmButtonText: 'OK'
                }).then(() => {
                  fetchSessions();
                });

              } catch (error) {
                Swal.fire({
                  title: 'Gagal',
                  text: 'Terjadi kesalahan saat force reset',
                  icon: 'error'
                });
              }
            });

            let qrInterval, countdownInterval;
    let restartedSessions = new Set(); // Track sessions that have been restarted

    async function showQr(session) {
      $('#qrModal').modal('show');
      document.getElementById('qrImage').style.display = 'none';
      document.getElementById('qrLoading').style.display = 'block';
      document.getElementById('qrCountdown').innerText = 'Memeriksa status session...';

      try {
            // Cek status session terlebih dahulu
        const sessionRes = await fetch(`/wa/${session}/session-status`).then(r => r.json());
        console.log(`🔍 Session ${session} status:`, sessionRes.status);

            // Jika session disconnected dan belum pernah direstart, restart dulu untuk fresh QR
        if (sessionRes.status === 'disconnected' && !restartedSessions.has(session)) {
          document.getElementById('qrCountdown').innerText = 'Session disconnected, melakukan restart untuk QR fresh...';
          console.log(`🔄 Restarting disconnected session ${session} for fresh QR...`);

                // Mark session as restarted to prevent infinite loop
          restartedSessions.add(session);

          const restartRes = await fetch(`/wa/${session}/restart`, {
            method: 'POST',
            headers: { 
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
          });

          if (!restartRes.ok) {
            throw new Error(`HTTP ${restartRes.status}: ${restartRes.statusText}`);
          }

          const restartData = await restartRes.json();
          console.log(`✅ Restart result:`, restartData);

          if (restartData.status === 'success') {
                    // Wait for client to initialize and generate fresh QR
            document.getElementById('qrCountdown').innerText = 'Menunggu client initialize...';
            await new Promise(resolve => setTimeout(resolve, 10000));
          } else {
            throw new Error(restartData.message || 'Restart failed');
          }
        } else if (sessionRes.status === 'disconnected') {
                // Already restarted but still disconnected - show error
          document.getElementById('qrCountdown').innerText = 'Session masih disconnected setelah restart. Coba restart gateway Node.js.';
          document.getElementById('qrLoading').style.display = 'none';
          return;
        }
            // Jika session not_created, start dulu
        else if (sessionRes.status === 'not_created') {
          document.getElementById('qrCountdown').innerText = 'Memulai session...';
          console.log(`🚀 Starting session ${session} for QR...`);

          const startRes = await fetch('/wa/start', {
            method: 'POST',
            headers: { 
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ session })
          });

          if (!startRes.ok) {
            throw new Error(`HTTP ${startRes.status}: ${startRes.statusText}`);
          }

          const startData = await startRes.json();
          console.log(`✅ Start result:`, startData);

          if (startData.status === 'started' || startData.status === 'running') {
                    // Wait a bit for client to initialize
            await new Promise(resolve => setTimeout(resolve, 5000));
          } else {
            throw new Error(startData.message || 'Start session failed');
          }
        }
      } catch (error) {
        console.error('❌ Error preparing session:', error);
        document.getElementById('qrCountdown').innerText = 'Error mempersiapkan session';
      }

      let countdown = 60;
      document.getElementById('qrCountdown').innerText = `QR akan diperbarui dalam ${countdown} detik`;

      countdownInterval = setInterval(() => {
        countdown--;
        if (countdown <= 0) countdown = 60;
        document.getElementById('qrCountdown').innerText = `QR akan diperbarui dalam ${countdown} detik`;
      }, 1000);

      qrInterval = setInterval(async () => {
        try {
                // Cek status session detail
          const sessionRes = await fetch(`/wa/${session}/session-status`).then(r => r.json());

          if (sessionRes.status === 'ready') {
                    // Sudah authenticated
            clearInterval(qrInterval);
            clearInterval(countdownInterval);
            $('#qrModal').modal('hide');

            Swal.fire({
              title: 'Berhasil!',
              text: `Session "${session}" sudah terhubung ke WhatsApp`,
              icon: 'success',
              timer: 2000,
              timerProgressBar: true
            });

            fetchSessions();
            return;
          }

                // Cek QR code
          const qrRes = await fetch(`/wa/${session}/status`).then(r => r.json());
          if (qrRes.status === 'not_authenticated' && qrRes.qr) {
            document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrRes.qr)}`;
            document.getElementById('qrLoading').style.display = 'none';
            document.getElementById('qrImage').style.display = 'block';
          } else if (qrRes.status === 'authenticated') {
            clearInterval(qrInterval);
            clearInterval(countdownInterval);
            $('#qrModal').modal('hide');

            Swal.fire({
              title: 'Berhasil!',
              text: `Session "${session}" berhasil login ke WhatsApp`,
              icon: 'success',
              timer: 2000,
              timerProgressBar: true
            });

            fetchSessions();
          } else if (qrRes.status === 'initializing') {
            document.getElementById('qrLoading').style.display = 'block';
            document.getElementById('qrImage').style.display = 'none';
            document.getElementById('qrCountdown').innerText = 'Menginisialisasi client...';
          }
        } catch (error) {
          console.error('❌ Error checking QR status:', error);
        }
      }, 3000);
    }

    $('#qrModal').on('hidden.bs.modal', function () {
      clearInterval(qrInterval);
      clearInterval(countdownInterval);
        // Clear restart tracking when modal is closed
      restartedSessions.clear();
    });

    document.getElementById('addSessionForm').addEventListener('submit', async function (e) {
      e.preventDefault();
      const session = document.getElementById('newSession').value.trim();

      if (!session) {
        Swal.fire({
          title: 'Peringatan',
          text: 'Nama session tidak boleh kosong',
          icon: 'warning',
          confirmButtonText: 'OK'
        });
        return;
      }

      if (/\s/.test(session)) {
        Swal.fire({
          title: 'Peringatan', 
          text: 'Nama session tidak boleh mengandung spasi',
          icon: 'warning',
          confirmButtonText: 'OK'
        });
        return;
      }

      const submitBtn = this.querySelector('button[type="submit"]');
      const inputField = document.getElementById('newSession');

      try {
            // Hide any external loading modals first
        hideAllModals();

            // Show loading state on button
        showButtonLoading(submitBtn, 'Memulai...');
        inputField.disabled = true;

        console.log('🚀 Starting session:', session);

        const response = await fetch('/wa/start', {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ session })
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('✅ Response:', data);

        if (data.status === 'started' || data.status === 'running') {
                // Clear form immediately
          document.getElementById('newSession').value = '';

          await Swal.fire({
            title: 'Berhasil!',
            text: `Session "${session}" siap digunakan. QR akan muncul jika belum login.`,
            icon: 'success',
            confirmButtonText: 'OK',
            timer: 3000,
            timerProgressBar: true
          });

                // Refresh sessions list
          fetchSessions();
        } else {
          await Swal.fire({
            title: 'Gagal',
            text: data.message || 'Status tidak diketahui',
            icon: 'error',
            confirmButtonText: 'OK'
          });
        }
      } catch (error) {
        console.error('❌ Error adding session:', error);
        await Swal.fire({
          title: 'Error',
          text: `Terjadi kesalahan: ${error.message}`,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      } finally {
            // Always reset UI state
        hideButtonLoading(submitBtn);
        inputField.disabled = false;

            // Double-check to hide any stuck modals
        setTimeout(() => {
          hideAllModals();
        }, 200);
      }
    });

    console.log('📡 Memuat dashboard WA...');
    fetchSessions();
    console.log('✅ fetchSessions() dipanggil');

    // Auto refresh setiap 30 detik
    setInterval(fetchSessions, 30000);
  </script>
  <script>
    async function logoutSession(session) {
      Swal.fire({
        title: 'Konfirmasi Logout',
        text: `Apakah kamu yakin ingin logout session "${session}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
      }).then(async (result) => {
        if (!result.isConfirmed) return;

        try {
          const response = await fetch(`/wa/${session}/logout`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
          });

          const resultData = await response.json();
          console.log('Logout result:', resultData);

          if (resultData.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Logout Berhasil',
              text: `Session "${session}" telah logout dari WhatsApp.`,
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
          fetchSessions(); // refresh tabel session
        });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Gagal Logout',
              text: resultData.message || 'Terjadi kesalahan saat logout.',
            });
          }
        } catch (error) {
          console.error('Logout error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Tidak dapat terhubung ke gateway atau server error.',
          });
        }
      });
    }
    async function forceResetSession(session) {
      Swal.fire({
        title: 'Konfirmasi Force Reset',
        html: `<b>${session}</b><br>Semua data auth akan dihapus dan perlu scan QR ulang.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Reset',
        cancelButtonText: 'Batal'
      }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
          const res = await fetch(`/wa/${session}/force-logout`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
          });
          const data = await res.json();
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Force Reset Berhasil',
              text: `Session "${session}" telah direset.`,
              timer: 2000,
              showConfirmButton: false
            });
            fetchSessions();
          } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Tidak dapat force reset.' });
          }
        } catch (e) {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Gateway tidak merespons.' });
        }
      });
    }

    async function restartSession(session) {
      Swal.fire({
        title: 'Restart Session',
        text: `Ingin merestart session "${session}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Restart',
        cancelButtonText: 'Batal'
      }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
          const res = await fetch(`/wa/${session}/restart`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
          });
          const data = await res.json();
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Restart Berhasil',
              text: `Session "${session}" sedang diinisialisasi ulang.`,
              timer: 2000,
              showConfirmButton: false
            });
            fetchSessions();
          } else {
            Swal.fire({ icon: 'error', title: 'Gagal Restart', text: data.message || 'Terjadi kesalahan.' });
          }
        } catch (e) {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Gateway tidak merespons.' });
        }
      });
    }
    /* ============================================================
   ⚙️ CLEAN SESSION (hapus auth data)
============================================================ */
    async function cleanSession(session) {
      Swal.fire({
        title: 'Clean Session',
        html: `Apakah kamu yakin ingin <b>menghapus data autentikasi</b> untuk session "<b>${session}</b>"?<br><br>Setelah dibersihkan, kamu harus <b>scan QR ulang</b>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6c757d',
        cancelButtonColor: '#999',
        confirmButtonText: 'Ya, Bersihkan',
        cancelButtonText: 'Batal'
      }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
          const res = await fetch(`/wa/${session}/clean`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
          });
          const data = await res.json();
          if (data.status === 'success' || data.status === 'cleaned') {
            Swal.fire({
              icon: 'success',
              title: 'Session Dibersihkan',
              text: `Data autentikasi untuk "${session}" telah dihapus.`,
              timer: 2000,
              showConfirmButton: false
            });
            fetchSessions();
          } else {
            Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Tidak dapat clean session.' });
          }
        } catch (e) {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Gateway tidak merespons.' });
        }
      });
    }
  </script>

  @endsection