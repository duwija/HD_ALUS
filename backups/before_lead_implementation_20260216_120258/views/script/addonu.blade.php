



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







</script>