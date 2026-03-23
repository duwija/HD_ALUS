<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar oleh FcmService::send() saat Google FCM mengembalikan
 * errorCode "UNREGISTERED" (HTTP 404).
 *
 * Terjadi ketika device telah uninstall aplikasi atau token sudah kadaluarsa.
 * Handler (misal NotifInvJob) harus menghapus token dari database agar
 * notifikasi berikutnya tidak mencoba mengirim ke token yang tidak valid.
 */
class FcmTokenUnregisteredException extends RuntimeException
{
    protected string $fcmToken;

    public function __construct(string $fcmToken, string $message = '')
    {
        $this->fcmToken = $fcmToken;
        parent::__construct($message ?: "FCM token UNREGISTERED: {$fcmToken}");
    }

    public function getFcmToken(): string
    {
        return $this->fcmToken;
    }
}
