{{--
  Cetak struk thermal. Android WebView (aplikasi) dan sebagian browser bawaan HP
  (Mi Browser, dll.) mengabaikan window.print() — dialog cetak tidak pernah muncul.
  Di sana struk ditawarkan untuk dibuka di Chrome lewat intent:// lalu dicetak.
--}}
@php
  // Link bertanda tangan (30 menit) supaya Chrome bisa membuka struk tanpa login.
  $strukBrowserUrl = \App\Http\Controllers\ThermalStrukController::signedUrl(
      $strukType ?? 'suminvoice', (string) request()->route('id')
  ) ?? url()->full();
@endphp
<div id="wv-print-help" class="no-print" style="display:none;max-width:320px;margin:10px auto;padding:10px;font:13px/1.4 sans-serif;font-weight:400;background:#fff3cd;border:1px solid #e0c36b;border-radius:6px;text-align:center">
  Browser / aplikasi ini tidak bisa mencetak langsung.<br>
  <a id="wv-open-browser" href="#" style="display:inline-block;margin:8px 0 4px;padding:8px 14px;background:#1a73e8;color:#fff;border-radius:4px;text-decoration:none;font-weight:700">Buka di Chrome &amp; Cetak</a><br>
  <button type="button" id="wv-copy-link" style="font:12px sans-serif;padding:4px 10px;cursor:pointer">Salin link struk</button>
  <div id="wv-copy-msg" style="font-size:11px;color:#555;margin-top:4px"></div>
</div>
<script>
  (function () {
    var ua = navigator.userAgent;
    var isAndroid = /Android/i.test(ua);
    // Diketahui mengabaikan window.print(): WebView aplikasi ("; wv)"), Mi Browser, browser
    // bawaan Oppo/Vivo, UC, serta in-app browser Facebook/Instagram/Line.
    var noPrintUA = /;\s*wv\)|MiuiBrowser|HeyTapBrowser|VivoBrowser|UCBrowser|FBAN|FBAV|Instagram|\bLine\//i.test(ua);
    var url = @json($strukBrowserUrl);
    // package=com.android.chrome: kalau tanpa package, Android bisa membuka browser bawaan yang sama lagi.
    var intentUrl = 'intent://' + url.replace(/^https?:\/\//, '') +
      '#Intent;scheme=' + url.split(':')[0] + ';action=android.intent.action.VIEW;package=com.android.chrome;S.browser_fallback_url=' +
      encodeURIComponent('https://play.google.com/store/apps/details?id=com.android.chrome') + ';end';

    var printDialogSeen = false;
    window.addEventListener('beforeprint', function () { printDialogSeen = true; });
    if (window.matchMedia) {
      var mq = window.matchMedia('print');
      var onMq = function (e) { if (e.matches) printDialogSeen = true; };
      mq.addListener ? mq.addListener(onMq) : mq.addEventListener('change', onMq);
    }

    function showHelp() { document.getElementById('wv-print-help').style.display = 'block'; }
    function openInBrowser() { window.location.href = intentUrl; }

    function tryPrint() {
      if (noPrintUA) { showHelp(); return; }
      printDialogSeen = false;
      try { window.print(); } catch (e) { showHelp(); return; }
      // Browser Android lain yang diam saja saat window.print(): tak ada event beforeprint → tawarkan Chrome.
      if (isAndroid) {
        setTimeout(function () { if (!printDialogSeen) showHelp(); }, 1500);
      }
    }

    window.printStruk = tryPrint;

    document.getElementById('wv-open-browser').addEventListener('click', function (e) {
      e.preventDefault();
      openInBrowser();
    });

    document.getElementById('wv-copy-link').addEventListener('click', function () {
      var msg = document.getElementById('wv-copy-msg');
      var done = function () { msg.textContent = 'Link disalin, tempel di Chrome lalu cetak.'; };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(done, function () { prompt('Salin link ini:', url); });
      } else {
        prompt('Salin link ini:', url);
      }
    });

    window.addEventListener('load', tryPrint);
  })();
</script>
