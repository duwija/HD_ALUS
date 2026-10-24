<?php

namespace App\Services;

use App\Services\Concerns\DrawsWithGd;
use App\User;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Name tag / ID card karyawan (PNG portrait, rasio kartu CR80 54 x 85,6 mm) untuk diunduh & dicetak
 * dari halaman Attendance › Employees. QR berisi vCard (nama, jabatan, perusahaan, HP, email).
 */
class EmployeeNameTagService
{
    use DrawsWithGd;

    private const W = 1012;   // 54 mm  (~476 dpi)
    private const H = 1604;   // 85,6 mm

    private const TOP    = [13, 71, 161];    // biru tua
    private const BOTTOM = [0, 150, 136];    // tosca
    private const DARK   = [17, 24, 39];
    private const MUTED  = [107, 114, 128];

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
    public function render(User $user, string $companyName, ?string $logoPath, string $employeeCode, string $footer = '', ?string $companyLegal = null): string
    {
        $companyLegal = trim((string) $companyLegal);
        $img = imagecreatetruecolor(self::W, self::H);
        imageantialias($img, true);
        imagealphablending($img, true);

        $white  = imagecolorallocate($img, 255, 255, 255);
        $dark   = imagecolorallocate($img, ...self::DARK);
        $muted  = imagecolorallocate($img, ...self::MUTED);
        $accent = imagecolorallocate($img, ...self::TOP);
        imagefilledrectangle($img, 0, 0, self::W, self::H, $white);

        // ── Header gradasi dengan lengkung bawah ─────────────────────────
        $bandH = 560;
        $this->verticalGradient($img, 0, 0, self::W, $bandH, self::TOP, self::BOTTOM);
        imagefilledellipse($img, (int) (self::W / 2), $bandH + 40, self::W + 520, 260, $white);
        $soft = imagecolorallocatealpha($img, 255, 255, 255, 110);
        imagefilledellipse($img, self::W - 60, 70, 360, 360, $soft);
        imagefilledellipse($img, 40, 360, 220, 220, $soft);

        // Lubang tali (dekoratif)
        // (warna solid — warna transparan akan menumpuk di bagian yang saling menimpa pada roundedRect)
        $this->roundedRect($img, (int) (self::W / 2) - 90, 40, 180, 34, 17, imagecolorallocate($img, 196, 214, 240));

        // Logo di atas pil putih + nama tenant (+ nama perusahaan bila ada).
        // Dengan nama perusahaan, header dirapatkan supaya 3 baris teks muat di atas foto (mulai y≈340).
        $compact = $companyLegal !== '';
        $pillY = $compact ? 82 : 100;
        $nameY = $compact ? 175 : 205;
        if ($logoPath && is_file($logoPath) && ($logo = @imagecreatefromstring((string) file_get_contents($logoPath)))) {
            // Logo sering punya ruang kosong di sekelilingnya: tempel ke latar putih
            // (piksel transparan bisa punya RGB acak), lalu buang tepi yang hampir putih.
            $flat = imagecreatetruecolor(imagesx($logo), imagesy($logo));
            imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $logo, 0, 0, 0, 0, imagesx($logo), imagesy($logo));
            imagedestroy($logo);
            $logo = $flat;
            if (($cropped = @imagecropauto($logo, IMG_CROP_THRESHOLD, 0.3, 0xFFFFFF)) !== false) {
                imagedestroy($logo);
                $logo = $cropped;
            }
            $lh = $compact ? 70 : 82;
            $lw = (int) round(imagesx($logo) * $lh / max(1, imagesy($logo)));
            $lw = min($lw, 360);
            $pillW = $lw + 56;
            $px = (int) ((self::W - $pillW) / 2);
            $this->roundedRect($img, $px, $pillY, $pillW, $lh + 30, 24, $white);
            imagecopyresampled($img, $logo, $px + 28, $pillY + 15, 0, 0, $lw, $lh, imagesx($logo), imagesy($logo));
            imagedestroy($logo);
            $nameY = $compact ? 238 : 270;
        }
        $subtle = imagecolorallocatealpha($img, 255, 255, 255, 25);
        $this->textCenterFit($img, mb_strtoupper($companyName), $this->fontBold, 40, 24, $nameY, $white, self::W - 120);
        if ($compact) {
            $this->textCenterFit($img, $companyLegal, $this->fontRegular, 23, 16, $nameY + 37, $white, self::W - 140);
            $this->textCenter($img, 'KARTU IDENTITAS KARYAWAN', $this->fontRegular, 17, $nameY + 70, $subtle, 4);
        } else {
            $this->textCenter($img, 'KARTU IDENTITAS KARYAWAN', $this->fontRegular, 21, $nameY + 46, $subtle, 4);
        }

        // ── Foto bulat berbingkai putih ──────────────────────────────────
        $d = 400;
        $cx = (int) (self::W / 2);
        $cy = 560;
        imagefilledellipse($img, $cx, $cy + 10, $d + 56, $d + 56, imagecolorallocatealpha($img, 0, 0, 0, 112)); // bayangan
        imagefilledellipse($img, $cx, $cy, $d + 40, $d + 40, $white);
        $this->drawCirclePhoto($img, $user, $cx, $cy, $d);
        foreach ([0, 1, 2] as $i) {
            imageellipse($img, $cx, $cy, $d + $i, $d + $i, $white); // haluskan tepi foto
        }

        // ── Nama, jabatan, jenis karyawan ────────────────────────────────
        [$displayName, $nickname] = $this->names($user);
        $this->textCenterFit($img, $displayName, $this->fontBold, 54, 30, 870, $dark, self::W - 100);
        if ($nickname !== null) {
            $this->textCenterFit($img, '"' . $nickname . '"', $this->fontRegular, 28, 20, 918, $muted, self::W - 140);
        }

        $job = mb_strtoupper(trim((string) $user->job_title) ?: 'KARYAWAN');
        $jobSize = 28;
        while ($jobSize > 18 && $this->textWidth($this->fontBold, $jobSize, $job) > self::W - 220) {
            $jobSize -= 2;
        }
        $pillW = $this->textWidth($this->fontBold, $jobSize, $job) + 80;
        $pillX = (int) ((self::W - $pillW) / 2);
        $this->roundedRect($img, $pillX, 958, $pillW, 66, 33, $accent);
        $this->textCenter($img, $job, $this->fontBold, $jobSize, 1004, $white);

        if (trim((string) $user->employee_type) !== '') {
            $this->textCenter($img, trim((string) $user->employee_type), $this->fontRegular, 24, 1072, $muted);
        }

        // ── Info + QR vCard ──────────────────────────────────────────────
        $grey = imagecolorallocate($img, 229, 231, 235);
        for ($x = 70; $x < self::W - 70; $x += 22) {
            imagefilledrectangle($img, $x, 1110, $x + 11, 1113, $grey);
        }

        // ID karyawan di kiri, sejajar tengah dengan QR di kanan
        $leftX = 80;
        imagettftext($img, 22, 0, $leftX, 1270, $muted, $this->fontBold, 'ID KARYAWAN');
        $idSize = 44;
        while ($idSize > 24 && $this->textWidth($this->fontBold, $idSize, $employeeCode) > 440) {
            $idSize -= 2;
        }
        imagettftext($img, $idSize, 0, $leftX, 1336, $dark, $this->fontBold, $employeeCode);
        imagefilledrectangle($img, $leftX, 1362, $leftX + 90, 1368, $accent);

        $qrSize = 290;
        $qrX = self::W - 80 - $qrSize;
        $qrY = 1158;
        $this->roundedRect($img, $qrX - 14, $qrY - 14, $qrSize + 28, $qrSize + 28, 18, imagecolorallocate($img, 243, 244, 246));
        $qr = imagecreatefromstring(QrCode::format('png')->size($qrSize)->margin(0)->errorCorrection('M')
            ->encoding('UTF-8')->generate($this->vcard($user, $companyName)));
        imagecopy($img, $qr, $qrX, $qrY, 0, 0, imagesx($qr), imagesy($qr));
        imagedestroy($qr);
        $label = 'Scan simpan kontak';
        imagettftext($img, 17, 0, $qrX + (int) (($qrSize - $this->textWidth($this->fontRegular, 17, $label)) / 2), $qrY + $qrSize + 42, $muted, $this->fontRegular, $label);

        // ── Footer ───────────────────────────────────────────────────────
        $this->verticalGradient($img, 0, self::H - 72, self::W, 72, self::TOP, self::BOTTOM);
        if ($footer !== '') {
            $this->textCenterFit($img, $footer, $this->fontBold, 22, 16, self::H - 27, $white, self::W - 80);
        }

        return $this->toPng($img);
    }

    /** Foto karyawan dipotong persegi di tengah lalu digambar sebagai lingkaran; tanpa foto → inisial. */
    private function drawCirclePhoto($img, User $user, int $cx, int $cy, int $d): void
    {
        $src = null;
        $path = $user->photo ? public_path('storage/users/' . basename((string) $user->photo)) : null;
        if ($path && is_file($path)) {
            $src = @imagecreatefromstring((string) file_get_contents($path));
        }

        if (!$src) {
            imagefilledellipse($img, $cx, $cy, $d, $d, imagecolorallocate($img, 224, 231, 255));
            $initials = collect(preg_split('/\s+/', $this->names($user)[0]))
                ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
            $size = 120;
            $box = imagettfbbox($size, 0, $this->fontBold, $initials ?: '?');
            imagettftext($img, $size, 0, $cx - (int) (($box[2] - $box[0]) / 2) - $box[0], $cy + (int) ($size / 2),
                imagecolorallocate($img, ...self::TOP), $this->fontBold, $initials ?: '?');
            return;
        }

        // Potong persegi di tengah, perkecil ke diameter lingkaran
        $sw = imagesx($src); $sh = imagesy($src);
        $side = min($sw, $sh);
        $square = imagecreatetruecolor($d, $d);
        imagecopyresampled($square, $src, 0, 0, (int) (($sw - $side) / 2), (int) (($sh - $side) / 2), $d, $d, $side, $side);
        imagedestroy($src);

        $r = $d / 2;
        $r2 = $r * $r;
        $x0 = $cx - (int) $r; $y0 = $cy - (int) $r;
        for ($y = 0; $y < $d; $y++) {
            $dy = $y - $r + 0.5;
            for ($x = 0; $x < $d; $x++) {
                $dx = $x - $r + 0.5;
                if ($dx * $dx + $dy * $dy <= $r2) {
                    imagesetpixel($img, $x0 + $x, $y0 + $y, imagecolorat($square, $x, $y));
                }
            }
        }
        imagedestroy($square);
    }

    /**
     * [nama utama, nama panggilan|null] — nama yang lebih panjang dijadikan nama utama,
     * karena di data sebagian karyawan kolom name/full_name terisi tertukar.
     */
    private function names(User $user): array
    {
        $a = trim((string) $user->full_name);
        $b = trim((string) $user->name);
        if ($a === '' || $b === '' || strcasecmp($a, $b) === 0) {
            return [$a ?: $b, null];
        }

        return mb_strlen($a) >= mb_strlen($b) ? [$a, $b] : [$b, $a];
    }

    private function vcard(User $user, string $companyName): string
    {
        $esc = fn ($v) => str_replace([',', ';', "\n"], ['\,', '\;', ' '], trim((string) $v));
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:' . $esc($this->names($user)[0]),
            'ORG:' . $esc($companyName),
        ];
        if ($user->job_title) { $lines[] = 'TITLE:' . $esc($user->job_title); }
        if ($user->phone)     { $lines[] = 'TEL;TYPE=CELL:' . $esc($user->phone); }
        if ($user->email)     { $lines[] = 'EMAIL:' . $esc($user->email); }
        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }
}
