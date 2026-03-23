<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ tenant_img('favicon.png', 'favicon.png') }}">
    <title>ALUSNET JOB SCHEDULE</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Responsif */
            grid-auto-rows: min-content; /* Tinggi item sesuai dengan panjang konten */
            gap: 10px; /* Jarak antar kotak */
            padding: 10px;
        }

        .grid-item {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            font-size: 14px;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
/*            justify-content: center;*/
align-items: center;
}
.clock {
    text-align: right;
    font-size: 24px;
    font-weight: bold;

}
.person {
    position: absolute;
    bottom: 0;
}
.time {
    text-align: right;
    font-size: 18 px;
    font-weight: bold;
    align-items: right;
    !important;
}
.grid-item h3 {
    margin: 0 0 10px; /* Jarak antara title dan deskripsi */
    font-size: 16px;
}

.grid-item p {
    margin: 5px 0;
}

/* Ukuran dinamis untuk kotak */
.size-1 { grid-row: span 1; grid-column: span 1; }
.size-2 { grid-row: span 1; grid-column: span 2; }
</style>
</head>
<body> <h1 class="text-center">Today's Schedule</h1>
    <div class="container-fluid">

        <div class="grid-container">
            @php
            $inprogress=0;
            $open = $close = $pending = $solved = 0; // Inisialisasi counter
            @endphp

            @foreach($ticket as $ticket)
            @php
            // Tentukan warna berdasarkan status tiket
            $color = '';
            if ($ticket->status == "Open") {
                $color = 'bg-danger text-light';
                $open++;
            } elseif ($ticket->status == "Close") {
                $color = 'bg-secondary';
                $close++;
            } elseif ($ticket->status == "Pending") {
                $color = 'bg-warning';
                $pending++;
            }
            elseif ($ticket->status == "Inprogress") {
                $color = 'bg-primary';
                $inprogress++;
            } else {
                $color = 'bg-info';
                $solved++;
            }

            // Ukuran kotak dinamis antara 1 dan 2
            $size = 'size-' . rand(1, 2);
            @endphp

            <div class="grid-item {{ $size }} {{ $color }}" style="display: flex; flex-direction: column; height: 100%;"> 
             <a href="/ticket/{{ $ticket->id }}"> <div class="time badge badge-light">{{ $ticket->time }} | CID: {{$ticket->customer->customer_id}} </div></a>
             <p><strong>  {{ $ticket->customer->name }}</strong></p>
             <p> {{ $ticket->tittle }}</p> 
             <div valign="bottom" class="badge badge-light" style="margin-top: auto;"> {{ $ticket->user->name }}</div>
         </div>
         @endforeach
     </div>
 </div>
 <div style="background-color:#a3301c" class="fixed-bottom  p-2">
    <span class="badge badge-danger p-2">OPEN TICKET :  {{$open}}</span>
    <span class="badge badge-secondary p-2">CLOSED TICKET :  {{$close}}</span>
    <span class="badge badge-warning p-2">PENDING TICKET :  {{$pending}}</span>
    <span class="badge badge-info p-2">SOLVED TICKET :  {{$solved}}</span>
    <span class="badge badge-primary p-2">INPROGRESS TICKET :  {{$inprogress}}</span>
    
    <span class="clock float-right text-light" id="clock"></span>


</div>
<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    function updateDateTime() {
        const now = new Date();

            // Format tanggal
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateString = now.toLocaleDateString('en-US', options);

            // Format waktu
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = ` ${dateString} ${hours}:${minutes}:${seconds}`;

            // Update elemen di HTML

        document.getElementById('clock').textContent = timeString;
    }

        // Update tanggal dan waktu segera dan kemudian setiap detik
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>
<script language="javascript">
    setTimeout(function(){
     window.location.reload(1);
 }, 60000);
</script>

</body>
</html>