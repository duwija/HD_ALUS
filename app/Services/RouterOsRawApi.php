<?php

namespace App\Services;

/**
 * Klien API RouterOS minimal untuk transfer data besar (mis. /file/read).
 *
 * evilfreelancer/routeros-api-php membaca tiap word dengan satu fread(); untuk word besar
 * (ribuan byte) fread sering hanya mendapat sebagian, sehingga respons tidak sinkron dan
 * berakhir "Stream timed out". Di sini setiap word dibaca sampai lengkap.
 * Login: metode plain (RouterOS >= 6.43).
 */
class RouterOsRawApi
{
    private $sock;

    /** Atribut pada sentence !done perintah terakhir (mis. =ret= dari /execute as-string). */
    public $lastDone = [];

    public function __construct(string $host, int $port, string $user, string $password, int $timeout = 30)
    {
        $this->sock = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10);
        if (!$this->sock) {
            throw new \RuntimeException("Tidak bisa terhubung ke {$host}:{$port} ({$errstr})");
        }
        stream_set_timeout($this->sock, $timeout);

        $this->command(['/login', '=name=' . $user, '=password=' . $password]);
    }

    public function __destruct()
    {
        if ($this->sock) {
            @fclose($this->sock);
        }
    }

    /**
     * Kirim satu perintah, kembalikan daftar sentence (!re/!done) sebagai array atribut.
     * Melempar exception bila router membalas !trap / !fatal.
     *
     * @return array<int, array<string, string>>
     */
    public function command(array $words): array
    {
        foreach ($words as $w) {
            fwrite($this->sock, $this->encodeLength(strlen($w)) . $w);
        }
        fwrite($this->sock, chr(0));

        $rows = [];
        $trap = null;
        while (true) {
            $sentence = $this->readSentence();
            $type = array_shift($sentence);
            $attrs = [];
            foreach ($sentence as $word) {
                if ($word !== '' && $word[0] === '=' && ($eq = strpos($word, '=', 1)) !== false) {
                    $attrs[substr($word, 1, $eq - 1)] = substr($word, $eq + 1);
                }
            }
            if ($type === '!re') {
                $rows[] = $attrs;
            } elseif ($type === '!trap') {
                $trap = $attrs['message'] ?? 'trap';
            } elseif ($type === '!fatal') {
                throw new \RuntimeException('RouterOS fatal: ' . implode(' ', $sentence));
            } elseif ($type === '!done') {
                $this->lastDone = $attrs;
                if ($trap !== null) {
                    throw new \RuntimeException($trap);
                }
                return $rows;
            }
        }
    }

    private function readSentence(): array
    {
        $words = [];
        while (($len = $this->readLength()) > 0) {
            $words[] = $this->readBytes($len);
        }

        return $words;
    }

    private function readBytes(int $n): string
    {
        $buf = '';
        while (strlen($buf) < $n) {
            $chunk = fread($this->sock, $n - strlen($buf));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->sock);
                throw new \RuntimeException($meta['timed_out'] ? 'Timeout membaca dari router' : 'Koneksi router terputus');
            }
            $buf .= $chunk;
        }

        return $buf;
    }

    private function readLength(): int
    {
        $c = ord($this->readBytes(1));
        if ($c < 0x80) {
            return $c;
        }
        if ($c < 0xC0) {
            return (($c & 0x3F) << 8) | ord($this->readBytes(1));
        }
        if ($c < 0xE0) {
            return (($c & 0x1F) << 16) | unpack('n', $this->readBytes(2))[1];
        }
        if ($c < 0xF0) {
            return (($c & 0x0F) << 24) | (ord($this->readBytes(1)) << 16) | unpack('n', $this->readBytes(2))[1];
        }

        return unpack('N', $this->readBytes(4))[1];
    }

    private function encodeLength(int $l): string
    {
        if ($l < 0x80) {
            return chr($l);
        }
        if ($l < 0x4000) {
            return pack('n', $l | 0x8000);
        }
        if ($l < 0x200000) {
            return chr(($l >> 16) | 0xC0) . pack('n', $l & 0xFFFF);
        }
        if ($l < 0x10000000) {
            return pack('N', $l | 0xE0000000);
        }

        return chr(0xF0) . pack('N', $l);
    }
}
