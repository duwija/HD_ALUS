@php
  $companyName = tenant_config('company_name', config('app.name'));
  $address1 = tenant_config('company_address1', env('COMPANY_ADDRESS1'));
  $address2 = tenant_config('company_address2', env('COMPANY_ADDRESS2'));
  $schedule = trim(($ticket->date ? \Carbon\Carbon::parse($ticket->date)->translatedFormat('l, d F Y') : '') . ($ticket->time ? ' · ' . substr($ticket->time, 0, 5) . ' WITA' : ''));
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Surat Tugas {{ $number }}</title>
  <style>
    @page { margin: 22px 34px 38px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; }
    table { border-collapse: collapse; width: 100%; }
    .kop td { vertical-align: middle; }
    .kop .name { font-size: 17px; font-weight: bold; color: #0d47a1; }
    .kop .addr { font-size: 9.5px; color: #4b5563; line-height: 1.35; }
    .kop-line { border-top: 3px solid #0d47a1; border-bottom: 1px solid #0d47a1; height: 2px; margin: 6px 0 10px; }
    .title { text-align: center; font-size: 16px; font-weight: bold; letter-spacing: 3px; text-decoration: underline; margin: 0; }
    .number { text-align: center; font-size: 10.5px; color: #374151; margin: 2px 0 10px; }
    .section { font-size: 11px; font-weight: bold; color: #0d47a1; margin: 10px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #c7d2fe; }
    .grid th, .grid td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; vertical-align: top; }
    .grid th { background: #eef2ff; font-size: 10px; }
    .kv td { padding: 2px 4px; vertical-align: top; }
    .kv td.k { width: 26%; color: #6b7280; }
    .kv td.s { width: 2%; color: #6b7280; }
    .desc { border: 1px solid #e5e7eb; background: #f9fafb; padding: 7px 9px; line-height: 1.45; }
    .desc p { margin: 0 0 4px; }
    .sign { page-break-inside: avoid; }
    .sign td { text-align: center; vertical-align: top; padding-top: 2px; width: 33%; }
    .sign .space { height: 52px; }
    .sign .who { font-weight: bold; text-decoration: underline; }
    .muted { color: #6b7280; }
    .badge { display: inline-block; padding: 1px 7px; border-radius: 8px; background: #e0e7ff; color: #3730a3; font-size: 9.5px; font-weight: bold; }
    .qr td { text-align: center; font-size: 8.5px; color: #6b7280; vertical-align: top; }
    .footer { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 8.5px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 4px; }
  </style>
</head>
<body>

  <div class="footer">
    <table><tr>
      <td>{{ $number }} · Tiket #{{ $ticket->id }}</td>
      <td style="text-align:right">Dicetak {{ now()->translatedFormat('d F Y H:i') }} oleh {{ $printedBy }}</td>
    </tr></table>
  </div>

  {{-- Kop surat --}}
  <table class="kop">
    <tr>
      @if($logo)
        <td style="width:90px"><img src="{{ $logo }}" style="max-width:82px;max-height:60px"></td>
      @endif
      <td>
        <div class="name">{{ $companyName }}</div>
        <div class="addr">
          @if($address1){{ $address1 }}<br>@endif
          @if($address2){{ $address2 }}@endif
        </div>
      </td>
    </tr>
  </table>
  <div class="kop-line"></div>

  <p class="title">SURAT TUGAS</p>
  <p class="number">Nomor: {{ $number }}</p>

  <p style="margin:0 0 6px">Dengan ini menugaskan kepada:</p>
  <table class="grid">
    <tr><th style="width:5%">No</th><th style="width:32%">Nama</th><th style="width:20%">Peran</th><th style="width:23%">Jabatan</th><th>No. HP</th></tr>
    @forelse($officers as $i => $o)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td><strong>{{ $o['name'] }}</strong></td>
        <td>{{ $o['role'] }}</td>
        <td>{{ $o['job'] ?: '-' }}</td>
        <td>{{ $o['phone'] ?: '-' }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted" style="text-align:center">Belum ada petugas yang ditugaskan pada tiket ini.</td></tr>
    @endforelse
  </table>

  <p style="margin:10px 0 0">Untuk melaksanakan pekerjaan dengan rincian sebagai berikut:</p>

  <div class="section">Rincian Pekerjaan</div>
  <table class="kv">
    <tr><td class="k">No. Tiket</td><td class="s">:</td><td><strong>#{{ $ticket->id }}</strong> <span class="badge">{{ $ticket->status }}</span></td></tr>
    <tr><td class="k">Kategori</td><td class="s">:</td><td>{{ optional($ticket->category)->name ?? '-' }}</td></tr>
    <tr><td class="k">Judul</td><td class="s">:</td><td><strong>{{ $ticket->tittle }}</strong></td></tr>
    <tr><td class="k">Jadwal</td><td class="s">:</td><td>{{ $schedule ?: '-' }}</td></tr>
    <tr><td class="k">Pelapor</td><td class="s">:</td><td>{{ $ticket->called_by ?: '-' }}{{ $ticket->phone ? ' · ' . $ticket->phone : '' }}</td></tr>
  </table>

  <div class="section">Data Pelanggan</div>
  <table>
    <tr>
      <td style="vertical-align:top">
        <table class="kv">
          @if($customer)
            <tr><td class="k">ID Pelanggan</td><td class="s">:</td><td><strong>{{ $customer->customer_id }}</strong></td></tr>
            <tr><td class="k">Nama</td><td class="s">:</td><td>{{ $customer->name }}</td></tr>
            <tr><td class="k">Alamat</td><td class="s">:</td><td>{{ $customer->address ?: '-' }}</td></tr>
            <tr><td class="k">No. HP</td><td class="s">:</td><td>{{ $customer->phone ?: '-' }}</td></tr>
            <tr><td class="k">Paket</td><td class="s">:</td><td>{{ $customer->plan_name ?: '-' }}</td></tr>
            <tr><td class="k">Koordinat</td><td class="s">:</td><td>{{ $customer->coordinate ?: '-' }}</td></tr>
          @else
            <tr><td class="muted">Tiket ini tidak terhubung ke pelanggan.</td></tr>
          @endif
        </table>
      </td>
      <td style="width:100px;vertical-align:top">
        <table class="qr">
          <tr>
            @if($qrMaps)
              <td>
                <img src="data:image/png;base64,{{ $qrMaps }}" style="width:78px;height:78px"><br>Lokasi (Maps)
              </td>
            @endif
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="section">Keterangan</div>
  <div class="desc" style="text-align:justify">
    Melaksanakan kegiatan dan tindakan teknis yang diperlukan sesuai dengan rincian pekerjaan pada surat tugas ini,
    meliputi pemeriksaan, pengecekan, penanganan gangguan, instalasi, konfigurasi, pemeliharaan, penggantian atau
    pengambilan perangkat, serta pemberian informasi dan penjelasan kepada pelanggan terkait layanan internet,
    sesuai kebutuhan dan prosedur operasional perusahaan.
  </div>

  <p style="margin:10px 0 0">Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>

  <table class="sign" style="margin-top:8px">
    <tr>
      <td>Pemberi Tugas,</td>
      <td>Petugas,</td>
      <td>Pelanggan,</td>
    </tr>
    <tr>
      <td class="space" style="padding:4px 0">
        <img src="data:image/png;base64,{{ $qrTicket }}" style="width:64px;height:64px"><br>
        <span class="muted" style="font-size:7.5px">Ditandatangani secara elektronik</span>
      </td>
      <td class="space"></td>
      <td class="space"></td>
    </tr>
    <tr>
      <td class="who">{{ $ticket->create_by ?: '(..........................)' }}</td>
      <td class="who">{{ $officers->first()['name'] ?? '(..........................)' }}</td>
      <td class="who">{{ $customer->name ?? '(..........................)' }}</td>
    </tr>
    <tr>
      <td class="muted">{{ $issued->translatedFormat('d F Y') }}</td>
      <td class="muted">{{ $officers->first()['role'] ?? '' }}</td>
      <td class="muted">{{ $customer->customer_id ?? '' }}</td>
    </tr>
  </table>

</body>
</html>
