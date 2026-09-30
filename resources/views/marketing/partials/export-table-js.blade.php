  // Tombol Excel/CSV/Print untuk tabel ringkasan statis (.export-table), termasuk baris total
  $('.export-table').each(function () {
    var title = $(this).data('export-title');
    // Teks sel tanpa HTML; angka "2,600" / "+110" jadi angka murni agar bisa dihitung di Excel
    var cellText = function (data) {
      var text = $('<div>').html(String(data)).text().replace(/\s+/g, ' ').trim();
      return /^[+-]?[\d,]+$/.test(text) ? text.replace(/[,+]/g, '') : text;
    };
    var exportOptions = { footer: true, format: { body: cellText, footer: cellText, header: cellText } };
    $(this).DataTable({
      dom: "<'px-2 pt-2 pb-1'B>t",
      paging: false,
      ordering: false,
      searching: false,
      info: false,
      buttons: [
        { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', title: title, exportOptions: exportOptions },
        { extend: 'csvHtml5', text: '<i class="fas fa-file-csv"></i> CSV', title: title, exportOptions: exportOptions },
        { extend: 'print', text: '<i class="fas fa-print"></i> Print', title: title, exportOptions: exportOptions }
      ]
    });
  });
