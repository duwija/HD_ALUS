<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticket Wall Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      background: #0d1117;
      color: #e8eaf6;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 20px;
      overflow: hidden;
    }

    /* === Header === */
    .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
    h3 { margin:0; color:#00e5ff; font-weight:600; }
    #clock { font-family:'Courier New',monospace; color:#ffeb3b; font-size:1.5rem; text-shadow:0 0 8px rgba(255,235,59,0.7); }

    /* === Filter === */
    .filter-bar { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px; }
    .filter-select, .filter-date {
      background:#1e293b;
      color:#e0f2f1;
      border:1px solid #00e5ff;
      border-radius:8px;
      padding:6px 12px;
      font-size:0.9rem;
    }
    .filter-select:focus, .filter-date:focus {
      outline:none;
      border-color:#29b6f6;
      box-shadow:0 0 5px #29b6f6;
    }

    /* === Summary === */
    .summary-container {
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-bottom:15px;
    }
    .summary-card {
      flex:1;
      min-width:130px;
      border-radius:10px;
      text-align:center;
      padding:8px;
      font-weight:500;
      box-shadow:0 0 10px rgba(0,0,0,0.4);
      transition:transform .3s ease;
    }
    .summary-card:hover { transform:translateY(-4px); }

    .bg-open{background:linear-gradient(135deg,#b71c1c,#ff1744);color:#fff;}
    .bg-inprogress{background:linear-gradient(135deg,#0d47a1,#2196f3);color:#fff;}
    .bg-pending{background:linear-gradient(135deg,#fbc02d,#fff176);color:#111;}
    .bg-solve{background:linear-gradient(135deg,#1b5e20,#00c853);color:#fff;}
    .bg-close{background:linear-gradient(135deg,#455a64,#90a4ae);color:#fff;}
    .bg-total{background:linear-gradient(135deg,#1a237e,#3949ab);border:1px solid #29b6f6;color:#fff;}

    /* === Ticket Grid === */
    #ticketPages {
      height: calc(100vh - 220px);
      overflow-y: hidden;
      scroll-behavior: smooth;
    }
    .ticket-grid {
      display: grid !important;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 10px;
      transition: opacity .3s ease-in-out;
    }

    /* === Ticket Card === */
    .ticket-card {
      border-radius: 10px;
      padding: 10px 12px 8px;
      min-height: 0;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 0 0 8px rgba(0,0,0,0.4);
      color: #fff;
      transition: transform 0.3s;
    }
    .ticket-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 0 14px rgba(0,0,0,0.6);
    }

    .ticket-open{background:linear-gradient(145deg,#b71c1c,#ff1744);}
    .ticket-inprogress{background:linear-gradient(145deg,#0d47a1,#2196f3);}
    .ticket-pending{background:linear-gradient(145deg,#fbc02d,#fff176);color:#111;}
    .ticket-solve{background:linear-gradient(145deg,#1b5e20,#00c853);}
    .ticket-close{background:linear-gradient(145deg,#455a64,#90a4ae);}

    .ticket-status {
      position: absolute;
      top: 8px;
      right: 10px;
      padding: 3px 8px;
      border-radius: 20px;
      font-size: 0.68rem;
      font-weight: 600;
      color: #fff;
      background: rgba(0,0,0,0.4);
      backdrop-filter: blur(3px);
    }
    .assign-photo {
      width:28px; height:28px; border-radius:50%; object-fit:cover;
      border:2px solid rgba(255,255,255,0.6); flex-shrink:0;
      vertical-align:middle;
    }

    /* === Workflow === */
    .workflow-wrapper { position:relative; margin-top:8px; margin-bottom:6px; }
    .workflow-line { position:absolute; top:14px; left:0; right:0; height:4px; background:rgba(255,255,255,0.25); border-radius:2px; }
    .workflow-progress { position:absolute; top:14px; left:0; height:4px; background:linear-gradient(90deg,#00e5ff,#29b6f6); border-radius:2px; box-shadow:0 0 6px rgba(0,229,255,0.7); transition:width .6s ease; }
    .workflow-steps { display:flex; justify-content:space-between; position:relative; z-index:3; }
    .step-item { text-align:center; flex:1; }
    .step-dot { width:20px; height:20px; border-radius:50%; background:#1c1f26; border:2px solid #777; display:flex; align-items:center; justify-content:center; font-size:9px; color:#bbb; margin:0 auto; top:2px; position:relative; }
    .step-dot.active { background:#00e5ff; border-color:#00bcd4; color:#fff; box-shadow:0 0 8px #00e5ff; }
    .step-dot.done { background:#00c853; border-color:#69f0ae; color:#fff; box-shadow:0 0 6px #69f0ae; }
    .step-label { font-size:0.65rem; margin-top:3px; color:rgba(255,255,255,0.8); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:70px; margin:0 auto; }
    .progress-percent { text-align:right; font-size:0.8rem; margin-top:3px; opacity:0.9; }

    .ticket-footer { position:relative; padding-bottom:16px; }
    .time-row { position:absolute; bottom:0; left:4px; right:4px; display:flex; justify-content:space-between; align-items:center; font-size:0.72rem; }
    .time-left, .time-right { font-weight:600; }

    .fadeIn { animation:fadeIn .5s ease-in-out; }
    @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

    @media (max-width:768px){
      .ticket-card { font-size:0.9rem; padding:10px; }
      .step-dot { width:16px; height:16px; font-size:8px; }
      .workflow-line,.workflow-progress { top:12px; }
      .step-label { font-size:0.55rem; max-width:50px; }
    }

    #btnFullscreen {
      backdrop-filter:blur(8px);
      background:rgba(0,0,0,0.3);
      border:1px solid #00e5ff;
      color:#00e5ff;
      border-radius:8px;
      padding:6px 10px;
    }
  </style>
</head>
<body>

  <div class="header">
    <button id="btnFullscreen"><i class="fas fa-expand"></i></button>
    <h3><i class="fas fa-tv"></i> Ticket Wall Dashboard</h3>
    <div id="clock"></div>
  </div>

  @php
  $categories = \App\Ticketcategorie::orderBy('name')->get();
  $tags = \App\Tag::orderBy('name')->get();
  @endphp

  <div class="filter-bar">
    <label style="color:#ccc;">From:</label>
    <input type="date" id="filterStart" class="filter-date">
    <label style="color:#ccc;">To:</label>
    <input type="date" id="filterEnd" class="filter-date">

    <select id="filterStatus" class="filter-select">
      <option value="all">All Status</option>
      <option value="Open">Open</option>
      <option value="Inprogress">Inprogress</option>
      <option value="Pending">Pending</option>
      <option value="Solve">Solve</option>
      <option value="Close">Close</option>
    </select>

    <select id="filterCategory" class="filter-select">
      <option value="all">All Category</option>
      @foreach($categories as $c)
      <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach
    </select>

    <select id="filterTag" class="filter-select">
      <option value="all">All Tag</option>
      @foreach($tags as $t)
      <option value="{{ $t->id }}">{{ $t->name }}</option>
      @endforeach
    </select>
  </div>

  <div class="summary-container">
    @include('ticket.tvwall-summary')
  </div>

  <div id="ticketPages">
    <div id="ticketGrid" class="ticket-grid">
      @include('ticket.tvwall-cards')
    </div>
  </div>

  <script>
    const tvwallDataUrl = "{{ route('ticket.tvwall.data') }}";

    function updateClock(){
      const now=new Date();
      document.getElementById('clock').textContent=now.toLocaleTimeString('en-GB',{hour12:false});
    }
    setInterval(updateClock,1000);updateClock();

    document.getElementById('btnFullscreen').addEventListener('click',()=>{
      if(!document.fullscreenElement){
        document.documentElement.requestFullscreen();
        btnFullscreen.innerHTML='<i class="fas fa-compress"></i>';
      }else{
        document.exitFullscreen();
        btnFullscreen.innerHTML='<i class="fas fa-expand"></i>';
      }
    });

    let scrollPos=0;
    function autoScroll(){
      const c=document.getElementById('ticketPages');
      const h=c.scrollHeight-c.clientHeight;
      scrollPos=scrollPos<h?scrollPos+2:0;
      c.scrollTo({top:scrollPos,behavior:'smooth'});
    }
    setInterval(autoScroll,150);

    const grid=document.querySelector('#ticketGrid');
    const summaryContainer=document.querySelector('.summary-container');

    async function fetchTickets(showLoading=false){
      try{
        const params=new URLSearchParams({
          status:document.getElementById('filterStatus').value,
          category:document.getElementById('filterCategory').value,
          tag:document.getElementById('filterTag').value,
          start_date:document.getElementById('filterStart').value,
          end_date:document.getElementById('filterEnd').value,
        });
        if(showLoading)grid.style.opacity="0.3";
        const res=await fetch(`${tvwallDataUrl}?${params.toString()}`);
        const data=await res.json();
        fadeReplace(grid,data.grid);
        fadeReplace(summaryContainer,data.summary);
      }catch(e){console.error('Gagal memuat data:',e);}finally{grid.style.opacity="1";}
    }

    function fadeReplace(el,html){
      el.style.opacity='0';
      setTimeout(()=>{
        el.innerHTML=html;
        el.style.opacity='1';
      },150);
    }

    ['filterStatus','filterCategory','filterTag','filterStart','filterEnd'].forEach(id=>{
      document.getElementById(id).addEventListener('change',()=>fetchTickets(true));
    });

    setInterval(fetchTickets,60000);

    const today=new Date().toISOString().split('T')[0];
    document.getElementById('filterStart').value=today;
    document.getElementById('filterEnd').value=today;
    fetchTickets();
  </script>
</body>
</html>
