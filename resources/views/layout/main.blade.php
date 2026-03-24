<!DOCTYPE html>
<html>
<head>

  <!-- === Dark Mode Preload Fix (no-flash) === -->
  <script>
    (function() {
      if (localStorage.getItem("darkMode") === "enabled") {
        document.documentElement.classList.add("dark-mode");
      }
      if (localStorage.getItem("densityMode") === "compact") {
        document.documentElement.classList.add("compact-mode");
      }
    })();
  </script>
  <link rel="icon" type="image/png" href="{{ tenant_img('favicon.png', 'favicon.png') }}">
  @inject('ticket', 'App\Ticket')
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  {{--   <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
  <title>| {{ config('app.name') }} Helpdesk System | @yield('title')</title>

  @yield('maps')
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{url('dashboard/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{url('dashboard/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
  <!-- Select2 -->
  <link rel="stylesheet" href="{{url('dashboard/plugins/select2/css/select2.min.css')}}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{url('dashboard/plugins/fontawesome-free/css/all.min.css')}}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="{{url('dashboard/plugins/summernote/summernote-bs4.css')}}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>
  <!-- Ionicons: async non-blocking -->
  <link rel="preconnect" href="https://code.ionicframework.com">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" media="print" onload="this.media='all'">
  <noscript><link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css"></noscript>
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
  <link rel="stylesheet" href="{{url('dashboard/dist/css/adminlte.min.css')}}">
  <!-- Google Font: async non-blocking -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
  <noscript><link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet"></noscript>
  <link rel="stylesheet" href="{{url('dashboard/dist/css/workflow.css')}}">
  <!-- <link rel="stylesheet" href="{{url('dashboard/dist/css/tvwall.css')}}"> -->
  <!-- Financial Reports & Accounting Stylesheet -->
  <!-- <link rel="stylesheet" href="{{url('css/financial-reports.css')}}?v={{ time() }}"> -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <!-- <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" /> -->
  <!-- <link rel="stylesheet" href="https://unpkg.com/leaflet-search/dist/leaflet-search.css" /> -->

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-search/3.0.0/leaflet-search.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">

  <!-- jQuery + DataTables + Bootstrap di head (lokal) agar inline scripts bisa pakai $ dan DataTable -->
  <script src="{{url('dashboard/plugins/jquery/jquery.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables/jquery.dataTables.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
  <script src="{{url('dashboard/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.5.6/css/buttons.dataTables.min.css">
  <!-- DataTables Buttons JS di head agar button types (print, excel, pdf, csv) tersedia sebelum DataTable init -->
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.print.min.js"></script>
  
  <style>
    /* ============================================================
       CSS VARIABLES — Light Mode (default)
       ============================================================ */
    :root {
      --brand:          #a3301c;
      --brand-dark:     #7f2515;
      --brand-light:    rgba(163,48,28,0.08);

      /* Surface */
      --bg-body:        #f4f6f9;
      --bg-surface:     #ffffff;
      --bg-surface-2:   #f8f9fa;
      --bg-sidebar:     #1e2230;
      --bg-navbar:      #ffffff;

      /* Text */
      --text-primary:   #1a1d23;
      --text-secondary: #6b7280;
      --text-muted:     #9ca3af;
      --text-inverse:   #ffffff;

      /* Border */
      --border:         #e5e7eb;
      --border-light:   #f3f4f6;

      /* Shadow */
      --shadow-sm:      0 1px 3px rgba(0,0,0,0.08);
      --shadow-md:      0 4px 12px rgba(0,0,0,0.1);
      --shadow-lg:      0 8px 24px rgba(0,0,0,0.12);

      /* Input */
      --input-bg:       #f8f9fa;
      --input-border:   #e2e8f0;
    }

    /* ============================================================
       CSS VARIABLES — Dark Mode
       Applied to both html.dark-mode (preload, no-flash) and body.dark-mode
       ============================================================ */
    html.dark-mode,
    body.dark-mode {
      --bg-body:        #1a1d23;
      --bg-surface:     #242832;
      --bg-surface-2:   #2d3140;
      --bg-sidebar:     #13151a;
      --bg-navbar:      #242832;

      --text-primary:   #e8eaf0;
      --text-secondary: #9ba3b2;
      --text-muted:     #6b7280;

      --border:         #333845;
      --border-light:   #2d3140;

      --shadow-sm:      0 1px 3px rgba(0,0,0,0.3);
      --shadow-md:      0 4px 12px rgba(0,0,0,0.4);
      --shadow-lg:      0 8px 24px rgba(0,0,0,0.5);

      --input-bg:       #2d3140;
      --input-border:   #3d4355;
    }

    /* ============================================================
       DENSITY — Compact Mode
       Applied to html.compact-mode (preload flag) and body.compact-mode
       PENTING: font-size HANYA pada body, BUKAN html — agar rem yang
       dipakai AdminLTE untuk layout sidebar (4.6rem, 3.5rem, dll)
       tetap berbasis 16px dan tidak bergeser posisinya.
       ============================================================ */
    body.compact-mode {
      font-size: 13px !important;
    }

    /* Navbar */
    body.compact-mode .main-header.navbar {
      min-height: 44px !important;
      padding: 0 12px !important;
    }
    body.compact-mode .main-header .navbar-nav .nav-link {
      padding: 4px 8px !important;
    }
    body.compact-mode .navbar-avatar { width: 28px !important; height: 28px !important; }

    /* Sidebar */
    body.compact-mode .brand-link  { padding: 9px 14px !important; }
    body.compact-mode .brand-link .brand-image { margin-left: 0 !important; }
    body.compact-mode .brand-text  { font-size: 13px !important; }
    body.compact-mode .nav-sidebar .nav-link {
      margin: 1px 8px !important;
      padding: 6px 10px !important;
      font-size: 12.5px !important;
    }

    /* Cards */
    body.compact-mode .card {
      border-radius: 8px !important;
      margin-bottom: 0.4rem !important;
    }
    body.compact-mode .card-header {
      padding: 5px 10px !important;
      min-height: 26px !important;
    }
    body.compact-mode .card-body   { padding: 6px 10px !important; }
    body.compact-mode .card-footer { padding: 5px 10px !important; }
    body.compact-mode .card-title  { font-size: 13px !important; }

    /* Tables */
    body.compact-mode .table td,
    body.compact-mode .table th    { padding: 0.3rem 0.5rem !important; font-size: 12px !important; }
    body.compact-mode .table thead th { font-size: 11px !important; }

    /* Forms */
    body.compact-mode .form-control,
    body.compact-mode .custom-select {
      padding: 0.22rem 0.5rem !important;
      font-size: 12px !important;
      height: calc(1.5em + 0.44rem + 2px) !important;
      border-radius: 6px !important;
    }
    body.compact-mode label        { font-size: 12px !important; }

    /* Buttons */
    body.compact-mode .btn {
      padding: 0.22rem 0.55rem !important;
      font-size: 12px !important;
      border-radius: 6px !important;
    }

    /* Info Boxes */
    body.compact-mode .info-box {
      min-height: 60px !important;
      margin-bottom: 0.4rem !important;
      border-radius: 8px !important;
    }
    body.compact-mode .info-box-icon  { width: 60px !important; font-size: 1.4rem !important; }
    body.compact-mode .info-box-content { padding: 5px 8px !important; }
    body.compact-mode .info-box-text   { font-size: 12px !important; }
    body.compact-mode .info-box-number { font-size: 18px !important; }

    /* Badges */
    body.compact-mode .badge     { font-size: 10px !important; padding: 2px 6px !important; }
    body.compact-mode .navbar-badge { font-size: 9px !important; padding: 1px 4px !important; }

    /* Timeline */
    body.compact-mode .timeline-item {
      padding: 8px 12px !important;
      margin-bottom: 8px !important;
      border-radius: 8px !important;
    }

    /* Dropdown */
    body.compact-mode .dropdown-item { padding: 5px 10px !important; font-size: 12px !important; }
    body.compact-mode .dropdown-menu { padding: 5px !important; }

    /* Content wrapper top padding */
    body.compact-mode .content-wrapper { padding-top: 2px !important; }

    /* DataTables */
    body.compact-mode .dataTables_wrapper .dataTables_filter input,
    body.compact-mode .dataTables_wrapper .dataTables_length select {
      padding: 2px 6px !important;
      font-size: 12px !important;
    }

    /* ============================================================
       GLOBAL BASE
       ============================================================ */
    *, *::before, *::after { box-sizing: border-box; }

    body {
      background-color: var(--bg-body) !important;
      color: var(--text-primary) !important;
      font-family: 'Inter', 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      transition: background-color 0.25s ease, color 0.25s ease;
    }

    /* ============================================================
       NAVBAR
       ============================================================ */
    .main-header.navbar {
      background-color: var(--bg-navbar) !important;
      border-bottom: 1px solid var(--border) !important;
      box-shadow: var(--shadow-sm) !important;
      padding: 0 16px;
      min-height: 56px;
      transition: background-color 0.25s ease, border-color 0.25s ease;
    }

    .main-header .navbar-nav .nav-link {
      color: var(--text-secondary) !important;
      border-radius: 8px;
      padding: 6px 10px;
      transition: all 0.2s ease;
    }
    .main-header .navbar-nav .nav-link:hover {
      background: var(--brand-light);
      color: var(--brand) !important;
    }
    .main-header .navbar-nav .nav-link i {
      color: inherit !important;
    }

    /* Search bar in navbar */
    .navbar-search-form .input-group {
      background: var(--input-bg);
      border: 1.5px solid var(--input-border);
      border-radius: 10px;
      overflow: hidden;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .navbar-search-form .input-group:focus-within {
      border-color: var(--brand);
      box-shadow: 0 0 0 3px rgba(163,48,28,0.12);
    }
    .navbar-search-form .form-control {
      background: transparent !important;
      border: none !important;
      color: var(--text-primary) !important;
      box-shadow: none !important;
      font-size: 13px;
      padding: 6px 12px;
    }
    .navbar-search-form .form-control::placeholder { color: var(--text-muted) !important; }
    .navbar-search-form .btn-search {
      background: var(--brand);
      border: none;
      color: #fff;
      padding: 0 14px;
      transition: background 0.2s ease;
    }
    .navbar-search-form .btn-search:hover { background: var(--brand-dark); }

    /* User avatar */
    .navbar-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--border);
      transition: border-color 0.2s;
    }
    .navbar-avatar:hover { border-color: var(--brand); }

    /* Dropdown menu */
    .dropdown-menu {
      background: var(--bg-surface) !important;
      border: 1px solid var(--border) !important;
      box-shadow: var(--shadow-md) !important;
      border-radius: 12px !important;
      padding: 8px !important;
      min-width: 200px;
    }
    .dropdown-item {
      color: var(--text-primary) !important;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 13px;
      transition: all 0.15s ease;
    }
    .dropdown-item:hover {
      background: var(--brand-light) !important;
      color: var(--brand) !important;
    }
    .dropdown-item.font-weight-bold { color: var(--brand) !important; }
    .dropdown-menu hr { border-color: var(--border) !important; margin: 4px 0; }

    /* Ticket badge */
    .navbar-badge {
      font-size: 10px;
      font-weight: 700;
      padding: 2px 5px;
      border-radius: 10px;
    }

    /* ============================================================
       SIDEBAR
       ============================================================ */
    .main-sidebar {
      background: var(--bg-sidebar) !important;
      box-shadow: var(--shadow-md) !important;
      transition: background-color 0.25s ease, box-shadow 0.25s ease;
    }

    .brand-link {
      background: rgba(0,0,0,0.2) !important;
      border-bottom: 1px solid rgba(255,255,255,0.06) !important;
      padding: 14px 16px !important;
    }
    .brand-link .brand-image { margin-left: 0 !important; }
    .brand-link:hover { background: rgba(0,0,0,0.3) !important; }
    .brand-text { color: #e8eaf0 !important; font-weight: 600 !important; font-size: 15px !important; }

    .sidebar { padding: 8px 0; }

    .nav-sidebar .nav-link {
      border-radius: 8px !important;
      margin: 2px 8px !important;
      padding: 9px 12px !important;
      color: rgba(232,234,240,0.7) !important;
      font-size: 13.5px !important;
      transition: all 0.2s ease !important;
    }
    .nav-sidebar .nav-link:hover,
    .nav-sidebar .nav-link.active {
      background: rgba(163,48,28,0.25) !important;
      color: #ffffff !important;
    }
    .nav-sidebar .nav-link .nav-icon {
      color: rgba(232,234,240,0.5) !important;
      font-size: 14px !important;
      margin-right: 10px !important;
      width: 18px !important;
    }
    .nav-sidebar .nav-link:hover .nav-icon,
    .nav-sidebar .nav-link.active .nav-icon {
      color: #ffffff !important;
    }
    .nav-sidebar .nav-item > .nav-treeview {
      background: rgba(0,0,0,0.15) !important;
      border-radius: 8px;
      margin: 0 8px 4px;
    }
    .sidebar hr {
      border-color: rgba(255,255,255,0.08) !important;
      margin: 8px 16px;
    }

    /* ============================================================
       CONTENT WRAPPER
       ============================================================ */
    .content-wrapper {
      background-color: var(--bg-body) !important;
      transition: background-color 0.25s ease;
    }

    /* ============================================================
       CARDS
       ============================================================ */
    .card {
      background: var(--bg-surface) !important;
      border: 1px solid var(--border) !important;
      border-radius: 12px !important;
      box-shadow: var(--shadow-sm) !important;
      color: var(--text-primary) !important;
      transition: box-shadow 0.2s ease;
    }
    .card:hover { box-shadow: var(--shadow-md) !important; }
    .card-header {
      background: var(--bg-surface-2) !important;
      border-bottom: 1px solid var(--border) !important;
      border-radius: 12px 12px 0 0 !important;
      font-weight: 600;
      color: var(--text-primary) !important;
    }
    .card-footer {
      background: var(--bg-surface-2) !important;
      border-top: 1px solid var(--border) !important;
      border-radius: 0 0 12px 12px !important;
    }

    /* ============================================================
       TABLES
       ============================================================ */
    .table {
      color: var(--text-primary) !important;
    }
    .table thead th {
      background: var(--bg-surface-2) !important;
      border-color: var(--border) !important;
      color: var(--text-secondary) !important;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .table td, .table th {
      border-color: var(--border) !important;
      vertical-align: middle;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: var(--bg-surface-2) !important;
    }
    .table-hover tbody tr:hover {
      background-color: var(--brand-light) !important;
    }

    /* ============================================================
       FORMS
       ============================================================ */
    .form-control, .custom-select {
      background: var(--input-bg) !important;
      border: 1.5px solid var(--input-border) !important;
      border-radius: 8px !important;
      color: var(--text-primary) !important;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-control:focus, .custom-select:focus {
      border-color: var(--brand) !important;
      box-shadow: 0 0 0 3px rgba(163,48,28,0.12) !important;
      background: var(--bg-surface) !important;
    }
    .form-control::placeholder { color: var(--text-muted) !important; }
    label { color: var(--text-secondary) !important; font-size: 13px; font-weight: 600; }

    /* ============================================================
       BUTTONS
       ============================================================ */
    .btn { border-radius: 8px !important; font-weight: 600 !important; font-size: 13px !important; transition: all 0.2s ease !important; }
    .btn-primary {
      background: #4a76bd !important;
      border-color: #4a76bd !important;
    }
    .btn-primary:hover { background: #3a62a0 !important; border-color: #3a62a0 !important; transform: translateY(-1px); }
    .btn-success {
      background: #2d8a5e !important;
      border-color: #2d8a5e !important;
    }
    .btn-success:hover { background: #22714c !important; border-color: #22714c !important; transform: translateY(-1px); }
    .btn-danger  { background: #d94f3d !important; border-color: #d94f3d !important; }
    .btn-warning { background: #e0963a !important; border-color: #e0963a !important; color: #fff !important; }
    .btn-info    { background: #3b9ec9 !important; border-color: #3b9ec9 !important; }
    .btn-secondary {
      background: var(--bg-surface-2) !important;
      border-color: var(--border) !important;
      color: var(--text-secondary) !important;
    }
    .btn-secondary:hover { background: var(--border) !important; }

    /* ============================================================
       BADGES
       ============================================================ */
    .badge-soft-danger    { background: #fee2e2 !important; color: #991b1b !important; }
    .badge-soft-warning   { background: #fef3c7 !important; color: #92400e !important; }
    .badge-soft-info      { background: #dbeafe !important; color: #1e40af !important; }
    .badge-soft-success   { background: #d1fae5 !important; color: #065f46 !important; }
    .badge-soft-secondary { background: #f3f4f6 !important; color: #4b5563 !important; }
    .badge-soft-inprogress{ background: #e0e7ff !important; color: #3730a3 !important; }

    body.dark-mode .badge-soft-danger    { background: rgba(239,68,68,0.15) !important;  color: #fca5a5 !important; }
    body.dark-mode .badge-soft-warning   { background: rgba(245,158,11,0.15) !important; color: #fcd34d !important; }
    body.dark-mode .badge-soft-info      { background: rgba(59,130,246,0.15) !important; color: #93c5fd !important; }
    body.dark-mode .badge-soft-success   { background: rgba(16,185,129,0.15) !important; color: #6ee7b7 !important; }
    body.dark-mode .badge-soft-secondary { background: rgba(107,114,128,0.2) !important; color: #d1d5db !important; }
    body.dark-mode .badge-soft-inprogress{ background: rgba(99,102,241,0.15) !important; color: #a5b4fc !important; }

    /* ============================================================
       TIMELINE (Ticket)
       ============================================================ */
    .timeline-item {
      background: var(--bg-surface);
      border-radius: 12px;
      border: 1px solid var(--border);
      box-shadow: var(--shadow-sm);
      transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .timeline-item:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
    }
    .time { font-size: 0.82rem; color: var(--text-muted); }
    .timeline-header strong { font-size: 0.9rem; color: var(--text-primary); }
    .timeline-body strong   { font-size: 0.9rem; color: var(--text-primary); }
    .ticket-meta { font-size: 0.8rem; color: var(--text-muted); line-height: 1.4; }

    .badge-modern { padding: 4px 8px; font-size: 0.7rem; border-radius: 20px; font-weight: 700; letter-spacing: 0.3px; }
    .badge-Open       { background: #fee2e2; color: #991b1b; }
    .badge-Pending    { background: #fef3c7; color: #92400e; }
    .badge-Solve      { background: #d1fae5; color: #065f46; }
    .badge-Close      { background: #f3f4f6; color: #374151; }
    .badge-Inprogress { background: #e0e7ff; color: #3730a3; }

    body.dark-mode .badge-Open       { background: rgba(239,68,68,0.15); color: #fca5a5; }
    body.dark-mode .badge-Pending    { background: rgba(245,158,11,0.15); color: #fcd34d; }
    body.dark-mode .badge-Solve      { background: rgba(16,185,129,0.15); color: #6ee7b7; }
    body.dark-mode .badge-Close      { background: rgba(107,114,128,0.2); color: #d1d5db; }
    body.dark-mode .badge-Inprogress { background: rgba(99,102,241,0.15); color: #a5b4fc; }

    /* ============================================================
       FOOTER
       ============================================================ */
    .main-footer {
      background: var(--bg-surface) !important;
      border-top: 1px solid var(--border) !important;
      color: var(--text-secondary) !important;
      font-size: 13px;
      transition: background-color 0.25s ease;
    }
    .main-footer a { color: var(--brand) !important; }
    .main-footer a:hover { color: var(--brand-dark) !important; }

    /* ============================================================
       MISC
       ============================================================ */
    .btn-choose-step {
      position: relative;
      z-index: 9999;
      cursor: pointer;
      padding: 8px 12px;
    }

    @keyframes glowing {
      0%   { background-color: #ffd700; box-shadow: 0 0 4px #2ba805; }
      50%  { background-color: #ffd966; box-shadow: 0 0 8px #49e819; }
      100% { background-color: #2ba805; box-shadow: 0 0 4px #2ba805; }
    }
    .btnblink { animation: glowing 1300ms infinite; }

    @media (max-width: 768px) {
      .tiketview_padding { padding: 2px !important; }
      .tiketview img { width: 100% !important; height: auto !important; }
    }

    /* Select2 dark mode */
    body.dark-mode .select2-container--default .select2-selection--single,
    body.dark-mode .select2-container--default .select2-selection--multiple {
      background: var(--input-bg) !important;
      border-color: var(--input-border) !important;
      color: var(--text-primary) !important;
    }
    body.dark-mode .select2-dropdown {
      background: var(--bg-surface) !important;
      border-color: var(--border) !important;
    }
    body.dark-mode .select2-results__option {
      color: var(--text-primary) !important;
    }
    body.dark-mode .select2-results__option--highlighted {
      background: var(--brand-light) !important;
      color: var(--brand) !important;
    }

    /* DataTables dark mode */
    body.dark-mode .dataTables_wrapper .dataTables_filter input,
    body.dark-mode .dataTables_wrapper .dataTables_length select {
      background: var(--input-bg) !important;
      border-color: var(--input-border) !important;
      color: var(--text-primary) !important;
    }
    body.dark-mode .dataTables_wrapper .dataTables_info,
    body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
      color: var(--text-secondary) !important;
    }
    body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--brand) !important;
      color: #fff !important;
      border-radius: 6px;
    }

    /* Scrollbar modern */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

    /* Cegah Bootstrap/SweetAlert menambah padding-right ke body saat modal/popup
       terbuka — ini yang menyebabkan sidebar bergeser ke kanan */
    html { overflow-y: scroll; }
    body.modal-open  { padding-right: 0 !important; overflow-y: scroll !important; }
    body.swal2-shown { padding-right: 0 !important; }
    body.swal2-height-auto { padding-right: 0 !important; }

    /* Sidebar hidden state — ditampilkan setelah JS merestorasi state agar tidak flicker */
    .main-sidebar { transition: none !important; }

    /* Loading modal */
    .modal-content {
      background: var(--bg-surface) !important;
      color: var(--text-primary) !important;
      border: 1px solid var(--border) !important;
      border-radius: 16px !important;
    }
  </style>

</head>
<body class="hold-transition sidebar-mini sidebar-collapse" id="page-body">
<script>
(function(){
  var html = document.documentElement;
  var body = document.body;

  // ── Compact mode: terapkan ke body SEGERA agar tidak ada visual shift ──
  // (html.compact-mode sudah di-set di <head>, tapi CSS body.compact-mode
  //  baru aktif setelah class dipindah ke body)
  if (html.classList.contains('compact-mode')) {
    body.classList.add('compact-mode');
  }

  // ── Dark mode: terapkan ke body SEGERA ──
  if (html.classList.contains('dark-mode')) {
    body.classList.add('dark-mode');
  }

  // ── Sidebar state restore ──
  // AdminLTE PushMenu menyimpan: key="remembersidebar-collapse", value="sidebar-collapse" jika collapsed
  // value="" / lainnya jika user sudah expand
  try {
    var stored = localStorage.getItem('remembersidebar-collapse');
    if (stored !== null && stored !== 'sidebar-collapse') {
      // User sebelumnya expand sidebar → hapus class kolaps sekarang
      body.classList.remove('sidebar-collapse');
    }
  } catch(e) {}
})();
</script>




  <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-center p-4">
        <i class="fa fa-spinner fa-spin" style="font-size:40px"></i>
        <a>Processing, please wait...</a>
      </div>
    </div>
  </div>

  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand">
      <!-- Left navbar links -->
      <ul class="navbar-nav  m-2">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>

      </ul>

      <!-- SEARCH FORM -->
      @php
      $privilege = Auth::user()?->privilege ?? 'merchant';
      @endphp

      @php $searchPrivileges = ['admin','noc','user','payment','accounting','marketing']; @endphp
      @if(in_array($privilege, $searchPrivileges))
      <form action="/customer/search" method="GET" class="navbar-search-form ml-3 d-none d-md-flex">
        <div class="input-group input-group-sm">
          <input class="form-control @error('search') is-invalid @enderror" name="search" id="search" type="search" placeholder="Cari customer..." aria-label="Search" style="min-width:200px">
          <div class="input-group-append">
            <button class="btn-search" type="submit">
              <i class="fas fa-search" style="font-size:12px"></i>
            </button>
          </div>
        </div>
      </form>
      @endif



      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Messages Dropdown Menu -->

        <li class="nav-item">
          <a href="#" id="toggleDarkMode" class="nav-link" title="Toggle Dark Mode">
            <i class="fas fa-moon"></i>
          </a>
        </li>

        <li class="nav-item">
          <a href="#" id="toggleDensity" class="nav-link" title="Toggle Compact Mode">
            <i class="fas fa-compress-alt" style="color:#6b7280"></i>
          </a>
        </li>


        <li class="nav-item dropdown">


          <a class="nav-link" href="/uncloseticket">
            <i class="nav-icon fas fa-ticket-alt"></i>
            <span class="badge badge-danger navbar-badge" data-toggle="tooltip" data-placement="top" title="My Ticket"> {{ $ticket->my_ticket() }}</span>
          </a>

          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">

          </div>
        </li>

        <li class="nav-item dropdown">


          <a id="navbarDropdown" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
            <img src="/storage/users/{{Auth::user()->photo}}" alt="User Avatar" class="navbar-avatar">
          </a>


          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
           <a class="dropdown-item font-weight-bold" >
            {{ Auth::user()->name }} 
          </a>
          <hr>




          @switch ($privilege)

          @case ("admin") 

          <a class="dropdown-item" href="/myticket">
            {{ " My Ticket"}}
          </a>
          <a class="dropdown-item" href="/suminvoice/mytransaction">
            {{ " My Transaction"}}
          </a>
          @break
          @case ("accounting") 

          <a class="dropdown-item" href="/myticket">
            {{ " My Ticket"}}
          </a>
          <a class="dropdown-item" href="/suminvoice/mytransaction">
            {{ " My Transaction"}}
          </a>
          @break
          @case ("merchant") 

          <a class="dropdown-item" href="/payment/mytransaction">
            {{ "Transaction"}}
          </a>
          @break

          @default
          <a class="dropdown-item" href="/myticket">
            {{ " My Ticket"}}
          </a>


          @endswitch



          <hr>
          <a class="dropdown-item" href="{{ url('my-team') }}">
            <i class="fas fa-users mr-1"></i>{{ " My Team" }}
            @php
              try {
                $mySubIds = \Illuminate\Support\Facades\Schema::hasColumn('users', 'supervisor_id')
                  ? Auth::user()->subordinates()->pluck('id')
                  : collect([]);
                $teamPending = $mySubIds->isNotEmpty()
                  ? \App\LeaveRequest::whereIn('user_id',$mySubIds)->where('status','pending')->count()
                  + \App\OvertimeRequest::whereIn('user_id',$mySubIds)->where('status','pending')->count()
                  : 0;
              } catch(\Exception $e) {
                $mySubIds = collect([]);
                $teamPending = 0;
              }
            @endphp
            @if($teamPending > 0) <span class="badge badge-warning ml-1">{{ $teamPending }}</span> @endif
          </a>
          <a class="dropdown-item" href="{{ url('my-pengajuan') }}">
            <i class="fas fa-paper-plane mr-1"></i>{{ " Pengajuan Saya" }}
          </a>
          <a class="dropdown-item" href="{{ url('my-attendance') }}">
            <i class="fas fa-calendar-check mr-1"></i>{{ " Absen & Jadwal Saya" }}
          </a>
          <a class="dropdown-item" href="{{'/user/'.(Auth::user()->id.'/myprofile') }}">
            {{ " My Profile"}}
          </a>

        <!--   <a class="dropdown-item" href="/suminvoice/mytransaction">
            {{ " My Transaction"}}
          </a> -->
          <a class="dropdown-item" href="{{ route('logout') }}"
          onclick="event.preventDefault();
          document.getElementById('logout-form').submit();">
          {{ __('Logout') }}
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
          @csrf
        </form>


      </div>

    </li>


  </ul>
</nav>
<!-- /.navbar -->

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary">
  <!-- Brand Logo -->
  <a href="../../" class="brand-link">
    <img src="{{ tenant_img('favicon.png', 'favicon.png') }}"
    alt="Logo"
    class="brand-image img-circle elevation-3"
    style="opacity: .8">
    <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar mt-1">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
           with font-awesome or any other icon font library -->
           <li class="nav-item">

            @php
              $dashPref = Auth::user()->dashboard_preference ?? null;
              $dashUrl  = in_array($dashPref, ['home-v2','home-v3','home-v4','home-v5','home-admin']) ? url($dashPref) : url('home');
            @endphp
            <a href="{{ $dashUrl }}" class="nav-link">
              <i class="nav-icon  fas fa-tachometer-alt"></i>
              <p>
                Dashboard
              </p>
            </a>

          </li>
          @switch ($privilege)

          @case ("admin") 
          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
          @include('layout/site')
          @include('layout/distpoint')
          @include('layout/olt')
          @include('layout/distrouter')
          @include('layout/map')
          <hr>
          @include('layout/payment')
          @include('layout/marketing')
          @include('layout/accounting')
          @include('layout/transaction')
          <hr>
          @include('layout/hrd')
          @include('layout/tool')
          @include('layout/admin')
          @break
          @case ("noc") 



          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
          @include('layout/site')
          @include('layout/distpoint')
          @include('layout/olt')
          @include('layout/distrouter')
          @include('layout/map')

          <hr>
          @include('layout/tool')
          @break

          @case ("accounting")


          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
        <!--   @include('layout/site')
          @include('layout/distpoint')
          @include('layout/olt')
          @include('layout/distrouter') -->
          @include('layout/map')
          <hr>
          @include('layout/payment')
          @include('layout/marketing')
          @include('layout/accounting')
          @include('layout/transaction')
          @break



          @case ("marketing")


          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')
          <!-- @include('layout/site') -->
          @include('layout/distpoint')
          @include('layout/map')
         <!--  @include('layout/olt')
          @include('layout/distrouter') -->
          <hr>
          <!-- @include('layout/payment') -->
          @include('layout/marketing')
          <!-- @include('layout/accounting') -->
          @break

          @case ("payment")


          @include('layout/customer')
          @include('layout/schedule')
          @include('layout/ticket')
          @include('layout/plan')

          <hr>
          @include('layout/payment')
          @include('layout/marketing')

          @break

          @case ("user")
          @include('layout/customerlite')
          @include('layout/schedule')
          @include('layout/ticket')
          <!-- @include('layout/plan') -->
          @include('layout/site')
          @include('layout/distpoint')
          @include('layout/map')
          <!-- @include('layout/olt') -->
          <!-- @include('layout/distrouter') -->

          <hr>
          @include('layout/tool')
          @break

          @case ("vendor")

          @include('layout/vendor')

          @break

          @case ("merchant")
          @include('layout/customermerchant')
          

          @break

          @default



          @endswitch







        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <div class="row">

      <div class="col-12 p-1 float-sm-right">
        @include('layout/flash-message')
      </div>
    </div>
    @yield('content')
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 2.0.1
    </div>
    <strong>Copyright &copy; 2024 <a href="http://duwija.io">lubax</a>.</strong> All rights
    reserved.
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- ============================================================ -->
<!-- JS CDN Libraries (dipindah dari <head> agar tidak block render) -->
<!-- ============================================================ -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" async
  onload="(function(){var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/leaflet-search/3.0.0/leaflet-search.min.js';document.head.appendChild(s);})()"
  onerror="console.warn('Leaflet CDN failed to load, map features disabled.')"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.11.5/api/sum().js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>

<!-- jQuery Local Plugins -->
{{-- jQuery, DataTables, Bootstrap sudah dimuat di <head> --}}
<!-- Sweetalert -->

<script src="{{url('dashboard/plugins/sweetalert2/sweetalert2.all.js')}}"></script>
<!-- Select2-->

<script src="{{url('dashboard/plugins/select2/js/select2.min.js')}}"></script>
<!-- Itik -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>

<script src="{{url('dashboard/dist/js/itik.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{url('dashboard/dist/js/adminlte.min.js')}}"></script>
<!-- AdminLTE fr demo purposes -->
<script src="{{url('dashboard/dist/js/demo.js')}}"></script>
@stack('summernote-script')


<script>
  $(document).ready(function() {
    $('form').submit(function(e) {
      // Mencegah submit ganda
      $(this).find(':button[type=submit]')
      .addClass("disabled");
      // .html('Processing.. <i class="fa fa-spinner fa-spin"></i>');
      
      // Tampilkan modal loading
      $('#loadingModal').modal({
        backdrop: 'static', // Modal tidak bisa ditutup dengan klik di luar
        keyboard: false     // Modal tidak bisa ditutup dengan tombol escape
      });
    });
  });
</script>
<script>
  function myFunction(a) {
    var productObj = {};

    productObj.id = a;
    productObj._token = '{{csrf_token()}}';


    $.ajax({
      url: '/invoice/mounthlyfee',
      method: 'post',
      data: productObj,
      success: function(data){
               // alert("Mounthly Invoice was Created !!");
        document.getElementById("inv"+productObj.id).innerHTML = '<a class="badge text-white text-center  badge-secondary"> Created</a>';
      },
      error: function(){
        alert("ERROR To Processed !!");
      }
    });
  }
</script>

<script>


  $(document).ready(function(){
        var dpOpts = {
          format: 'yyyy-mm-dd',
          todayHighlight: true,
          autoclose: true,
          enableOnReadonly: false,
        };

        // Initialize on every datepicker input directly (NOT the wrapper div,
        // so the picker never auto-opens on page load)
        var $dpInputs = $('input.datetimepicker-input, input[id="date"]').filter(function(){
          return !$(this).data('datepicker'); // skip already-initialized
        });
        $dpInputs.datepicker(dpOpts);

        // Calendar icon buttons — show the sibling input's picker on click
        $(document).on('click', '[data-toggle="datetimepicker"], .input-group.date .input-group-append, .input-group.date .input-group-prepend', function(e){
          e.stopPropagation();
          var $input = $(this).closest('.input-group').find('input.datetimepicker-input, input[id="date"]').first();
          if ($input.length) {
            $input.datepicker('show');
          }
        });
      });

    </script>
    


    <script>
      $('#sale_customer_filter').click(function() 
      {
        $('#table-sale-customer').DataTable().ajax.reload()
      });

      var table = $('#table-sale-customer').DataTable({
        "responsive": true,
        "autoWidth": true,
        "searching": true,
        "language": {
          "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
        },
        dom: 'Bfrtip',
        buttons: [
          'pageLength','copy', 'excel', 'pdf', 'csv', 'print'
          ],
        serverSide: true,
        ajax: {
          url: '/sale/table_sale_customer',
          method: 'POST',
          
          data: function ( d ) {
           return $.extend( {}, d, {
            "id_sale":$("#id_sale").val(),
            "filter": $("#filter").val(),
            "parameter": $("#parameter").val(),
            "id_status": $("#id_status").val(),
            "id_plan": $("#id_plan").val(),             
          } );
         }
       },
       'columnDefs': [
       {
      "targets": 5, // your case first column
      "className": "text-center",

    },
    {
      "targets": 6, // your case first column
      "className": "text-center",

    },
    {
      "targets": 7, // your case first columnzZxZ
      "className": "text-center",

    }
    ],
       columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        {data: 'customer_id', name: 'customer_id'},
        {data: 'name', name: 'name'},
        {data: 'address', name: 'address'},
        {data: 'plan', name: 'plan'},
        {data: 'price', name: 'price'},
        {data: 'billing_start', name: 'billing_start'},
        {data: 'status_cust', name: 'status_cust'},
                     // {data: 'select', name: 'select'},
        {data: 'invoice', name: 'invoice'},
                     // {data: 'action', name: 'action'}


        ],

     });
   </script>



   <script>
    $('#sales_filter').click(function() 
    {
      $('#table_sales').DataTable().ajax.reload()
    });

    var table = $('#table_sales').DataTable({

      "responsive": true,
      "autoWidth": false,
      "searching": false,
      "language": {
        "processing": "<i class='fa fa-spinner fa-spin'></i>&emsp;Processing ..."
      },
      dom: 'lBfrtip',
      buttons: [
        'copy', 'excel', 'pdf', 'csv', 'print'
        ],
      "lengthMenu": [[25, 50, 100, 200, 500], [25, 50, 100, 200, 500]],
      processing: true,
      serverSide: true,
      ajax: {
        url: '/sale/table_sales',
        method: 'POST',
        // },
        data: function ( d ) {
         return $.extend( {}, d, {

           "id_user": $("#id_user").val(),
           "date_from": $(document.querySelector('[name="date_from"]')).val(),
           "date_end": $(document.querySelector('[name="date_end"]')).val(),             
         } );
       }
     },
 //                 'columnDefs': [
 // //  {
 // //      "targets": , // your case first column
 // //      "className": "text-center",

 // // },
 // {
 //      "targets": 2, // your case first column
 //      "className": "text-center",

 // }
 // ],

     "footerCallback": function ( row, data, start, end, display ) {
      var api = this.api(), data;

            // Remove the formatting to get integer data for summation
      var intVal = function ( i ) {
        return typeof i === 'string' ?
        i.replace(/[\$,]/g, '')*1 :
        typeof i === 'number' ?
        i : 0;
      };

            // Total over all pages
      total_input = api
      .column( 5 )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

            // Total over this page
      pageTotal_input = api
      .column( 5, { page: 'current'} )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

            // Update footer


      total_output = api
      .column( 6 )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

            // Total over this page
      pageTotal_output = api
      .column( 6, { page: 'current'} )
      .data()
      .reduce( function (a, b) {
        return intVal(a) + intVal(b);
      }, 0 );

      $( api.column( 5 ).footer() ).html(
        ' (Rp.'+pageTotal_input.toLocaleString("id-ID")+') <br/> Rp.'+total_input.toLocaleString("id-ID")
        );
            // Update footer
      $( api.column( 6 ).footer() ).html(
       ' (Rp.'+pageTotal_output.toLocaleString("id-ID")+') <br/> Rp.'+total_output.toLocaleString("id-ID")
       );
      $( api.column( 7 ).footer() ).html(
        'Margin per Page : Rp.'+ (pageTotal_input - pageTotal_output).toLocaleString("id-ID")+'<br/>'+
        'Margin Total : Rp.'+ (total_input - total_output).toLocaleString("id-ID")
        );


    },

    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {data: 'date', name: 'date'},
      {data: 'sales', name: 'sales', orderable: false},
      {data: 'customer', name: 'customer', orderable: false},
      {data: 'customer_name', name: 'customer_name'},
      {data: 'input', name: 'input'},
      {data: 'output', name: 'output'},
      {data: 'suminvoice', name: 'suminvoice'},
                     // {data: 'invoice', name: 'invoice'},
                     // {data: 'action', name: 'action'}


      ],



  });
</script>
<script>
  $(function () {
    $("#example1").DataTable({

      "lengthMenu": [[25, 50, 100, 200, -1], [25, 50, 100, 200, "All"]],



      "responsive": true,
      "autoWidth": false,
      dom: 'Bfrtip',
      buttons: [
        'pageLength',
        'copyHtml5',
        'print',

        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
//yajra
 // $('#table-customer thead th').each( function (i) {
 //        var title = $('#table-customer thead th').eq( $(this).index() ).text();
 //        $(this).html( '<input type="text" placeholder="'+title+'" data-index="'+i+'" />' );
 //    } );



 // Filter event handler
    // $( table.table().container() ).on( 'keyup', 'thead input', function () {
    //     table
    //         .column( $(this).data('index') )
    //         .search( this.value )
    //         .draw();
    // } );

    $("#datatablerugilaba").DataTable({

      //  "lengthMenu": [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "All"]],



      "responsive": true,
      "autoWidth": false,
      dom: 'Bfrtip',
      buttons: [
        'pageLength',
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
    $("#datatableneraca").DataTable({

      "lengthMenu": [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "All"]],



      "responsive": true,
      "autoWidth": false,
      dom: 'Bfrtip',
      buttons: [
        'pageLength',
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });

    $('.select2').select2();
      // $('#time').timepicker({ timeFormat: 'HH:mm', startTime: '08:00',dynamic: false,
      //   dropdown: true,});

    $('#time_update').timepicker({ timeFormat: 'hh:mm', startTime: '08:00',dynamic: false,
      dropdown: true,});

    if (typeof $.fn.summernote !== 'undefined') {
      $('.textarea').summernote({
        height: 300,
        dialogsInBody: true,
        callbacks: {
          onInit: function() {
            $('body > .note-popover').hide();
          }
        },
      });
    }
    if ($('#example2').length) {
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
      dom: 'Bfrtip',
      buttons: [
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
    }
    if ($('#example3').length) {
    $('#example3').DataTable({
      "paging": false,
      "lengthChange": false,
      "searching": true,
      "ordering": true,
      "info": false,
      "autoWidth": false,
      "responsive": true,
      dom: 'Bfrtip',
      buttons: [
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdfHtml5'
        ]
    });
    }
  });

</script>

@stack('highcharts-scripts')


<script>
  document.addEventListener("DOMContentLoaded", function() {
    const body = document.body;
    const html = document.documentElement;
    const toggleButton = document.getElementById("toggleDarkMode");

    // Apply preload fix — class sudah dipindah ke body di inline script awal
    // (tidak perlu lagi di sini, tapi pastikan html.dark-mode tetap ada untuk CSS vars)

    // Apply density preload fix
    const densityBtn = document.getElementById("toggleDensity");

    function setDensityIcon() {
      const isCompact = body.classList.contains("compact-mode");
      densityBtn.innerHTML = isCompact
        ? '<i class="fas fa-expand-alt" style="color:#a78bfa"></i>'
        : '<i class="fas fa-compress-alt" style="color:#6b7280"></i>';
      densityBtn.title = isCompact ? 'Switch to Comfortable' : 'Switch to Compact';
    }

    setDensityIcon();

    densityBtn.addEventListener("click", function(e) {
      e.preventDefault();
      body.classList.toggle("compact-mode");
      const isCompact = body.classList.contains("compact-mode");
      html.classList.toggle("compact-mode", isCompact);
      localStorage.setItem("densityMode", isCompact ? "compact" : "comfortable");
      setDensityIcon();
    });

    function setThemeIcon() {
      const isDark = body.classList.contains("dark-mode");
      toggleButton.innerHTML = isDark
        ? '<i class="fas fa-sun" style="color:#fbbf24"></i>'
        : '<i class="fas fa-moon" style="color:#6b7280"></i>';
      toggleButton.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    }

    setThemeIcon();

    toggleButton.addEventListener("click", function(e) {
      e.preventDefault();
      body.classList.toggle("dark-mode");
      const isDark = body.classList.contains("dark-mode");
      // keep html in sync so CSS vars stay consistent
      html.classList.toggle("dark-mode", isDark);
      localStorage.setItem("darkMode", isDark ? "enabled" : "disabled");
      setThemeIcon();
    });
  });
</script>

@yield('footer-scripts')
</body>
</html>
