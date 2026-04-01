<script>
  $(document).ready(function() {

    $('#onu_sn').change(function() {
      var onu_sn = $(this).val();
      var olt_id = $('#olt').val();
      
      // Aktifkan tombol submit
      $('#submitBtn').attr('disabled', false);

      // Ajax request untuk mendapatkan ONU ID
      $.ajax({
        url: '/olt/getemptyonuid', // Endpoint URL pada controller
        type: 'GET',
        data: {onu_sn: onu_sn, olt_id: olt_id},
        success: function(response) {
          // Kosongkan dulu dropdown ONU ID
          $('#onu_id').empty();

          // Jika respons tidak kosong
          if (response.length > 0) {
            $.each(response, function(key, value) {
              $('#onu_id').append('<option value="' + value + '">' + value + '</option>');
            });
          } else {
            // Jika tidak ada ONU ID yang tersedia
            $('#onu_id').append('<option value="">No ONU ID available</option>');
          }
        },
        error: function(xhr, status, error) {
          console.log(error);
          // Opsional: tambahkan pesan error ke user
        }
      });
    });

    $('#onu_snx').change(function() {
      var onu_sn = $(this).val();
      var olt_id = $('#olt').val();
      
      // Aktifkan tombol submit
      $('#submitBtn').attr('disabled', false);

      // Ajax request untuk mendapatkan ONU ID
      $.ajax({
        url: '/olt/getemptyonuid', // Endpoint URL pada controller
        type: 'GET',
        data: {onu_sn: onu_sn, olt_id: olt_id},
        success: function(response) {
          // Kosongkan dulu dropdown ONU ID
          $('#onu_idx').empty();

          // Jika respons tidak kosong
          if (response.length > 0) {
            $.each(response, function(key, value) {
              $('#onu_idx').append('<option value="' + value + '">' + value + '</option>');
            });
          } else {
            // Jika tidak ada ONU ID yang tersedia
            $('#onu_idx').append('<option value="">No ONU ID available</option>');
          }
        },
        error: function(xhr, status, error) {
          console.log(error);
          // Opsional: tambahkan pesan error ke user
        }
      });
    });

  });
</script>
