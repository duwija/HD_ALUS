@extends('layout.main')
@section('title', 'Transaksi General')
@section('content')

<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header-custom">
      <div class="d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-file-invoice mr-2"></i>TRANSAKSI GENERAL</h3>
        <div class="dropdown">
          <button class="btn btn-light dropdown-toggle" type="button" id="transactionDropdown" data-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-plus-circle mr-1"></i> Transaksi Lainnya
          </button>
          <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="transactionDropdown">
            <li><a class="dropdown-item" href="/jurnal/kasmasuk"><i class="fas fa-hand-holding-usd text-success mr-2"></i> Kas Masuk</a></li>
            <li><a class="dropdown-item" href="/jurnal/kaskeluar"><i class="fas fa-money-bill-wave text-danger mr-2"></i> Kas Keluar</a></li>
            <li><a class="dropdown-item" href="/jurnal/transferkas"><i class="fas fa-exchange-alt text-primary mr-2"></i> Transfer Kas</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="card-body">
      <form id="transaksiForm" method="POST" action="/jurnal/generaltransaction">
        @csrf
        
        <!-- Info Transaksi Section -->
        <div class="form-section">
          <h5 class="form-section-title"><i class="fas fa-info-circle mr-2"></i>Informasi Transaksi</h5>
          <div class="row">
            <div class="col-md-4">
              <label for="date" class="form-label"><i class="far fa-calendar-alt mr-1"></i> Tanggal Transaksi</label>
              <input type="date" name="date" class="form-control" id="date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-4">
              <label for="noTransaksi" class="form-label"><i class="fas fa-hashtag mr-1"></i> No Transaksi</label>
              <input type="text" class="form-control" id="noTransaksi" placeholder="[Auto Generate]" disabled>
            </div>
          </div>
        </div>

        <!-- Pihak Terkait Section -->
        <div class="form-section">
          <h5 class="form-section-title"><i class="fas fa-user-friends mr-2"></i>Pihak Terkait</h5>
          <div class="row">
            <div class="col-md-4">
              <label for="category" class="form-label"><i class="fas fa-tag mr-1"></i> Bertransaksi dengan</label>
              <select name="category" id="category" class="form-control select2" required>
                <option value="" selected disabled>-- Pilih Kategori --</option>
                <option value="none">None</option>
                <option value="contact">Contact</option>
                <option value="customer">Customer</option>
                <option value="employee">Employee</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="name" class="form-label"><i class="fas fa-user mr-1"></i> Nama</label>
              <input type="hidden" name="contact_id" class="form-control" placeholder="ID">
              <input type="text" name="name" class="form-control" placeholder="Nama pihak terkait" readonly>
            </div>
          </div>
        </div>

        <!-- Detail Transaksi Section -->
        <div class="form-section">
          <h5 class="form-section-title"><i class="fas fa-list-ul mr-2"></i>Detail Transaksi</h5>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th style="width: 30%;">Akun</th>
                  <th style="width: 35%;">Deskripsi</th>
                  <th style="width: 15%;">Debet</th>
                  <th style="width: 15%;">Kredit</th>
                  <th style="width: 5%;"></th>
                </tr>
              </thead>
              <tbody id="transaksiTable">
                <tr>
                  <td>
                    <select name="akun[]" class="form-control select2" required>
                      <option value="">-- Pilih Akun --</option>
                      @foreach($akundebet as $akund)
                      <option value="{{ $akund->akun_code }}">{{ $akund->akun_code }} - {{ $akund->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><input type="text" name="description[]" class="form-control" placeholder="Keterangan transaksi"></td>
                  <td><input type="number" name="debet[]" class="form-control jumlah" placeholder="0" min="0" step="0.01" required></td>
                  <td><input type="number" name="kredit[]" class="form-control jumlah" placeholder="0" min="0" step="0.01" required></td>
                  <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm delete-row">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot class="total-row">
                <tr>
                  <td colspan="2" class="text-right"><strong>TOTAL:</strong></td>
                  <td>
                    <input type="number" readonly name="totaldebet" id="totalAmountdebet" class="form-control" placeholder="0" min="0" step="0.01" required>
                  </td>
                  <td>
                    <input type="number" readonly name="totalkredit" id="totalAmountkredit" class="form-control" placeholder="0" min="0" step="0.01" required>
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <button type="button" class="btn btn-add-row btn-sm" id="addRow">
            <i class="fas fa-plus mr-1"></i> Tambah Baris
          </button>
        </div>

        <!-- Memo Section -->
        <div class="form-section">
          <h5 class="form-section-title"><i class="fas fa-sticky-note mr-2"></i>Memo</h5>
          <textarea class="form-control" name="memo" id="memo" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-4">
          <button type="reset" class="btn btn-danger">
            <i class="fas fa-times mr-1"></i> Batal
          </button>
          <button type="submit" id='submit' disabled class="btn btn-success">
            <i class="fas fa-save mr-1"></i> Buat Transaksi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal untuk search customer -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #4a90e2; color: white;">
        <h5 class="modal-title" id="customerModalLabel"><i class="fas fa-search mr-2"></i>Cari Customer</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-10">
            <input type="text" id="searchCustomerText" class="form-control" placeholder="Masukkan ID atau Nama Customer">
          </div>
          <div class="col-md-2">
            <button type="button" id="searchCustomer" class="btn btn-primary btn-block">
              <i class="fas fa-search"></i> Cari
            </button>
          </div>
        </div>
        <ul id="customerList" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk search contact -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #4a90e2; color: white;">
        <h5 class="modal-title" id="contactModalLabel"><i class="fas fa-search mr-2"></i>Cari Contact</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-10">
            <input type="text" id="searchcontactText" class="form-control" placeholder="Masukkan ID atau Nama Contact">
          </div>
          <div class="col-md-2">
            <button type="button" id="searchcontact" class="btn btn-primary btn-block">
              <i class="fas fa-search"></i> Cari
            </button>
          </div>
        </div>
        <ul id="contactList" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk search employee -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #4a90e2; color: white;">
        <h5 class="modal-title" id="employeeModalLabel"><i class="fas fa-search mr-2"></i>Cari Employee</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-10">
            <input type="text" id="searchemployeeText" class="form-control" placeholder="Masukkan ID atau Nama Employee">
          </div>
          <div class="col-md-2">
            <button type="button" id="searchemployee" class="btn btn-primary btn-block">
              <i class="fas fa-search"></i> Cari
            </button>
          </div>
        </div>
        <ul id="employeeList" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

<!-- Modal Loading -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
          <span class="sr-only">Loading...</span>
        </div>
        <h5><i class="fas fa-hourglass-half mr-2"></i>Sedang memproses, harap tunggu...</h5>
        <p class="text-muted mb-0">Transaksi sedang disimpan ke sistem</p>
      </div>
    </div>
  </div>
</div>

@endsection

@section('footer-scripts')

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const transaksiTable = document.getElementById('transaksiTable');
    const addRowBtn = document.getElementById('addRow');

    addRowBtn.addEventListener('click', function () {
      const newRow = document.createElement('tr');
      newRow.innerHTML = `
      <td>
      <select name="akun[]" class="form-control select2" required>
      <option value="">-- Pilih Akun --</option>
      ${generateAkunOptions()}
      </select>
      </td>
      <td><input type="text" name="description[]" class="form-control" placeholder="Deskripsi"></td>
      <td><input type="number" name="debet[]" class="form-control jumlah" placeholder="Rp. 0,00" min="0" step="0.01" required></td>
      <td><input type="number" name="kredit[]" class="form-control jumlah" placeholder="Rp. 0,00" min="0" step="0.01" required></td>
      <td><button type="button" class="btn btn-danger btn-sm delete-row">-</button></td>
      `;
      transaksiTable.appendChild(newRow);

  // *** Tambahkan baris ini ***
      $(newRow).find('.select2').select2({
        dropdownParent: $(newRow)
      });

      updateTotal();
    });


    // Hapus baris
    transaksiTable.addEventListener('click', function (e) {
      if (e.target.classList.contains('delete-row')) {
        e.target.closest('tr').remove();
        updateTotal();
      }
    });

    // Hanya satu dari debet atau kredit yang bisa diisi
    transaksiTable.addEventListener('input', function (e) {
      if (e.target.name === 'debet[]') {
        const kreditInput = e.target.closest('tr').querySelector('input[name="kredit[]"]');
        if (e.target.value) {
          kreditInput.value = '';
          kreditInput.readOnly = true;
          kreditInput.value = 0 ;
        } else {
          kreditInput.readOnly = false;
        }
      }

      if (e.target.name === 'kredit[]') {
        const debetInput = e.target.closest('tr').querySelector('input[name="debet[]"]');
        if (e.target.value) {
          debetInput.value = '';
          debetInput.readOnly = true;
          debetInput.value = 0;
        } else {
          debetInput.readOnly = false;
        }
      }

      updateTotal();
    });

    function updateTotal() {
      let totalDebet = 0;
      let totalKredit = 0;

  // Hitung total debet
      document.querySelectorAll('input[name="debet[]"]').forEach(input => {
        totalDebet += parseFloat(input.value) || 0;
      });

  // Hitung total kredit
      document.querySelectorAll('input[name="kredit[]"]').forEach(input => {
        totalKredit += parseFloat(input.value) || 0;
      });

  // Perbarui nilai total debet dan kredit
  document.getElementById('totalAmountdebet').value = totalDebet.toFixed(2); // Format ke 2 desimal
  document.getElementById('totalAmountkredit').value = totalKredit.toFixed(2); // Format ke 2 desimal

  // Cek apakah total debet dan kredit sama
  const submitButton = document.querySelector('button[id=submit]');
  if (totalDebet !== totalKredit) {
    submitButton.classList.add("disabled");
    submitButton.setAttribute("disabled", "disabled"); // Disable the button
  } else {
    submitButton.classList.remove("disabled");
    submitButton.removeAttribute("disabled"); // Enable the button

  }
}

    // Fungsi untuk mengambil daftar akun
function generateAkunOptions() {
  let options = '';
  @foreach($akunkredit as $akunk)
  options += `<option value="{{ $akunk->akun_code }}">{{ $akunk->akun_code }} - {{ $akunk->name }}</option>`;
  @endforeach
  return options;
}


});

</script>
<script>
  $(document).ready(function () {
    // Tampilkan modal jika memilih "Customer"
    $('#category').change(function () {
      $('input[name="name"]').val('');      // Kosongkan input name
      $('input[name="contact"]').val('');  // Kosongkan input contact
      if ($(this).val() === 'none') {
    // Set value contact_id jadi kosong/null
        $('input[name="contact_id"]').val('').prop('readonly', true);
    // Set name jadi "none"
        $('input[name="name"]').val('none').prop('readonly', true);
    // Sembunyikan semua modal pencarian
        $('#customerModal').modal('hide');
        $('#contactModal').modal('hide');
        $('#employeeModal').modal('hide');
      }
      else if ($(this).val() === 'customer') {
        $('#customerModal').modal('show');
      }
      else if ($(this).val() === 'contact') {
        $('#contactModal').modal('show');
      }
      else if ($(this).val() === 'employee') {
        $('#employeeModal').modal('show');
      }
    });

    // Tombol "Find" untuk mulai mencari
    $('#searchCustomer').click(function () {
      let query = $('#searchCustomerText').val();
      if (query.length < 3) {
        alert('Masukkan minimal 3 huruf untuk mencari.');
        return;
      }

      // AJAX Request ke Laravel
      $.ajax({
        url: '/customer/searchforjurnal', // Pastikan route benar
        type: 'POST',
        data: {
          q: query,
          _token: '{{ csrf_token() }}' // Tambahkan CSRF Token
        },
        success: function (data) {
          $('#customerList').html('');
          if (data.length === 0) {
            $('#customerList').append('<li class="list-group-item text-danger">Data Tidak Ditemukan</li><a href="/customer/create"> <div class=" mt-2 btn btn btn-success">Add New Customer</div></a>');
            return;
          }
          data.forEach(customer => {
            $('#customerList').append(
              `<li class="dropdown-hover list-group-item list-group-item-action customer-item" data-id="${customer.customer_id}" data-name="${customer.name}">${customer.customer_id}  |
                ${customer.name}  
                </li>`
                );
          });
        },
        error: function () {
          alert('Terjadi kesalahan saat mencari data.');
        }
      });
    });

    // Pilih customer dari hasil pencarian
    $(document).on('click', '.customer-item', function () {
      let customerName = $(this).data('name');
      let customerId = $(this).data('id');

      $('input[name="name"]').val(customerName);
      $('input[name="contact_id"]').val(customerId);

      Swal.fire({
        title: "Customer Dipilih",
        text: `CID: ${customerId} | Nama: ${customerName}`,
        icon: "success",
        confirmButtonText: "OK"
      });
      $('#customerModal').modal('hide');
    });

    // Tombol "Find" untuk mulai mencari
    $('#searchcontact').click(function () {
      let query = $('#searchcontactText').val();
      if (query.length < 3) {
        alert('Masukkan minimal 3 huruf untuk mencari.');
        return;
      }

      // AJAX Request ke Laravel
      $.ajax({
        url: '/contact/searchforjurnal', // Pastikan route benar
        type: 'POST',
        data: {
          q: query,
          _token: '{{ csrf_token() }}' // Tambahkan CSRF Token
        },
        success: function (data) {
          $('#contactList').html('');
          if (data.length === 0) {
            $('#contactList').append('<li class="list-group-item text-danger">Data Tidak Ditemukan</li><a href="/contact/create"> <div class=" mt-2 btn btn-success">Add New contact</div></a>');
            return;
          }
          data.forEach(contact => {
            $('#contactList').append(
              `<li class="dropdown-hover modal-content  btn btn-primary list-group-item list-group-item-action contact-item" data-id="${contact.contact_id}" data-name="${contact.name}">${contact.contact_id}  | ${contact.category}  |
                ${contact.name}  
                </li>`
                );
          });
        },
        error: function () {
          alert('Terjadi kesalahan saat mencari data.');
        }
      });
    });

    // Pilih contact dari hasil pencarian
    $(document).on('click', '.contact-item', function () {
      let contactName = $(this).data('name');
      let contactId = $(this).data('id');

      $('input[name="name"]').val(contactName);
      $('input[name="contact_id"]').val(contactId);

      Swal.fire({
        title: "Contact Dipilih",
        text: `CID: ${contactId} | Nama: ${contactName}`,
        icon: "success",
        confirmButtonText: "OK"
      });
      $('#contactModal').modal('hide');
    });

    $('#searchemployee').click(function () {
      let query = $('#searchemployeeText').val();
      if (query.length < 3) {
        alert('Masukkan minimal 3 huruf untuk mencari.');
        return;
      }

      // AJAX Request ke Laravel
      $.ajax({
        url: '/user/searchforjurnal', // Pastikan route benar
        type: 'POST',
        data: {
          q: query,
          _token: '{{ csrf_token() }}' // Tambahkan CSRF Token
        },
        success: function (data) {
          $('#employeeList').html('');
          if (data.length === 0) {
            $('#employeeList').append('<li class="list-group-item text-danger">Tidak ada hasil</li>');
            return;
          }
          data.forEach(user => {
            $('#employeeList').append(
              `<li class="dropdown-hover modal-content  btn btn-primary list-group-item list-group-item-action user-item" data-id="${user.id}" data-name="${user.name}">
                ${user.name}  
                </li>`
                );
          });
        },
        error: function () {
          alert('Terjadi kesalahan saat mencari data.');
        }
      });
    });

    $(document).on('click', '.user-item', function () {
      let userName = $(this).data('name');
      let userId = $(this).data('id');

      $('input[name="name"]').val(userName);
      $('input[name="contact_id"]').val(userId);

      Swal.fire({
        title: "User  Dipilih",
        text: `CID: ${userId} | Nama: ${userName}`,
        icon: "success",
        confirmButtonText: "OK"
      });
      $('#employeeModal').modal('hide');
    });
  });
</script>
<script>
// Saat modal contact ditutup, cek apakah ada item yang dipilih
  $('#contactModal').on('hidden.bs.modal', function () {
  // Jika input 'name' kosong, berarti tidak ada yang dipilih
    if($('input[name="name"]').val() === '') {
    // Reset category ke none (atau kosong)
      $('#category').val('none').trigger('change');
    }
  });

  $('#customerModal').on('hidden.bs.modal', function () {
    if($('input[name="name"]').val() === '') {
      $('#category').val('none').trigger('change');
    }
  });
  $('#employeeModal').on('hidden.bs.modal', function () {
    if($('input[name="name"]').val() === '') {
      $('#category').val('none').trigger('change');
    }
  });

</script>
@endsection