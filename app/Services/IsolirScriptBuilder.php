<?php

namespace App\Services;

use App\Distrouter;

/**
 * Generator script RouterOS untuk captive portal isolir (metode "ganti profile").
 *
 * Alur di router:
 *  - Profile isolir menaruh IP pelanggan ke address-list "isolir" (dinamis).
 *  - HTTP (port 80) dari address-list isolir di-redirect ke web proxy router,
 *    lalu proxy me-redirect ke https://{domain tenant}/isolir.
 *  - Hanya domain walled garden (portal, CDN aset, payment gateway) + DNS yang boleh lewat,
 *    sisanya di-reject (HTTPS/QUIC gagal cepat, OS captive-check memunculkan popup login).
 *
 * Semua rule diberi comment berawalan "ISOLIR:" supaya script bisa dijalankan ulang
 * (rule lama dihapus dulu) dan bisa di-rollback.
 */
class IsolirScriptBuilder
{
    const ALLOW_LIST = 'isolir-allow';
    const TAG        = 'ISOLIR:';

    /** Aset CDN yang dipakai halaman tagihan (invoice/custinv). */
    const CDN_HOSTS = ['cdn.jsdelivr.net', 'cdnjs.cloudflare.com', 'code.jquery.com'];

    /** Host halaman pembayaran per provider payment gateway. */
    const GATEWAY_HOSTS = [
        'xendit'  => ['checkout.xendit.co', 'assets.xendit.co'],
        'tripay'  => ['tripay.co.id'],
        'duitku'  => ['app-prod.duitku.com', 'api-prod.duitku.com'],
        'duitku2' => ['app-prod.duitku.com', 'api-prod.duitku.com'],
    ];

    public static function isValidHost(string $host): bool
    {
        return (bool) preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host);
    }

    /**
     * Parse textarea domain tambahan (satu per baris / dipisah koma).
     * Mengembalikan [valid[], invalid[]].
     */
    public static function parseHosts(?string $text): array
    {
        $valid = $invalid = [];
        foreach (preg_split('/[\s,]+/', strtolower((string) $text), -1, PREG_SPLIT_NO_EMPTY) as $h) {
            $h = preg_replace('#^https?://#', '', $h);
            $h = rtrim(explode('/', $h)[0], '.');
            if ($h === '') {
                continue;
            }
            if (static::isValidHost($h)) {
                $valid[] = $h;
            } else {
                $invalid[] = $h;
            }
        }
        return [array_values(array_unique($valid)), array_values(array_unique($invalid))];
    }

    /** Host gateway untuk provider yang sedang aktif di tenant ini. */
    public static function gatewayHosts(): array
    {
        $providers = \App\PaymentGateway::where('enabled', 1)->pluck('provider')->all();
        $hosts = [];
        foreach ($providers as $p) {
            $hosts = array_merge($hosts, self::GATEWAY_HOSTS[$p] ?? []);
        }
        return array_values(array_unique($hosts));
    }

    /**
     * @param array $opt domain, profile, ros (6|7), proxy_port, extra_hosts[]
     */
    public static function build(array $opt): string
    {
        $domain  = $opt['domain'];
        $profile = $opt['profile'];
        $ros     = (int) $opt['ros'] === 6 ? 6 : 7;
        $port    = (int) $opt['proxy_port'];
        $pool    = Distrouter::ISOLIR_POOL_NAME;
        $list    = self::ALLOW_LIST;
        $t       = self::TAG;

        $hosts = array_merge(
            [$domain],
            self::CDN_HOSTS,
            static::gatewayHosts(),
            $opt['extra_hosts'] ?? []
        );
        $hosts = array_values(array_unique(array_filter($hosts, [static::class, 'isValidHost'])));

        $redirect = $ros === 6
            ? "action=deny redirect-to=\"{$domain}/isolir\""
            : "action=redirect action-data=\"{$domain}/isolir\"";

        $L = [];
        $L[] = "# ==========================================================";
        $L[] = "# Captive portal ISOLIR - {$domain}";
        $L[] = "# RouterOS v{$ros} | proxy port {$port} | dibuat " . now()->format('Y-m-d H:i');
        $L[] = "# Aman dijalankan ulang: rule lama berkomentar \"{$t}\" dihapus dulu.";
        $L[] = "# ==========================================================";
        $L[] = "";
        $L[] = "# 1. Pool & profile isolir (dilewati jika sudah ada)";
        $L[] = ":if ([:len [/ip pool find name=\"{$pool}\"]] = 0) do={ /ip pool add name=\"{$pool}\" ranges=" . Distrouter::ISOLIR_POOL_RANGES . " }";
        $L[] = ":if ([:len [/ppp profile find name=\"{$profile}\"]] = 0) do={ /ppp profile add name=\"{$profile}\" local-address=" . Distrouter::ISOLIR_LOCAL_ADDR . " remote-address=\"{$pool}\" rate-limit=64k/64k }";
        $L[] = "/ppp profile set [find name=\"{$profile}\"] address-list=isolir dns-server=" . Distrouter::ISOLIR_DNS;
        $L[] = "";
        $L[] = "# 2. Hapus rule isolir lama";
        $L[] = "/ip firewall filter remove [find comment~\"^{$t}\"]";
        $L[] = "/ip firewall nat remove [find comment~\"^{$t}\"]";
        $L[] = "/ip firewall address-list remove [find list=\"{$list}\"]";
        $L[] = "/ip proxy access remove [find comment~\"^{$t}\"]";
        $L[] = "";
        $L[] = "# 3. Walled garden (domain yang tetap boleh diakses pelanggan isolir)";
        foreach ($hosts as $h) {
            $L[] = "/ip firewall address-list add list=\"{$list}\" address={$h} comment=\"{$t} {$h}\"";
        }
        $L[] = "";
        $L[] = "# 4. Web proxy: semua HTTP pelanggan isolir diarahkan ke halaman /isolir";
        $L[] = "/ip proxy set enabled=yes port={$port}";
        // Pakai local-port, bukan src-address subnet pool: pelanggan dengan IP statis (remote-address
        // di secret) tetap memakai IP-nya saat diisolir, jadi tidak berada di subnet pool isolir.
        // Proxy port ini hanya menerima pelanggan isolir (lihat filter input di bawah).
        $L[] = "/ip proxy access add local-port={$port} dst-host={$domain} action=allow comment=\"{$t} portal\"";
        $L[] = "/ip proxy access add local-port={$port} {$redirect} comment=\"{$t} redirect ke portal\"";
        $L[] = "";
        $L[] = "# 5. NAT";
        $L[] = "/ip firewall nat add chain=dstnat src-address-list=isolir dst-address-list=!{$list} protocol=tcp dst-port=80 action=redirect to-ports={$port} comment=\"{$t} HTTP ke proxy\"";
        $L[] = "/ip firewall nat add chain=srcnat src-address-list=isolir dst-address-list={$list} action=masquerade comment=\"{$t} masquerade walled garden\"";
        $L[] = "";
        $L[] = "# 6. Filter";
        $L[] = "/ip firewall filter add chain=input src-address-list=isolir protocol=tcp dst-port={$port} action=accept comment=\"{$t} akses proxy\"";
        $L[] = "/ip firewall filter add chain=input protocol=tcp dst-port={$port} action=drop comment=\"{$t} tutup proxy dari luar\"";
        $L[] = "/ip firewall filter add chain=forward src-address-list=isolir protocol=udp dst-port=53 action=accept comment=\"{$t} DNS udp\"";
        $L[] = "/ip firewall filter add chain=forward src-address-list=isolir protocol=tcp dst-port=53 action=accept comment=\"{$t} DNS tcp\"";
        $L[] = "/ip firewall filter add chain=forward src-address-list=isolir dst-address-list={$list} action=accept comment=\"{$t} walled garden\"";
        $L[] = "/ip firewall filter add chain=forward src-address-list=isolir protocol=tcp action=reject reject-with=tcp-reset comment=\"{$t} blok TCP\"";
        $L[] = "/ip firewall filter add chain=forward src-address-list=isolir action=reject reject-with=icmp-admin-prohibited comment=\"{$t} blok lainnya\"";
        $L[] = "";
        $L[] = "# 7. Pindahkan rule isolir ke paling atas (urutan tetap)";
        // Rule dinamis (mis. dummy fasttrack counter di v7) tidak bisa jadi tujuan move → acuan hanya rule statis
        foreach (['/ip firewall filter' => ' dynamic=no', '/ip firewall nat' => ' dynamic=no', '/ip proxy access' => ''] as $menu => $where) {
            $L[] = "{ :local ids [{$menu} find comment~\"^{$t}\"]; :for i from=([:len \$ids] - 1) to=0 step=-1 do={ :local cur [:pick \$ids \$i]; :local first [:pick [{$menu} find{$where}] 0]; :if (\$cur != \$first) do={ {$menu} move numbers=\$cur destination=\$first } } }";
        }
        $L[] = "";
        $L[] = ":log info \"ISOLIR captive portal terpasang ({$domain})\"";

        return implode("\n", $L) . "\n";
    }

    /** Script untuk membongkar semua rule isolir (proxy dibiarkan menyala). */
    public static function rollback(): string
    {
        $t = self::TAG;
        $list = self::ALLOW_LIST;
        return implode("\n", [
            "# Hapus captive portal ISOLIR",
            "/ip firewall filter remove [find comment~\"^{$t}\"]",
            "/ip firewall nat remove [find comment~\"^{$t}\"]",
            "/ip firewall address-list remove [find list=\"{$list}\"]",
            "/ip proxy access remove [find comment~\"^{$t}\"]",
            "# Matikan web proxy jika tidak dipakai keperluan lain:",
            "# /ip proxy set enabled=no",
        ]) . "\n";
    }
}
