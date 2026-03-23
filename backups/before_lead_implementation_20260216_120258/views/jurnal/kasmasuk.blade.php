@extends('layout.main')
@section('title', 'Kas Masuk')
@section('content')
<section class="content-header">
  <div class="card card-primary card-outline">
    <div class="card-header row mb-12 card-title">


      <div class="mb-4">
        <strong>Transaksi - Kas Masuk</strong>
      </div> 
      <div class="ml-auto ">
        <div class="nav-item dropdown ">
          <button class="btn btn-primary dropdown-toggle" type="button" id="transactionDropdown" data-toggle="dropdown" aria-expanded="false">
           Transaksi
         </button>
         <ul class="dropdown-menu" aria-labelledby="transactionDropdown">
          <li><a class="dropdown-item" href="/jurnal/kasmasuk"><i class="fas fa-hand-holding-usd"></i> Kas Masuk</a></li>
          <li><a class="dropdown-item" href="/jurnal/kaskeluar"><i class="fas fa-money-bill-wave"></i> Kas Keluar</a></li>
          <li><a class="dropdown-item" href="/jurnal/transferkas"><i class="fas fa-random"></i> Transfer Kas</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="card-body">
    <form id="transaksiForm" method="POST" action="/jurnal/kasmasuktransaction" class="d-inline">
      @csrf
      <input type="hidden" name="type" class="form-control" value="kasmasuk">
      <div class="row mb-3">
        <div class="col-md-3">
          <label for="kas" class="form-label">Setor Ke</label>
          <select name="akundebet" id="akundebet" class="form-control select2" required>
            <option value="">-- Pilih Akun --</option>
            @foreach($akundebet as $akund)
            <option value="{{ $akund->akun_code }}">{{ $akund->akun_code }} - {{ $akund->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label for="date" class="form-label">Tgl Transaksi</label>
          <input type="date" name="date" class="form-control" id="date" value="{{ now()->format('Y-m-d') }}" required>
        </div>
        <div class="col-md-3">
          <label for="noTransaksi" class="form-label">No Transaksi</label>
          <input type="text" class="form-control" id="noTransaksi" placeholder="[Auto]" disabled>
        </div>
      </div>

      <div class="row mb-6">


        <div class="col-md-3">
          <label for="kas" class="form-label">Yang Membayar</label>
          <select name="category" id="category" class="form-control select2" required>
           <option value="" selected disabled>-- Pilih Kategori --</option>
           <option value="none">None</option>
           <option value="contact">Contact</option>
           <option value="customer">Customer</option>
           <option value="employee">Employee</option>
         </select>
       </div>
       <div class="col-md-3">
        <label for="yangMembayar" class="form-label" >Name</label>


        <input type="hidden" name="contact_id" class="form-control" placeholder="ID">
        <input type="text" name="name" class="form-control" placeholder="name" readonly>
        <div class="invalid-feedback" id="nameError" style="display:none;">Nama penerima wajib diisi!</div>
      </div>



    </div>



    <table class="table table-bordered">
      <thead class="table-light bg-light">
        <tr>
          <th class="col-md-3">Terima Dari</th>
          <th class="col-md-6">Deskripsi</th>
          <th class="col-md-2">Jumlah</th>
          <th class="col-md-1"></th>
        </tr>
      </thead>
      <tbody id="transaksiTable">
        <tr>
          <td>
            <select name="akunkredit[]" class="form-control select2" required>
              <option value="">-- Pilih Akun --</option>
              @foreach($akunkredit as $akunk)
              <option value="{{ $akunk->akun_code }}">{{ $akunk->akun_code }} - {{ $akunk->name }}</option>
              @endforeach
            </select>
          </td>
          <td><input type="text" name="description[]" class="form-control" placeholder="Deskripsi"></td>
          <td><input type="number" name="kredit[]" class="form-control jumlah" placeholder="Rp. 0,00" min="0" step="0.01" required></td>
          <td><button type="button" class="btn btn-danger btn-sm delete-row">-</button></td>
        </tr>
      </tbody>
      <tr>
        <td colspan="2" class="text-end"><h5>Total:</h5></td>
        <td colspan="2">
          <h5>
            <input type="number" readonly name="debet" id="totalAmount" class="form-control jumlah" placeholder="Rp. 0,00" min="0" step="0.01" required></h5>
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
        <button type="submit" class="btn btn-success">Buat Penerimaan</button>
      </div>
    </form>

  </div>
  <!-- </div> -->




  <!-- Modal untuk search customer -->
  <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="customerModalLabel">Cari Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-10">
              <input type="text" id="searchCustomerText" class="form-control" placeholder="Enter Id or Name">
            </div>
            <div class="col-md-2">
              <button type="button" id="searchCustomer" class="btn btn-primary">Find</button>
            </div>
          </div>
        </div>
        <ul id="customerList" class="list-group m-2 p-1"></ul>
      </div>
    </div>
  </div>


  <!-- Modal untuk search contact -->
  <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="contactModalLabel">Cari contact</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-10">
              <input type="text" id="searchcontactText" class="form-control " placeholder="Enter Id or Name">
            </div>
            <div class="col-md-2">
              <button type="button" id="searchcontact" class="btn btn-primary">Find</button>
            </div>
          </div>
        </div>
        <ul id="contactList" class="list-group m-2 p-1 "></ul>
      </div>
    </div>
  </div>

  <div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeModalLabel">Cari employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-10">
              <input type="text" id="searchemployeeText" class="form-control " placeholder="Enter Id or Name">
            </div>
            <div class="col-md-2">
              <button type="button" id="searchemployee" class="btn btn-primary">Find</button>
            </div>
          </div>
        </div>
        <ul id="employeeList" class="list-group m-2 p-1 "></ul>
      </div>
    </div>
  </div>


</div>

</section>
@endsection
@section('footer-scripts')
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
    <select name="akunkredit[]" class="form-control select2" required>
    <option value="">-- Pilih Akun --</option>
    ${generateAkunOptions()}
    </select>
    </td>
    <td><input type="text" name="description[]" class="form-control" placeholder="Deskripsi"></td>
    <td><input type="number" name="kredit[]" class="form-control jumlah" placeholder="Rp. 0,00" min="0" step="0.01" required></td>
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

    // Hitung total saat input berubah
  transaksiTable.addEventListener('input', function (e) {
    if (e.target.classList.contains('jumlah')) {
      updateTotal();
    }
  });

  function updateTotal() {
    let total = 0;
    document.querySelectorAll('input[name="kredit[]"]').forEach(input => {
      total += parseFloat(input.value) || 0;
    });
    totalAmountEl.value = total;
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
        title: "contact Dipilih",
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
        title: "user Dipilih",
        text: `CID: ${userId} | Nama: ${userName}`,
        icon: "success",
        confirmButtonText: "OK"
      });
      $('#employeeModal').modal('hide');
    });



  });

$('#transaksiForm').on('submit', function(e) {
  if ($('input[name="name"]').val().trim() === "") {
        e.preventDefault(); // Cegah submit
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
  </script>

  @endsection
