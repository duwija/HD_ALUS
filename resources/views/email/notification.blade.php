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
        <p>Terima kasih telah menggunakan layanan <strong>{{ config('app.name') }}</strong>.</p>

        <p>Kami ingin menginformasikan bahwa tagihan WiFi Anda untuk periode berikut telah diterbitkan. Berikut detailnya:</p>

        <table>
            <tr>
                <td><strong>Nama</strong></td>
                <td>: {{ $data['name'] }}</td>
            </tr>
            <tr>
                <td><strong>CID</strong></td>
                <td>: {{ $data['customer_id'] }}</td>
            </tr>
            <tr>
                <td><strong>No. Invoice</strong></td>
                <td>: {{ $data['number'] }}</td>
            </tr>
            <tr>
                <td><strong>Total Tagihan</strong></td>
                <td>: Rp {{ number_format($data['total_amount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Periode</strong></td>
                <td>: {{ $data['date'] }}</td>
            </tr>
            <tr>
                <td><strong>Jatuh Tempo</strong></td>
                <td>: {{ $data['due_date'] }}</td>
            </tr>
        </table>

        <p>Silakan klik tombol di bawah ini untuk melihat detail tagihan dan melakukan pembayaran melalui portal billing kami:</p>

        <a href="{{ config('app.url') }}{{ $data['url'] }}" class="btn" target="_blank">Lihat Tagihan</a>
        <p></p>

        <p>Agar selalu dapat menikmati layanan kami, Segera lakukan pembayaran sebelum tanggal jatuh tempo.</p>

        <p>Apabila Anda memiliki pertanyaan, silakan hubungi tim layanan pelanggan kami.</p>

        <div class="footer">
            <p>Hormat kami, <br><strong>{{ config('app.name') }}</strong></p>
        </div>
    </div>
</body>
</html>
