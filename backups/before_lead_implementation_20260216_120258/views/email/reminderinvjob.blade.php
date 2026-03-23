<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notifikasi Tagihan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        td {
            padding: 8px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 20px;
            margin-top: 20px;
            background-color: #007bff;
            color: #fff !important;
            text-decoration: none;
            border-radius: 6px;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <h2>Yth. {{ $data['name'] }},</h2>
        <p><strong>CID : {{ $data['cid'] }} </strong></p>

        <p>Terima kasih telah menggunakan layanan <strong>{{ config('app.name') }}</strong>.</p>

        <p>Kami ingin mengingatkan bahwa tagihan Anda sudah tersedia</p>
        <p>Agar tetap bisa menikmati layanan kami, mohon untuk menyelesaikan pembayaran tepat waktu </p>


        <p>Silakan klik tombol di bawah ini untuk melihat detail tagihan dan melakukan pembayaran melalui portal billing kami:</p>

        <a href="{{ config('app.url') }}{{ $data['url'] }}" class="btn" target="_blank">Lihat Tagihan</a>
        <p></p>

        <p>Abaikan pesan ini jika sudah melakukan pembayaran 
        Terima kasih atas kepercayaan Anda</p>

        <p>Apabila Anda memiliki pertanyaan, silakan hubungi tim layanan pelanggan kami.</p>

        <div class="footer">
            <p>Hormat kami, <br><strong>{{ config('app.name') }}</strong></p>
        </div>
    </div>
</body>
</html>
