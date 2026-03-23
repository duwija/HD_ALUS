
<section class="content-header">
  
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-book"></i> Jurnal dengan Code:
      <span class="text-primary">{{ $code }}</span>
    </h3>
  </div>

  <div class="card-body">
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
          <th>Created At</th>
          <th>Deskripsi</th>

          <th>ID Akun</th>
          <th class="text-right">Debet</th>
          <th class="text-right">Kredit</th>
        </tr>
      </thead>
      <tbody>
       <tr class="bg-light font-weight-bold">
        <td colspan="7" class="text-left">{{ $note }}</td>

      </tr>
      @foreach($jurnals as $index => $jurnal)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ \Carbon\Carbon::parse($jurnal->date)->format('Y-m-d H:i') }}</td>
        <td>{{ $jurnal->created_at }}</td>
        <td>{{ $jurnal->description }}</td>

        <td>
          <span class="">
            {{ $jurnal->id_akun }}
            @if($jurnal->id_akun)
            | {{ $jurnal->akun->name }}
            @endif
          </span>
        </td>
        <td class="text-right">
          {{ $jurnal->debet ? number_format($jurnal->debet, 0, ',', '.') : '-' }}
        </td>
        <td class="text-right">
          {{ $jurnal->kredit ? number_format($jurnal->kredit, 0, ',', '.') : '-' }}
        </td>
      </tr>

      @endforeach

      <tr class="bg-light font-weight-bold">
        <td colspan="5" class="text-right">TOTAL</td>
        <td class="text-right">{{ number_format($totalDebet, 0, ',', '.') }}</td>
        <td class="text-right">{{ number_format($totalKredit, 0, ',', '.') }}</td>
      </tr>
    </tbody>
  </table>
  @endif
</div>

</section>

