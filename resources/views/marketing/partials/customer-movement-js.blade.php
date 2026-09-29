<script>
$(function () {
  $('.select2').select2({ width: '100%', allowClear: true });

  $('.period-quick').on('click', function () {
    function ymd(d) {
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    var range = String($(this).data('range'));
    var today = new Date();
    var from;
    if (range === 'month') {
      from = new Date(today.getFullYear(), today.getMonth(), 1);
    } else if (range === 'year') {
      from = new Date(today.getFullYear(), 0, 1);
    } else {
      // N bulan terakhir, termasuk bulan berjalan
      from = new Date(today.getFullYear(), today.getMonth() - (parseInt(range, 10) - 1), 1);
    }
    $('#period_from').val(ymd(from));
    $('#period_to').val(ymd(today));
  });

  // ── Line chart pergerakan customer ───────────────────────────────────────
  var movement = @json($movement);
  var keys = Object.keys(movement.periods);
  // Warna sama dengan pie di tab Umur Customer; "Tidak Jadi" putus-putus +
  // penanda segitiga karena merah-hijau sulit dibedakan bagi buta warna.
  var lines = {
    'new':           { label: 'Customer Baru',    color: '#2a78d6', dash: [],     point: 'circle' },
    'del_terminate': { label: 'Berhenti',         color: '#e34948', dash: [],     point: 'rect' },
    'del_cancel':    { label: 'Tidak Jadi',       color: '#008300', dash: [6, 4], point: 'triangle' },
    'del_other':     { label: 'Tanpa Keterangan', color: '#8a8985', dash: [2, 3], point: 'circle' }
  };

  function drawLines(canvasId, seriesKeys) {
    var maxValue = Math.max.apply(null, seriesKeys.map(function (s) {
      return Math.max.apply(null, keys.map(function (k) { return movement.series[s][k]; }));
    }));

    new Chart(document.getElementById(canvasId).getContext('2d'), {
      type: 'line',
      data: {
        labels: keys.map(function (k) { return movement.periods[k]; }),
        datasets: seriesKeys.map(function (s) {
          var l = lines[s];
          return {
            label: l.label,
            data: keys.map(function (k) { return movement.series[s][k]; }),
            borderColor: l.color,
            backgroundColor: l.color,
            borderWidth: 2,
            borderDash: l.dash,
            fill: false,
            lineTension: 0,
            pointStyle: l.point,
            pointRadius: keys.length > 40 ? 2 : 4,
            pointHoverRadius: 6,
            pointHitRadius: 10,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 1
          };
        })
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        // Satu seri tidak perlu legend; judul chart sudah menamainya
        legend: { display: seriesKeys.length > 1, position: 'bottom', labels: { boxWidth: 12, fontColor: '#212529' } },
        tooltips: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function (item, data) {
              return data.datasets[item.datasetIndex].label + ': ' + Number(item.yLabel).toLocaleString('id-ID');
            }
          }
        },
        hover: { mode: 'index', intersect: false },
        scales: {
          xAxes: [{ gridLines: { display: false }, ticks: { fontColor: '#52514e', autoSkip: true, maxRotation: 0 } }],
          yAxes: [{
            gridLines: { color: 'rgba(0,0,0,0.06)', zeroLineColor: 'rgba(0,0,0,0.2)' },
            ticks: {
              beginAtZero: true,
              fontColor: '#52514e',
              // Jumlah customer selalu bulat; nilai kecil pakai langkah 1
              stepSize: maxValue <= 10 ? 1 : undefined,
              suggestedMax: maxValue === 0 ? 5 : undefined,
              callback: function (v) { return v.toLocaleString('id-ID'); }
            }
          }]
        }
      }
    });
  }

  drawLines('movementNewChart', ['new']);
  drawLines('movementDeletedChart', ['del_terminate', 'del_cancel', 'del_other']);
});
</script>
