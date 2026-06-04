<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #222;
        }
        h2, h3, h4, p {
            margin: 0 0 8px 0;
        }
        .muted {
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: left;
        }
        .summary td, .summary th {
            text-align: center;
        }
        .calendar td {
            text-align: center;
            width: 14.285%;
            height: 48px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            color: #fff;
            margin-top: 3px;
        }
        .success { background: #198754; }
        .warning { background: #d39e00; }
        .info { background: #0dcaf0; color: #000; }
        .secondary { background: #6c757d; }
        .dark { background: #212529; }
        .danger { background: #dc3545; }
        .light { background: #f8f9fa; color: #222; }
    </style>
</head>
<body>
    <h2>Laporan Rekap Absensi</h2>
    <p class="muted">Periode: {{ $monthStart->format('F Y') }}@if($calendarUser) | Karyawan: {{ $calendarUser->name }}@endif</p>

    <table class="summary">
        <thead>
            <tr>
                <th>Absensi</th>
                <th>Terlambat</th>
                <th>Cuti</th>
                <th>Sakit</th>
                <th>Libur</th>
                <th>Tanpa Info</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $summary->sum('attendance') }}</td>
                <td>{{ $summary->sum('late') }}</td>
                <td>{{ $summary->sum('cuti') }}</td>
                <td>{{ $summary->sum('sakit') }}</td>
                <td>{{ $summary->sum('libur') }}</td>
                <td>{{ $summary->sum('tanpa_keterangan') }}</td>
            </tr>
        </tbody>
    </table>

    <h3>Rekap Per Karyawan</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Karyawan</th>
                <th>Absensi</th>
                <th>Terlambat</th>
                <th>Cuti</th>
                <th>Sakit</th>
                <th>Libur</th>
                <th>Tanpa Info</th>
                <th>Total Jam Kerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary as $empId => $stat)
                @php $emp = $employees->firstWhere('id', $empId); $minutes = (int) ($stat['total_work_minutes'] ?? 0); @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $emp->name ?? 'Karyawan #' . $empId }}</td>
                    <td>{{ $stat['attendance'] ?? 0 }}</td>
                    <td>{{ $stat['late'] ?? 0 }}</td>
                    <td>{{ $stat['cuti'] ?? 0 }}</td>
                    <td>{{ $stat['sakit'] ?? 0 }}</td>
                    <td>{{ $stat['libur'] ?? 0 }}</td>
                    <td>{{ $stat['tanpa_keterangan'] ?? 0 }}</td>
                    <td>{{ intdiv($minutes, 60) }}j {{ $minutes % 60 }}m</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h3>Kalender Absensi</h3>
    @if(!$calendarUser)
        <p class="muted">Pilih karyawan untuk melihat kalender.</p>
    @else
        @php
            $firstDow = $monthStart->dayOfWeek;
            $col = 0;
            $statusStyle = [
                'attendance' => ['class' => 'success', 'label' => 'H'],
                'sakit' => ['class' => 'secondary', 'label' => 'S'],
                'cuti' => ['class' => 'info', 'label' => 'C'],
                'izin' => ['class' => 'info', 'label' => 'I'],
                'libur' => ['class' => 'dark', 'label' => 'L'],
                'tanpa_keterangan' => ['class' => 'danger', 'label' => 'A'],
            ];
        @endphp
        <table class="calendar">
            <thead>
                <tr>
                    <th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    @for($i = 0; $i < $firstDow; $i++)
                        <td></td>
                        @php $col++; @endphp
                    @endfor
                    @foreach($calendarDays as $day)
                        @if($col % 7 === 0 && !$loop->first)
                            </tr><tr>
                        @endif
                        @php $style = $statusStyle[$day['status']] ?? $statusStyle['tanpa_keterangan']; @endphp
                        <td>
                            <div><strong>{{ $day['day'] }}</strong></div>
                            <div class="badge {{ $style['class'] }}">{{ $style['label'] }}</div>
                            @if(!empty($day['clock_in']))
                                <div class="muted" style="font-size:10px;">{{ substr($day['clock_in'], 0, 5) }}</div>
                            @endif
                        </td>
                        @php $col++; @endphp
                    @endforeach
                    @while($col % 7 !== 0)
                        <td></td>
                        @php $col++; @endphp
                    @endwhile
                </tr>
            </tbody>
        </table>
    @endif

    <h3>Detail Absensi</h3>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Karyawan</th>
                <th>Shift</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Status</th>
                <th>Jam Kerja</th>
                <th>Terlambat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $rec)
                <tr>
                    <td>{{ $rec->date ? $rec->date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $rec->user->name ?? '-' }}</td>
                    <td>{{ optional($rec->shift)->name ?? '-' }}</td>
                    <td>{{ $rec->clock_in ?? '-' }}</td>
                    <td>{{ $rec->clock_out ?? '-' }}</td>
                    <td>{{ strip_tags($rec->statusBadge()) }}</td>
                    <td>
                        @if($rec->work_minutes)
                            {{ intdiv($rec->work_minutes, 60) }}j {{ $rec->work_minutes % 60 }}m
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $rec->late_minutes > 0 ? $rec->late_minutes . ' mnt' : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">Tidak ada data absensi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
