<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * Pengganti driver 'daily' untuk channel tenant (notif, payment, invoice, ...)
 * yang tidak pernah menggagalkan request saat file log tidak bisa ditulis.
 *
 * Nama channel dan key 'path' di config/logging.php tetap sama, sehingga
 * job yang memindahkan path per tenant lewat
 * Config::set("logging.channels.{$ch}.path") + Log::forgetChannel($ch)
 * (NotifInvJob, IsolirJob, IsolirAuto) tetap berfungsi.
 */
class SafeDailyLogger
{
    public function __invoke(array $config)
    {
        $handler = new SafeRotatingFileHandler(
            $config['path'],
            $config['days'] ?? 7,
            Logger::toMonologLevel($config['level'] ?? 'debug'),
            $config['bubble'] ?? true,
            $config['permission'] ?? null,
            $config['locking'] ?? false
        );

        // Format sama dengan driver 'daily' bawaan Laravel
        $formatter = new LineFormatter(null, 'Y-m-d H:i:s', true, true);
        $formatter->includeStacktraces();
        $handler->setFormatter($formatter);

        return new Logger($config['name'] ?? app()->environment(), [$handler]);
    }
}

/**
 * RotatingFileHandler yang menelan kegagalan tulis (mis. file log hari ini
 * tertinggal milik root karena queue worker berjalan sebagai root). Sebelumnya
 * fopen() melempar exception yang membatalkan transaksi pembayaran di
 * SuminvoiceController@update padahal WA konfirmasi sudah terkirim.
 */
class SafeRotatingFileHandler extends RotatingFileHandler
{
    protected function write(array $record): void
    {
        try {
            parent::write($record);
        } catch (\Throwable $e) {
            // Tutup stream yang mungkin setengah terbuka agar tulis berikutnya mencoba lagi
            $this->close();
            error_log(sprintf(
                '[channel-log-failed] file=%s reason=%s original_message=%s',
                $this->url ?? '',
                $e->getMessage(),
                $record['message'] ?? ''
            ));
        }
    }
}
