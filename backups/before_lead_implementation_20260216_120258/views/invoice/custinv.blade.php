<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Invoice</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 8px; /* Padding untuk menyembunyikan bagian atas navbar */
            font-size: 14px; /* Ukuran font umum */
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .table th, .table td {
            font-size: 12px; /* Ukuran font untuk sel tabel */
        }
    </style>
</head>
<body>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-12">
              <div class="container">

                <span class="text-center"><h3 class=""><img style="width:150px; height: auto;" src="/dashboard/dist/img/logoinv.png"> </h3></span>
                <span class="text-center"><h6>{{ $companyName}}</h6></span>

                <span class="text-center"><h6>Customer Invoice </h6></span>
            </div>
            <table class="table ">
                <tbody>
                    <tr>
                        <th style="width: 15%;" class="text-left">CID :</th>
                        <td><strong>{{ $customer->customer_id }}</strong></td>
                    </tr>
                    <tr>
                        <th style="width: 15%;" class="text-left">Name :</th>
                        <td><strong>{{ $customer->name }}</strong></td>
                    </tr>
                    <tr>
                        <th style="width: 15%;" class="text-left">Phone :</th>
                        <td>{{ $customer->phone }}</td>
                    </tr>
                    <tr>
                        <th style="width: 15%;" class="text-left">Address :</th>
                        <td>{{ $customer->address }}</td>
                    </tr>
                    <tr>
                        <th style="width: 15%;" class="text-left">Status :</th>
                        <td><strong>{{ $customer->status_name }}</strong></td>
                    </tr>
                    <tr>
                        <td colspan="2"><h7>{!! $invNote !!}
                        </h7>

                    </td>
                </tr>
            </tbody>
        </table>

        <table id="example1" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">INV Number</th>
                    <th scope="col">Date</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($suminvoice as $suminvoice)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $suminvoice->number }}</td>
                    <td>{{ $suminvoice->date }}</td>
                    @php
                    $sub_total = $suminvoice->total_amount;
                    $tax = $suminvoice->tax;

                    $sum_total = $sub_total;
                    @endphp
                    <td>{{ number_format($sum_total, 0, ',', '.') }}</td>
                    @if($suminvoice->payment_status == 0)
                    <td><span class="badge badge-warning">{{ 'UNPAID' }}</span></td>
                    @elseif($suminvoice->payment_status == 1)
                    <td><span class="badge badge-success"><strong>{{ 'PAID' }}</strong></span></td>
                    @elseif($suminvoice->payment_status == 2)
                    <td><span class="badge badge-secondary">{{ 'CANCEL' }}</span></td>
                    @endif
                    <td>
                        <a href="/suminvoice/{{ $suminvoice->tempcode }}/print" title="detail" class="btn btn-primary btn-sm">
                            <i class="fa fa-list-ul"></i> Show
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <span>
           <!-- <strong><a style="color: red;"> CATATAN PENTING: Pembayaran transfer langsung ke rekening perusahaan PT. Adi Solusindo Teknologi akan kami tutup per 01 Juni 2025</a></strong> -->
       </span>
   </div>

</div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <span class="text-muted">{{ $companyAddress1}}<br> {{ $companyAddress2}}</span>
        <span class="text-muted"><i class="fa fa-whatsapp" aria-hidden="true"></i></span> 
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
