@extends('layout.main')
@section('title', 'Kas Keluar')
@section('content')

<div class="container-fluid">
  <div class="card shadow-sm">
    <div class="card-header-custom" style="background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); padding: 18px 24px;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-0 font-weight-bold text-white" style="letter-spacing: 1px;">
            <i class="fas fa-money-bill-wave mr-2"></i>KAS KELUAR
          </h4>
          <small class="text-white" style="opacity: 0.85;">Pencatatan transaksi kas keluar</small>
        </div>
        <div class="dropdown">
          <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="transactionDropdown" data-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-exchange-alt mr-1"></i> Transaksi Lainnya
          </button>
          <ul class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="transactionDropdown">
            <li><a class="dropdown-item" href="/jurnal/kasmasuk"><i class="fas fa-hand-holding-usd text-success mr-2"></i> Kas Masuk</a></li>
            <li><a class="dropdown-item" href="/jurnal/kaskeluar"><i class="fas fa-money-bill-wave text-danger mr-2"></i> Kas Keluar</a></li>
            <li><a class="dropdown-item" href="/jurnal/transferkas"><i class="fas fa-exchange-alt text-primary mr-2"></i> Transfer Kas</a></li>
            <li><a class="dropdown-item" href="/jurnal/general"><i class="fas fa-file-invoice text-secondary mr-2"></i> Transaksi General</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="card-body">
    <form id="transaksiForm" method="POST" action="/jurnal/kaskeluartransaction">
      @csrf
      <input type="hidden" name="type" class="form-control" value="kaskeluar">
      <div class="row mb-3">
        <div class="col-md-3">
          <label for="kas" class="form-label"> Bayar Dari</label>
          <select name="akunkredit" id="akunkredit" class="form-control select2" required>
            <option value="">-- Pilih Akun --</option>
            @foreach($akunkredit as $akunk)
            <option value="{{ $akunk->akun_code }}">{{ $akunk->akun_code }} - {{ $akunk->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label for="date_display" class="form-label">Tgl Transaksi</label>
          <input type="text" class="form-control" id="date_display" value="{{ now()->format('d/m/Y') }}" autocomplete="off" required placeholder="dd/mm/yyyy">
          <input type="hidden" name="date" id="date_hidden" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="col-md-3">
          <label for="noTransaksi" class="form-label">No Transaksi</label>
          <input type="text" class="form-control" id="noTransaksi" placeholder="[Auto]" disabled>
        </div>
      </div>

      <div class="row mb-6">


        <div class="col-md-3">
          <label for="kas" class="form-label">Penerima</label>
          
          <select name="category" id="category" class="form-control select2" required>
           <option value="" selected disabled>-- Pilih Kategori --</option>
           <option value="none">None</option>
           <option value="contact">Contact</option>
           <option value="customer">Customer</option>
           <option value="employee">Employee</option>
         </select>

       </select>
     </div>
     <div class="col-md-3">
      <label for="yangMembayar" class="form-label" >Name</label>


      <input type="hidden" name="contact_id" class="form-control" placeholder="ID">
      <input type="text" name="name" class="form-control" placeholder="name" readonly>
      
      <div class="invalid-feedback" id="nameError" style="display:none;">Nama penerima wajib diisi!</div>
    </div>

  </div>



  <table class="table table-bordered" style="margin-top: 1.5rem;">
    <thead class="table-light bg-light">
      <tr>
        <th class="col-md-3">Pembayaran Untuk</th>
        <th class="col-md-6">Deskripsi</th>
        <th class="col-md-2">Jumlah</th>
        <th class="col-md-1"></th>
      </tr>
    </thead>
    <tbody id="transaksiTable">
      <tr>
        <td>
          <select name="akundebet[]" class="form-control select2" required>
            <option value="">-- Pilih Akun --</option>
            @foreach($akundebet as $akund)
            <option value="{{ $akund->akun_code }}">{{ $akund->akun_code }} - {{ $akund->name }}</option>
            @endforeach
          </select>
        </td>
        <td><input type="text" name="description[]" class="form-control" placeholder="Deskripsi"></td>
        <td><input type="text" name="debet[]" class="form-control jumlah" placeholder="0" inputmode="numeric" autocomplete="off" required></td>
        <td><button type="button" class="btn btn-danger btn-sm delete-row">-</button></td>
      </tr>
    </tbody>
    <tr>
      <td colspan="2" class="text-end"><h5>Total:</h5></td>
      <td colspan="2">
        <h5>
          <input type="text" readonly name="kredit" id="totalAmount" class="form-control jumlah" placeholder="0"></h5>
        </td>
      </tr>
    </table>

    <button type="button" class="btn btn-primary btn-sm" id="addRow">+ Tambah Data</button>

    <div class="row my-3">
      <div class="col-md-12">
        <label for="memo" class="form-label">Memo</label>
        <textarea class="form-control" name="memo" id="memo" rows="3"></textarea>
      </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
      <button type="reset" class="btn btn-danger">Batal</button>
      <button type="submit" class="btn btn-success">Buat Pengeluaran</button>
    </div>
  </form>

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

</div>
@endsection
@section('footer-scripts')
<script>
$(document).ready(function() {
  $('#date_display').datepicker({
    format: 'dd/mm/yyyy',
    todayHighlight: true,
    autoclose: true,
  }).on('changeDate', function(e) {
    var d = e.date;
    var yyyy = d.getFullYear();
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    $('#date_hidden').val(yyyy + '-' + mm + '-' + dd);
  });

  // Izinkan ketik tanggal manual (format: dd/mm/yyyy)
  $('#date_display').on('input', function() {
    var val = $(this).val().replace(/[^0-9\/]/g, '');
    if (val.length === 2 && val.indexOf('/') === -1) val += '/';
    else if (val.length === 5 && val.split('/').length - 1 === 1) val += '/';
    $(this).val(val);
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(val)) {
      var parts = val.split('/');
      var dNum = parseInt(parts[0], 10);
      var mNum = parseInt(parts[1], 10) - 1;
      var yNum = parseInt(parts[2], 10);
      var dateObj = new Date(yNum, mNum, dNum);
      if (dateObj.getFullYear() === yNum && dateObj.getMonth() === mNum && dateObj.getDate() === dNum) {
        $('#date_display').datepicker('update', val);
        $('#date_hidden').val(yNum + '-' + String(mNum + 1).padStart(2, '0') + '-' + String(dNum).padStart(2, '0'));
      }
    }
  });
});
</script>
<script>
 document.addEventListener('DOMContentLoaded', function () {
  const transaksiTable = document.getElementById('transaksiTable');
  const addRowBtn = document.getElementById('addRow');
  const totalAmountEl = document.getElementById('totalAmount');

    // Tambah baris baru
  addRowBtn.addEventListener('click', function () {
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
    <td>
    <select name="akundebet[]" class="form-control select2" required>
    <option value="">-- Pilih Akun --</option>
    ${generateAkunOptions()}
    </select>
    </td>
    <td><input type="text" name="description[]" class="form-control" placeholder="Deskripsi"></td>
    <td><input type="text" name="debet[]" class="form-control jumlah" placeholder="0" inputmode="numeric" autocomplete="off" required></td>
    <td><button type="button" class="btn btn-danger btn-sm delete-row">-</button></td>
    `;
    transaksiTable.appendChild(newRow);
    updateTotal();
  });

    // Hapus baris
  transaksiTable.addEventListener('click', function (e) {
    if (e.target.classList.contains('delete-row')) {
      e.target.closest('tr').remove();
      updateTotal();
    }
  });

    // Helper: format dan parse ribuan
  function formatRibuan(num) {
    if (isNaN(num) || num === 0) return '0';
    return num.toLocaleString('id-ID');
  }
  function parseRibuan(val) {
    if (typeof val === 'number') return val;
    return parseFloat(String(val).replace(/\./g, '').replace(',', '.')) || 0;
  }

    // Format ribuan saat input + hitung total
  transaksiTable.addEventListener('input', function (e) {
    if (e.target.name === 'debet[]') {
      var raw = e.target.value.replace(/[^0-9,]/g, '');
      if (!raw.endsWith(',')) {
        var num = parseRibuan(raw);
        e.target.value = raw === '' ? '' : (num > 0 ? num.toLocaleString('id-ID') : '0');
      } else {
        e.target.value = raw;
      }
      updateTotal();
    }
  });

    // Blur: kosong → 0
  transaksiTable.addEventListener('blur', function (e) {
    if (e.target.name === 'debet[]') {
      if (e.target.value.trim() === '') { e.target.value = '0'; updateTotal(); }
    }
  }, true);

  function updateTotal() {
    let total = 0;
    document.querySelectorAll('input[name="debet[]"]').forEach(input => {
      total += parseRibuan(input.value);
    });
    totalAmountEl.value = formatRibuan(total);
  }

    // Fungsi untuk mengambil daftar akun
  function generateAkunOptions() {
    let options = '';
    @foreach($akundebet as $akund)
    options += `<option value="{{ $akund->akun_code }}">{{ $akund->akun_code }} - {{ $akund->name }}</option>`;
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
                    `<li class="ddropdown-hover list-group-item list-group-item-action contact-item" data-id="${contact.contact_id}" data-name="${contact.name}">${contact.contact_id}  | ${contact.category}  |
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
                  $('#employeeList').append('<li class="list-group-item text-danger">Data Tidak Ditemukann</li>');
                  return;
                }
                data.forEach(user => {
                  $('#employeeList').append(
                    `<li class="dropdown-hover list-group-item list-group-item-action user-item" data-id="${user.id}" data-name="${user.name}">
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
        title: "user Dipilih",
        text: `CID: ${userId} | Nama: ${userName}`,
        icon: "success",
        confirmButtonText: "OK"
      });
      $('#employeeModal').modal('hide');
    });



  });


$('#transaksiForm').on('submit', function(e) {
  // Strip pemisah ribuan sebelum submit
  $('input[name="debet[]"]').each(function() {
    $(this).val(parseFloat(String($(this).val()).replace(/\./g, '').replace(',', '.')) || 0);
  });
  if ($('input[name="name"]').val().trim() === "") {
        e.preventDefault();
        Swal.fire({
          title: 'Error!',
          text: 'Kolom Nama Penerima wajib diisi!',
          icon: 'error',
          confirmButtonText: 'OK'
        });
        $('input[name="name"]').focus();
        return false;
      }
    });

    // Enter key support untuk semua modal pencarian
    $('#searchCustomerText').on('keypress', function(e) {
      if (e.which === 13) { e.preventDefault(); $('#searchCustomer').click(); }
    });
    $('#searchcontactText').on('keypress', function(e) {
      if (e.which === 13) { e.preventDefault(); $('#searchcontact').click(); }
    });
    $('#searchemployeeText').on('keypress', function(e) {
      if (e.which === 13) { e.preventDefault(); $('#searchemployee').click(); }
    });

    // Auto focus saat modal dibuka
    $('#customerModal').on('shown.bs.modal', function() {
      $('#searchCustomerText').val('').focus();
    });
    $('#contactModal').on('shown.bs.modal', function() {
      $('#searchcontactText').val('').focus();
    });
    $('#employeeModal').on('shown.bs.modal', function() {
      $('#searchemployeeText').val('').focus();
    });

    // Reset category jika ditutup tanpa pilih
    $('#customerModal, #contactModal, #employeeModal').on('hidden.bs.modal', function () {
      if ($('input[name="name"]').val() === '') {
        $('#category').val('none').trigger('change');
      }
    });
  </script>

  @endsection
