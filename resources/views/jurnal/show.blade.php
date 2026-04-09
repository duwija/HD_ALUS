
<section class="content-header">

  <div class="card-header d-flex align-items-center">
    <h3 class="card-title mb-0">
      <i class="fas fa-book"></i> Jurnal Code: <span class="text-primary">{{ $code }}</span>
    </h3>
    <button type="button" id="btn-toggle-edit" class="btn btn-warning btn-sm ml-auto">
      <i class="fas fa-edit mr-1"></i> Edit Jurnal
    </button>
  </div>

  <div class="card-body">

    {{-- ======== VIEW MODE ======== --}}
    <div id="view-mode">
      @if($jurnals->isEmpty())
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-circle"></i>
        Tidak ada jurnal ditemukan untuk <strong>{{ $code }}</strong>.
      </div>
      @else
      <table class="table table-bordered table-striped">
        <thead class="thead-dark">
          <tr>
            <th>#</th>
            <th>Tanggal</th>
            <th>Deskripsi</th>
            <th>ID Akun</th>
            <th class="text-right">Debet</th>
            <th class="text-right">Kredit</th>
          </tr>
        </thead>
        <tbody>
          <tr class="bg-light font-weight-bold">
            <td colspan="6" class="text-left">{{ $note }}</td>
          </tr>
          @foreach($jurnals as $index => $jurnal)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($jurnal->date)->format('d/m/Y') }}</td>
            <td>{{ $jurnal->description }}</td>
            <td>{{ $jurnal->id_akun }}{{ $jurnal->akun ? ' | ' . $jurnal->akun->name : '' }}</td>
            <td class="text-right">{{ $jurnal->debet ? number_format($jurnal->debet, 0, ',', '.') : '-' }}</td>
            <td class="text-right">{{ $jurnal->kredit ? number_format($jurnal->kredit, 0, ',', '.') : '-' }}</td>
          </tr>
          @endforeach
          <tr class="bg-light font-weight-bold">
            <td colspan="4" class="text-right">TOTAL</td>
            <td class="text-right">{{ number_format($totalDebet, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($totalKredit, 0, ',', '.') }}</td>
          </tr>
        </tbody>
      </table>
      @if($memo)
      <div class="alert alert-info mt-2 mb-0"><i class="fas fa-sticky-note mr-1"></i> <strong>Memo:</strong> {{ $memo }}</div>
      @endif
      @endif
    </div>

    {{-- ======== EDIT MODE ======== --}}
    <div id="edit-mode" style="display:none;">
      <div id="edit-alert"></div>
      <form id="form-edit-jurnal">
        @csrf
        <div class="row mb-3">
          <div class="col-md-4">
            <label><i class="far fa-calendar-alt mr-1"></i> Tanggal Transaksi</label>
            <input type="text" id="edit-date-display" class="form-control" autocomplete="off" readonly
              value="{{ \Carbon\Carbon::parse(optional($jurnals->first())->date)->format('d/m/Y') }}">
            <input type="hidden" id="edit-date-hidden" name="date"
              value="{{ optional($jurnals->first())->date }}">
          </div>
          <div class="col-md-8">
            <label><i class="fas fa-sticky-note mr-1"></i> Memo</label>
            <input type="text" name="memo" class="form-control" value="{{ $memo }}" placeholder="Memo (opsional)">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered" id="edit-rows-table">
            <thead class="thead-dark">
              <tr>
                <th style="width:4%">#</th>
                <th style="width:27%;">Akun</th>
                <th style="width:26%;">Deskripsi</th>
                <th style="width:16%;">Debet</th>
                <th style="width:16%;">Kredit</th>
                <th style="width:5%;"></th>
              </tr>
            </thead>
            <tbody id="edit-rows-body">
              @foreach($jurnals as $index => $jurnal)
              <tr class="edit-row">
                <td class="row-num">{{ $index + 1 }}
                  <input type="hidden" class="row-id" value="{{ $jurnal->id }}">
                </td>
                <td>
                  <select class="form-control form-control-sm row-akun" required>
                    @foreach($akunList as $akun)
                      <option value="{{ $akun->akun_code }}" {{ $jurnal->id_akun == $akun->akun_code ? 'selected' : '' }}>
                        {{ $akun->akun_code }} - {{ $akun->name }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm row-desc"
                    value="{{ $jurnal->description }}" placeholder="Deskripsi">
                </td>
                <td>
                  <input type="number" class="form-control form-control-sm edit-debet row-debet"
                    value="{{ $jurnal->debet }}" min="0" step="0.01">
                </td>
                <td>
                  <input type="number" class="form-control form-control-sm edit-kredit row-kredit"
                    value="{{ $jurnal->kredit }}" min="0" step="0.01">
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-danger btn-sm btn-delete-row" title="Hapus baris">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="font-weight-bold" id="edit-total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td><input type="text" id="edit-total-debet" class="form-control form-control-sm font-weight-bold" readonly></td>
                <td><input type="text" id="edit-total-kredit" class="form-control form-control-sm font-weight-bold" readonly></td>
              </tr>
              <tr id="edit-balance-row">
                <td colspan="5">
                  <div id="edit-balance-alert" class="mb-0 py-1 px-2 rounded text-center font-weight-bold" style="font-size:0.9rem;"></div>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3">
          <button type="button" id="btn-add-row" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Tambah Baris
          </button>
          <div>
            <button type="button" id="btn-cancel-edit" class="btn btn-secondary mr-2">
              <i class="fas fa-times mr-1"></i> Batal
            </button>
            <button type="submit" id="btn-save-edit" class="btn btn-success" disabled>
              <i class="fas fa-save mr-1"></i> Simpan Perubahan
            </button>
          </div>
        </div>
      </form>
    </div>

  </div>
</section>

<script>
(function() {
  // Toggle view/edit mode
  document.getElementById('btn-toggle-edit').addEventListener('click', function() {
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-mode').style.display = '';
    this.style.display = 'none';
    recalcEditTotals(); // hitung saldo saat edit mode dibuka
  });
  document.getElementById('btn-cancel-edit').addEventListener('click', function() {
    document.getElementById('edit-mode').style.display = 'none';
    document.getElementById('view-mode').style.display = '';
    document.getElementById('btn-toggle-edit').style.display = '';
    document.getElementById('edit-alert').innerHTML = '';
  });

  // Datepicker for edit date
  if ($.fn.datepicker) {
    $('#edit-date-display').datepicker({
      format: 'dd/mm/yyyy',
      todayHighlight: true,
      autoclose: true,
    }).on('changeDate', function(e) {
      var d = e.date;
      var yyyy = d.getFullYear();
      var mm = String(d.getMonth() + 1).padStart(2, '0');
      var dd = String(d.getDate()).padStart(2, '0');
      $('#edit-date-hidden').val(yyyy + '-' + mm + '-' + dd);
    });
  }

  // Akun options HTML (built from server-side data)
  var akunOptions = (function() {
    var opts = '';
    @foreach($akunList as $akun)
    opts += '<option value="{{ $akun->akun_code }}">{{ $akun->akun_code }} - {{ addslashes($akun->name) }}</option>';
    @endforeach
    return opts;
  })();

  // Renumber rows
  function renumberRows() {
    document.querySelectorAll('#edit-rows-body .edit-row').forEach(function(tr, i) {
      tr.querySelector('.row-num').childNodes[0].textContent = (i + 1);
    });
  }

  // Add new blank row
  document.getElementById('btn-add-row').addEventListener('click', function() {
    var tr = document.createElement('tr');
    tr.className = 'edit-row';
    tr.innerHTML =
      '<td class="row-num">?<input type="hidden" class="row-id" value=""></td>' +
      '<td><select class="form-control form-control-sm row-akun" required>' + akunOptions + '</select></td>' +
      '<td><input type="text" class="form-control form-control-sm row-desc" placeholder="Deskripsi"></td>' +
      '<td><input type="number" class="form-control form-control-sm edit-debet row-debet" value="0" min="0" step="0.01"></td>' +
      '<td><input type="number" class="form-control form-control-sm edit-kredit row-kredit" value="0" min="0" step="0.01"></td>' +
      '<td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-delete-row" title="Hapus baris"><i class="fas fa-trash"></i></button></td>';
    document.getElementById('edit-rows-body').appendChild(tr);
    renumberRows();
    recalcEditTotals();
  });

  // Delete row
  document.getElementById('edit-rows-body').addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-delete-row');
    if (!btn) return;
    var rows = document.querySelectorAll('#edit-rows-body .edit-row');
    if (rows.length <= 1) {
      alert('Minimal harus ada 1 baris.');
      return;
    }
    btn.closest('tr').remove();
    renumberRows();
    recalcEditTotals();
  });

  // Live recalc debet/kredit totals
  function recalcEditTotals() {
    var totalD = 0, totalK = 0;
    document.querySelectorAll('#edit-rows-table .edit-debet').forEach(function(el) {
      totalD += parseFloat(el.value) || 0;
    });
    document.querySelectorAll('#edit-rows-table .edit-kredit').forEach(function(el) {
      totalK += parseFloat(el.value) || 0;
    });

    var fmtD = totalD.toLocaleString('id-ID', {minimumFractionDigits: 2});
    var fmtK = totalK.toLocaleString('id-ID', {minimumFractionDigits: 2});
    document.getElementById('edit-total-debet').value  = fmtD;
    document.getElementById('edit-total-kredit').value = fmtK;

    var balanceEl = document.getElementById('edit-balance-alert');
    var saveBtn   = document.getElementById('btn-save-edit');
    var diff = Math.abs(totalD - totalK);

    if (diff < 0.01) {
      balanceEl.className = 'mb-0 py-1 px-2 rounded text-center font-weight-bold bg-success text-white';
      balanceEl.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Debet = Kredit &mdash; Jurnal seimbang';
      saveBtn.disabled = false;
    } else {
      var selisih = (totalD - totalK).toLocaleString('id-ID', {minimumFractionDigits: 2});
      balanceEl.className = 'mb-0 py-1 px-2 rounded text-center font-weight-bold bg-danger text-white';
      balanceEl.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Selisih: ' + selisih + ' &mdash; Debet harus sama dengan Kredit';
      saveBtn.disabled = true;
    }
  }

  // Attach listeners to debet/kredit inputs
  document.getElementById('edit-rows-table').addEventListener('input', function(e) {
    if (e.target.classList.contains('edit-debet') || e.target.classList.contains('edit-kredit')) {
      recalcEditTotals();
    }
  });

  // Default to 0 when input is left blank
  document.getElementById('edit-rows-table').addEventListener('blur', function(e) {
    if (e.target.classList.contains('edit-debet') || e.target.classList.contains('edit-kredit')) {
      if (e.target.value === '' || e.target.value === null) {
        e.target.value = 0;
        recalcEditTotals();
      }
    }
  }, true);

  // AJAX submit
  document.getElementById('form-edit-jurnal').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('btn-save-edit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Menyimpan...';

    // Build rows from DOM (not serializeArray, since names were removed)
    var formData = {};
    var memoEl = document.querySelector('#form-edit-jurnal input[name="memo"]');
    var csrfEl = document.querySelector('#form-edit-jurnal input[name="_token"]');
    formData.memo = memoEl ? memoEl.value : '';
    formData._token = csrfEl ? csrfEl.value : '';
    formData.date = document.getElementById('edit-date-hidden').value;
    formData.rows = [];
    document.querySelectorAll('#edit-rows-body .edit-row').forEach(function(tr) {
      formData.rows.push({
        id:          tr.querySelector('.row-id').value,
        id_akun:     tr.querySelector('.row-akun').value,
        description: tr.querySelector('.row-desc').value,
        debet:       tr.querySelector('.row-debet').value,
        kredit:      tr.querySelector('.row-kredit').value,
      });
    });

    $.ajax({
      url: '/jurnal/updatebycode/{{ $code }}',
      type: 'POST',
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      data: JSON.stringify(formData),
      success: function(res) {
        document.getElementById('edit-alert').innerHTML =
          '<div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i>' + res.message + '</div>';
        // Refresh the modal content after short delay
        setTimeout(function() {
          $.ajax({
            url: '/jurnal/show/{{ $code }}',
            type: 'GET',
            success: function(html) {
              $('#modal-jurnal-content').html(html);
            }
          });
        }, 1000);
      },
      error: function(xhr) {
        var msg = 'Gagal menyimpan perubahan.';
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        document.getElementById('edit-alert').innerHTML =
          '<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i>' + msg + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan Perubahan';
      }
    });
  });
})();
</script>

