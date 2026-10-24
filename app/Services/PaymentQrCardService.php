<?php

namespace App\Services;

use App\Services\Concerns\DrawsWithGd;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Gambar kartu "Link Pembayaran <tenant>" (PNG, 1080x1350 — rasio 4:5 yang pas di WhatsApp)
 * berisi QR link tagihan + nama & Customer ID pelanggan.
 * Dipakai untuk tombol Download QR dan Kirim via WA di halaman /invoice/{id}, agar hasilnya sama.
 */
class PaymentQrCardService
{
    use DrawsWithGd;

    private const W = 1080;
    private const H = 1350;

    /** Warna tema (RGB). */
    private const TOP    = [13, 110, 253];   // biru
    private const BOTTOM = [32, 201, 151];   // hijau tosca
    private const DARK   = [17, 24, 39];

    private string $fontRegular;
    private string $fontBold;

    public function __construct()
    {
        $this->canvasWidth = self::W;
        $this->fontRegular = $this->findFont('DejaVuSans.ttf');
        $this->fontBold    = $this->findFont('DejaVuSans-Bold.ttf');
    }

    /**
     * @return string PNG binary
     */
    public function render(string $url, string $tenantName, string $customerId, string $customerName, string $footer = ''): string
    {
        $img = imagecreatetruecolor(self::W, self::H);
        imageantialias($img, true);
        imagealphablending($img, true);

        // Latar: gradasi biru → tosca + ornamen lingkaran transparan
        $this->verticalGradient($img, 0, 0, self::W, self::H, self::TOP, self::BOTTOM);
        $soft = imagecolorallocatealpha($img, 255, 255, 255, 112);
        imagefilledellipse($img, self::W - 90, 120, 420, 420, $soft);
        imagefilledellipse($img, 80, self::H - 110, 360, 360, $soft);

        $white = imagecolorallocate($img, 255, 255, 255);

        // Judul
        $this->textCenter($img, 'LINK PEMBAYARAN', $this->fontBold, 30, 120, imagecolorallocatealpha($img, 255, 255, 255, 30), 6);
        $this->textCenterFit($img, $tenantName, $this->fontBold, 64, 34, 205, $white, self::W - 140);
        $this->textCenter($img, 'Scan untuk melihat & membayar tagihan', $this->fontRegular, 28, 262, $white);

        // Kartu putih + bayangan lembut (beberapa lapis tipis yang makin lebar)
        $cardX = 110; $cardY = 320; $cardW = self::W - 220; $cardH = 860;
        foreach ([[18, 122], [12, 118], [6, 114]] as [$spread, $alpha]) {
            $this->roundedRect($img, $cardX - $spread / 2, $cardY + $spread, $cardW + $spread, $cardH, 44 + $spread / 2, imagecolorallocatealpha($img, 0, 0, 0, $alpha));
        }
        $this->roundedRect($img, $cardX, $cardY, $cardW, $cardH, 44, $white);

        // QR
        $qrSize = 560;
        $qr = imagecreatefromstring(QrCode::format('png')->size($qrSize)->margin(0)->errorCorrection('M')->generate($url));
        $qrX = (int) ((self::W - $qrSize) / 2);
        $qrY = $cardY + 70;
        imagecopy($img, $qr, $qrX, $qrY, 0, 0, imagesx($qr), imagesy($qr));
        imagedestroy($qr);

        // Bingkai sudut QR (aksen biru)
        $accent = imagecolorallocate($img, ...self::TOP);
        $this->cornerBrackets($img, $qrX - 26, $qrY - 26, $qrSize + 52, 46, 8, $accent);

        // Garis pemisah putus-putus
        $lineY = $qrY + $qrSize + 60;
        $grey = imagecolorallocate($img, 229, 231, 235);
        for ($x = $cardX + 50; $x < $cardX + $cardW - 50; $x += 22) {
            imagefilledrectangle($img, $x, $lineY, $x + 11, $lineY + 3, $grey);
        }

        // Nama & Customer ID
        $this->textCenterFit($img, $customerName, $this->fontBold, 40, 26, $lineY + 75, imagecolorallocate($img, ...self::DARK), $cardW - 80);

        $pillText = 'ID Pelanggan: ' . $customerId;
        $pillSize = 26;
        $pillW = $this->textWidth($this->fontBold, $pillSize, $pillText) + 60;
        $pillH = 58;
        $pillX = (int) ((self::W - $pillW) / 2);
        $pillY = $lineY + 108;
        $this->roundedRect($img, $pillX, $pillY, $pillW, $pillH, 29, imagecolorallocate($img, 232, 240, 254));
        $this->textCenter($img, $pillText, $this->fontBold, $pillSize, $pillY + 40, $accent);

        // Footer
        $this->textCenter($img, 'Buka kamera HP lalu arahkan ke QR code di atas', $this->fontRegular, 24, self::H - 108, $white);
        if ($footer !== '') {
            $this->textCenterFit($img, $footer, $this->fontBold, 26, 18, self::H - 58, imagecolorallocatealpha($img, 255, 255, 255, 20), self::W - 120);
        }

        return $this->toPng($img);
    }

    /** Siku di empat sudut area QR (gaya "scan frame"). */
    private function cornerBrackets($img, int $x, int $y, int $size, int $len, int $thick, int $color): void
    {
        $x2 = $x + $size; $y2 = $y + $size;
        foreach ([[$x, $y, 1, 1], [$x2, $y, -1, 1], [$x, $y2, 1, -1], [$x2, $y2, -1, -1]] as [$cx, $cy, $dx, $dy]) {
            $hx = $dx > 0 ? [$cx, $cx + $len] : [$cx - $len, $cx];
            $vy = $dy > 0 ? [$cy, $cy + $len] : [$cy - $len, $cy];
            $ty = $dy > 0 ? [$cy, $cy + $thick] : [$cy - $thick, $cy];
            $tx = $dx > 0 ? [$cx, $cx + $thick] : [$cx - $thick, $cx];
            imagefilledrectangle($img, $hx[0], $ty[0], $hx[1], $ty[1], $color);
            imagefilledrectangle($img, $tx[0], $vy[0], $tx[1], $vy[1], $color);
        }
    }
}
