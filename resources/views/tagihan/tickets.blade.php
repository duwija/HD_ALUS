<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Tiket - {{ $customer->name }} - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0 60px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-container {
            max-width: 860px;
            margin: 0 auto;
        }
        /* ---- Header ---- */
        .header-card {
            background: white;
            border-radius: 15px;
            padding: 28px 32px;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header-left { display: flex; align-items: center; gap: 18px; }
        .header-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 22px; flex-shrink: 0;
        }
        .header-title { margin: 0; font-size: 20px; font-weight: 700; color: #333; }
        .header-sub  { margin: 3px 0 0; color: #888; font-size: 14px; }
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none;
            padding: 9px 20px; border-radius: 8px; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
            transition: all .3s;
        }
        .btn-back:hover { color: white; opacity: .88; text-decoration: none; }

        /* ---- Stat pills ---- */
        .stat-row {
            display: flex; flex-wrap: wrap; gap: 10px;
            margin-bottom: 22px;
        }
        .stat-pill {
            background: white; border-radius: 30px;
            padding: 8px 18px; font-size: 13px; font-weight: 600;
            box-shadow: 0 3px 12px rgba(0,0,0,0.12);
            display: flex; align-items: center; gap: 8px;
        }
        .stat-pill .dot {
            width: 10px; height: 10px; border-radius: 50%;
            display: inline-block; flex-shrink: 0;
        }

        /* ---- Ticket card ---- */
        .ticket-card {
            background: white;
            border-radius: 14px;
            margin-bottom: 18px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.10);
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .ticket-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.16);
        }
        .ticket-top {
            padding: 20px 24px 14px;
            border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: flex-start; justify-content: space-between;
            flex-wrap: wrap; gap: 10px;
        }
        .ticket-title {
            font-size: 16px; font-weight: 700; color: #222;
            margin: 0 0 5px;
        }
        .ticket-meta { font-size: 12px; color: #999; }
        .ticket-meta span { margin-right: 14px; }
        .ticket-meta i { margin-right: 4px; }
        .badge-status {
            padding: 6px 13px; border-radius: 20px; font-size: 12px;
            font-weight: 700; letter-spacing: .3px; white-space: nowrap;
        }
        .badge-open       { background: #fde8e8; color: #c0392b; }
        .badge-inprogress { background: #e8f0fe; color: #1a73e8; }
        .badge-pending    { background: #fff3cd; color: #856404; }
        .badge-solve      { background: #d4edda; color: #155724; }
        .badge-close      { background: #e9ecef; color: #495057; }

        /* ---- Workflow steps ---- */
        .workflow-wrap {
            padding: 16px 24px 20px;
        }
        .workflow-label {
            font-size: 11px; font-weight: 700; color: #aaa;
            text-transform: uppercase; letter-spacing: .8px;
            margin-bottom: 14px;
        }
        .workflow-steps {
            display: flex; align-items: center;
            overflow-x: auto; padding-bottom: 4px;
        }
        /* connector line */
        .workflow-steps .step-conn {
            flex: 1; height: 3px; min-width: 18px;
            background: #e0e0e0; border-radius: 2px;
            position: relative; top: -9px;
        }
        .workflow-steps .step-conn.done { background: #667eea; }

        .step-item {
            display: flex; flex-direction: column;
            align-items: center; flex-shrink: 0;
        }
        .step-dot {
            width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            border: 2px solid #e0e0e0;
            background: #f8f9fa; color: #aaa;
            transition: all .2s;
        }
        .step-dot.done {
            background: #667eea; border-color: #667eea;
            color: white;
        }
        .step-dot.active {
            background: white; border-color: #667eea;
            color: #667eea;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.15);
        }
        .step-dot.finish-done {
            background: #28a745; border-color: #28a745; color: white;
        }
        .step-name {
            font-size: 10px; color: #aaa;
            margin-top: 6px; text-align: center;
            max-width: 64px; word-break: break-word; line-height: 1.2;
        }
        .step-name.active { color: #667eea; font-weight: 700; }
        .step-name.done   { color: #667eea; }
        .step-name.finish-done { color: #28a745; font-weight: 700; }

        /* progress bar */
        .prog-bar-wrap { margin-top: 14px; }
        .prog-label {
            display: flex; justify-content: space-between;
            font-size: 11px; color: #999; margin-bottom: 4px;
        }
        .progress { height: 6px; border-radius: 10px; background: #eee; }
        .progress-bar { border-radius: 10px; background: linear-gradient(90deg,#667eea,#764ba2); }

        /* empty state */
        .empty-state {
            background: white; border-radius: 14px;
            padding: 50px 30px; text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,.10);
        }
        .empty-state i { color: #ddd; }
        .empty-state h5 { color: #aaa; margin: 16px 0 8px; }
        .empty-state p  { color: #bbb; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
<div class="page-container">

    {{-- Header --}}
    <div class="header-card">
        <div class="header-left">
            <div class="header-icon"><i class="fas fa-ticket-alt"></i></div>
            <div>
                <h2 class="header-title">Status Tiket Pengaduan</h2>
                <p class="header-sub">
                    <i class="fas fa-user"></i> {{ $customer->name }}
                    &nbsp;|&nbsp;
                    <i class="fas fa-id-badge"></i> {{ $customer->customer_id }}
                </p>
            </div>
        </div>
        <a href="{{ url('/tagihan') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- Stats --}}
    @php
        $statMap = [
            'Open'       => ['color'=>'#e74c3c','label'=>'Open'],
            'Inprogress' => ['color'=>'#1a73e8','label'=>'Inprogress'],
            'Pending'    => ['color'=>'#f39c12','label'=>'Pending'],
            'Solve'      => ['color'=>'#27ae60','label'=>'Selesai'],
            'Close'      => ['color'=>'#7f8c8d','label'=>'Ditutup'],
        ];
        $counts = $tickets->groupBy('status')->map->count();
    @endphp
    <div class="stat-row">
        <div class="stat-pill">
            <span class="dot" style="background:#555"></span>
            Total: {{ $tickets->count() }} tiket
        </div>
        @foreach($statMap as $key => $cfg)
            @if(($counts[$key] ?? 0) > 0)
            <div class="stat-pill">
                <span class="dot" style="background:{{ $cfg['color'] }}"></span>
                {{ $cfg['label'] }}: {{ $counts[$key] }}
            </div>
            @endif
        @endforeach
    </div>

    {{-- Ticket list --}}
    @if($tickets->isEmpty())
    <div class="empty-state">
        <i class="fas fa-inbox fa-4x"></i>
        <h5>Belum Ada Tiket</h5>
        <p>Tidak ada tiket pengaduan yang terdaftar untuk akun ini.</p>
    </div>
    @else
        @foreach($tickets as $ticket)
        @php
            $steps       = $ticket->steps->sortBy('position')->values();
            $totalSteps  = $steps->count();
            $current     = $ticket->currentStep;
            $currentPos  = $current ? $current->position : 0;
            $pct         = $totalSteps > 0 ? round(($currentPos / $totalSteps) * 100) : 0;
            $isClosed    = in_array($ticket->status, ['Close','Solve']);
            $statusBadge = [
                'Open'       => 'badge-open',
                'Inprogress' => 'badge-inprogress',
                'Pending'    => 'badge-pending',
                'Solve'      => 'badge-solve',
                'Close'      => 'badge-close',
            ][$ticket->status] ?? 'badge-secondary';
        @endphp
        <div class="ticket-card">
            <div class="ticket-top">
                <div>
                    <div class="ticket-title">{{ $ticket->tittle }}</div>
                    <div class="ticket-meta">
                        <span><i class="fas fa-hashtag"></i>#{{ $ticket->id }}</span>
                        @if($ticket->categorie)
                        <span><i class="fas fa-tag"></i>{{ $ticket->categorie->name }}</span>
                        @endif
                        <span><i class="fas fa-calendar-alt"></i>{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, H:i') }}</span>
                    </div>
                </div>
                <span class="badge-status {{ $statusBadge }}">
                    @if($ticket->status === 'Inprogress') Sedang Diproses
                    @elseif($ticket->status === 'Open') Baru Masuk
                    @elseif($ticket->status === 'Pending') Menunggu
                    @elseif($ticket->status === 'Solve') Selesai
                    @elseif($ticket->status === 'Close') Ditutup
                    @else {{ $ticket->status }}
                    @endif
                </span>
            </div>

            <div class="workflow-wrap">
                @if($steps->isEmpty())
                    <p class="text-muted small mb-0"><i class="fas fa-info-circle"></i> Belum ada alur kerja.</p>
                @else
                <div class="workflow-label"><i class="fas fa-project-diagram"></i> Alur Kerja</div>
                <div class="workflow-steps">
                    @foreach($steps as $i => $step)
                        @php
                            $isActive  = $current && $current->id == $step->id;
                            $isDone    = $currentPos > $step->position || ($isClosed && $currentPos >= $step->position);
                            $isFinish  = strtolower($step->name) === 'finish';
                            $dotClass  = $isActive ? 'active' : ($isDone && $isFinish ? 'finish-done' : ($isDone ? 'done' : ''));
                            $nameClass = $isActive ? 'active' : ($isDone && $isFinish ? 'finish-done' : ($isDone ? 'done' : ''));
                        @endphp
                        <div class="step-item">
                            <div class="step-dot {{ $dotClass }}">
                                @if($isDone && $isFinish) <i class="fas fa-check"></i>
                                @elseif($isDone) <i class="fas fa-check"></i>
                                @elseif($isActive) {{ $step->position }}
                                @else {{ $step->position }}
                                @endif
                            </div>
                            <div class="step-name {{ $nameClass }}">{{ $step->name }}</div>
                        </div>
                        @if(!$loop->last)
                            <div class="step-conn {{ $isDone ? 'done' : '' }}"></div>
                        @endif
                    @endforeach
                </div>

                <div class="prog-bar-wrap">
                    <div class="prog-label">
                        <span>
                            @if($current)
                                <i class="fas fa-map-marker-alt" style="color:#667eea"></i>
                                {{ $current->name }}
                                (Langkah {{ $currentPos }} dari {{ $totalSteps }})
                            @else
                                Belum dimulai
                            @endif
                        </span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    @endif

</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
