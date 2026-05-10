@extends('layout.main')
@section('title','Job Schedule List')

@section('content')
<style>
  .schedule-list-wrap {
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 10px;
  }

  .schedule-filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-surface);
  }

  .schedule-filter-input,
  .schedule-filter-select {
    min-width: 160px;
    height: 36px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-surface-2);
    color: var(--text-primary);
    padding: 0 10px;
    font-size: 13px;
  }

  .schedule-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 12px;
  }

  .schedule-ticket-card {
    background: var(--bg-surface);
    border-radius: 9px;
    border: 1px solid var(--border);
    border-left: 4px solid var(--border);
    padding: 12px 14px;
    color: var(--text-primary);
    box-shadow: var(--shadow-sm);
  }

  .schedule-ticket-card.card-open {
    border-left-color: #dc3545;
  }

  .schedule-ticket-card.card-inprogress {
    border-left-color: #0d6efd;
  }

  .schedule-ticket-card.card-pending {
    border-left-color: #f0ad00;
  }

  .schedule-ticket-card.card-solve {
    border-left-color: #198754;
  }

  .schedule-ticket-card.card-close {
    border-left-color: #6c757d;
  }

  .schedule-ticket-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
  }

  .status-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.2px;
  }

  .status-open { background: #dc3545; }
  .status-inprogress { background: #0d6efd; }
  .status-pending { background: #ffc107; color: #222; }
  .status-solve { background: #198754; }
  .status-close { background: #6c757d; }

  .ticket-title {
    font-size: 15px;
    font-weight: 700;
    line-height: 1.3;
    margin-bottom: 4px;
  }

  .ticket-meta {
    font-size: 12px;
    color: var(--text-secondary);
    display: flex;
    flex-wrap: wrap;
    gap: 6px 10px;
    margin-bottom: 8px;
  }

  .ticket-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 12px;
    margin-bottom: 8px;
  }

  .mttr-row {
    margin-top: -2px;
    margin-bottom: 8px;
    font-size: 12px;
    color: var(--text-secondary);
  }

  .mttr-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: var(--bg-surface-2);
    font-weight: 700;
    color: var(--text-primary);
  }

  .workflow-steps {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 6px;
    margin-bottom: 8px;
  }

  .workflow-step {
    flex: 1;
    min-width: 0;
    text-align: center;
  }

  .workflow-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin: 0 auto 3px;
    border: 2px solid var(--border);
    background: var(--bg-surface-2);
    box-shadow: inset 0 0 0 2px rgba(255,255,255,0.35);
  }

  .workflow-dot.done {
    background: #2f9e44;
    border-color: #2f9e44;
    box-shadow: 0 0 0 2px rgba(47,158,68,0.2);
  }

  .workflow-dot.active {
    background: #1c7ed6;
    border-color: #1c7ed6;
    box-shadow: 0 0 0 2px rgba(28,126,214,0.2);
  }

  .workflow-label {
    font-size: 10px;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .workflow-track {
    height: 8px;
    border-radius: 999px;
    background: var(--border-light);
    overflow: hidden;
    margin-bottom: 6px;
  }

  .workflow-fill {
    height: 100%;
    background: linear-gradient(90deg, #2d9cdb, #2368d1);
  }

  .workflow-info {
    font-size: 11px;
    color: var(--text-secondary);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
  }

  .ticket-link-btn {
    border: 1px solid #2f6feb;
    color: #2f6feb;
    background: transparent;
    font-weight: 700;
    min-width: 74px;
  }

  .schedule-list-header {
    background: var(--bg-surface-2);
    border-bottom: 1px solid var(--border);
  }

  .ticket-link-btn:hover {
    background: #2f6feb;
    color: #fff;
    border-color: #2f6feb;
  }

  @media (max-width: 992px) {
    .schedule-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<section class="content-header">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
      <h1 class="m-0" style="font-size:1.25rem;">
        <i class="fas fa-list-ul text-primary mr-1"></i>
        Job Schedule List
      </h1>
      <div>
        <span class="badge badge-info" style="font-size:.8rem;">{{ $today->format('d M Y') }}</span>
        <span id="schedule-count" class="badge badge-primary" style="font-size:.8rem;">{{ $tickets->count() }} tiket</span>
        <span id="schedule-updated" class="badge badge-secondary" style="font-size:.8rem;">update --:--:--</span>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="schedule-list-wrap">
      <div class="card-header d-flex justify-content-between align-items-center schedule-list-header">
        <h3 class="card-title mb-0">Semua tiket hari ini</h3>
        <a href="{{ url('schedule') }}" target="_blank" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-tv mr-1"></i>TV Wall
        </a>
      </div>
      <div class="schedule-filter-bar">
        <input type="date" id="schedule-filter-start" class="schedule-filter-input" value="{{ $today->toDateString() }}">
        <input type="date" id="schedule-filter-end" class="schedule-filter-input" value="{{ $today->toDateString() }}">

        <select id="schedule-filter-status" class="schedule-filter-select">
          <option value="all">All Status</option>
          <option value="Open">Open</option>
          <option value="Inprogress">Inprogress</option>
          <option value="Pending">Pending</option>
          <option value="Solve">Solve</option>
          <option value="Close">Close</option>
        </select>

        <select id="schedule-filter-category" class="schedule-filter-select">
          <option value="all">All Category</option>
          @foreach($categories as $category)
          <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>

        <select id="schedule-filter-tag" class="schedule-filter-select">
          <option value="all">All Tag</option>
          @foreach($tags as $tag)
          <option value="{{ $tag->id }}">{{ $tag->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="card-body p-0">
        <div id="schedule-grid-wrap">
          @include('ticket.schedule-list-cards')
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  const scheduleListDataUrl = "{{ route('ticket.schedule.list.data') }}";
  const gridWrapEl = document.getElementById('schedule-grid-wrap');
  const countEl = document.getElementById('schedule-count');
  const updatedEl = document.getElementById('schedule-updated');
  const startEl = document.getElementById('schedule-filter-start');
  const endEl = document.getElementById('schedule-filter-end');
  const statusEl = document.getElementById('schedule-filter-status');
  const categoryEl = document.getElementById('schedule-filter-category');
  const tagEl = document.getElementById('schedule-filter-tag');

  async function refreshScheduleList() {
    try {
      const params = new URLSearchParams({
        start_date: startEl.value,
        end_date: endEl.value,
        status: statusEl.value,
        category: categoryEl.value,
        tag: tagEl.value,
      });

      const res = await fetch(`${scheduleListDataUrl}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!res.ok) return;

      const data = await res.json();
      gridWrapEl.style.opacity = '0.45';
      setTimeout(() => {
        gridWrapEl.innerHTML = data.html;
        gridWrapEl.style.opacity = '1';
      }, 120);

      countEl.textContent = `${data.count} tiket`;
      updatedEl.textContent = `update ${data.updated_at}`;
    } catch (err) {
      console.error('Auto refresh schedule list gagal:', err);
    }
  }

  [startEl, endEl, statusEl, categoryEl, tagEl].forEach((el) => {
    el.addEventListener('change', () => refreshScheduleList());
  });

  refreshScheduleList();
  setInterval(refreshScheduleList, 60000);
</script>
@endsection
