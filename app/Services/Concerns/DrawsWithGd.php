<?php

namespace App\Services\Concerns;

/**
 * Helper gambar GD bersama untuk kartu PNG (PaymentQrCardService, EmployeeNameTagService):
 * font DejaVu, teks rata tengah (otomatis mengecil / dipotong), kotak membulat, gradasi.
 * Kelas pemakai wajib mengisi $canvasWidth (lebar kanvas, untuk perataan tengah).
 */
trait DrawsWithGd
{
    protected int $canvasWidth = 0;

    protected function findFont(string $file): string
    {
        foreach ([
            base_path('vendor/dompdf/dompdf/lib/fonts/' . $file),
            '/usr/share/fonts/dejavu-sans-fonts/' . $file,
            '/usr/share/fonts/truetype/dejavu/' . $file,
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new \RuntimeException("Font {$file} tidak ditemukan.");
    }

    protected function mix(array $a, array $b, float $t): array
    {
        return [
            (int) round($a[0] + ($b[0] - $a[0]) * $t),
            (int) round($a[1] + ($b[1] - $a[1]) * $t),
            (int) round($a[2] + ($b[2] - $a[2]) * $t),
        ];
    }

    /** Gradasi vertikal dari $top ke $bottom pada area (x, y, w, h). */
    protected function verticalGradient($img, int $x, int $y, int $w, int $h, array $top, array $bottom): void
    {
        for ($i = 0; $i < $h; $i++) {
            $c = imagecolorallocate($img, ...$this->mix($top, $bottom, $h > 1 ? $i / ($h - 1) : 0));
            imageline($img, $x, $y + $i, $x + $w - 1, $y + $i, $c);
        }
    }

    protected function textWidth(string $font, float $size, string $text): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box[2] - $box[0];
    }

    protected function textCenter($img, string $text, string $font, float $size, int $baselineY, int $color, int $letterSpacing = 0): void
    {
        if ($letterSpacing > 0) {
            // Spasi antar huruf manual (untuk label kapital kecil)
            $chars = array_map(fn ($ch) => [$ch, $this->textWidth($font, $size, $ch)], mb_str_split($text));
            $total = array_sum(array_column($chars, 1)) + $letterSpacing * (count($chars) - 1);
            $x = ($this->canvasWidth - $total) / 2;
            foreach ($chars as [$ch, $w]) {
                imagettftext($img, $size, 0, (int) $x, $baselineY, $color, $font, $ch);
                $x += $w + $letterSpacing;
            }
            return;
        }

        $box = imagettfbbox($size, 0, $font, $text);
        $x = (int) (($this->canvasWidth - ($box[2] - $box[0])) / 2) - $box[0];
        imagettftext($img, $size, 0, $x, $baselineY, $color, $font, $text);
    }

    /** Teks di tengah; ukuran font dikecilkan sampai muat di $maxWidth, kalau masih kepanjangan dipotong dengan "…". */
    protected function textCenterFit($img, string $text, string $font, float $size, float $minSize, int $baselineY, int $color, int $maxWidth): void
    {
        $text = trim($text) !== '' ? trim($text) : '-';

        while ($size > $minSize && $this->textWidth($font, $size, $text) > $maxWidth) {
            $size -= 2;
        }
        while (mb_strlen($text) > 4 && $this->textWidth($font, $size, $text) > $maxWidth) {
            $text = rtrim(mb_substr($text, 0, -2)) . '…';
        }

        $this->textCenter($img, $text, $font, $size, $baselineY, $color);
    }

    protected function roundedRect($img, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
        imagefilledellipse($img, $x + $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
    }

    protected function toPng($img): string
    {
        ob_start();
        imagepng($img, null, 6);
        imagedestroy($img);

        return (string) ob_get_clean();
    }
}
