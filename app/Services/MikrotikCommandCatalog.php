<?php

namespace App\Services;

/**
 * Daftar perintah MikroTik (RouterOS API) yang boleh dijalankan dari menu
 * "MikroTik Commands" di halaman distrouter. Hanya perintah baca (print):
 * DistrouterController@executeCommand menolak apa pun di luar daftar ini.
 *
 * Tiap item: label, path RouterOS API, kolom utama (ditampilkan lebih dulu;
 * kolom lain disembunyikan tapi bisa dimunculkan), dan catatan opsional.
 */
class MikrotikCommandCatalog
{
    const GROUPS = [
        'System' => [
            'system-resource'   => ['Resource', '/system/resource/print', ['uptime', 'version', 'board-name', 'cpu-load', 'cpu-count', 'free-memory', 'total-memory', 'free-hdd-space', 'total-hdd-space', 'architecture-name']],
            'system-identity'   => ['Identity', '/system/identity/print', ['name']],
            'system-routerboard'=> ['RouterBOARD', '/system/routerboard/print', ['model', 'serial-number', 'firmware-type', 'current-firmware', 'upgrade-firmware']],
            'system-clock'      => ['Clock', '/system/clock/print', ['date', 'time', 'time-zone-name', 'gmt-offset']],
            'system-health'     => ['Health', '/system/health/print', ['name', 'value', 'type', 'voltage', 'temperature']],
            'system-package'    => ['Package', '/system/package/print', ['name', 'version', 'build-time', 'disabled']],
            'system-scheduler'  => ['Scheduler', '/system/scheduler/print', ['name', 'start-date', 'start-time', 'interval', 'next-run', 'run-count', 'on-event', 'disabled']],
            'user-active'       => ['User aktif', '/user/active/print', ['name', 'address', 'via', 'when', 'group']],
        ],
        'IP' => [
            'ip-address'        => ['Addresses', '/ip/address/print', ['address', 'network', 'interface', 'comment', 'disabled', 'dynamic']],
            'ip-route'          => ['Route', '/ip/route/print', ['dst-address', 'gateway', 'distance', 'routing-table', 'routing-mark', 'comment', 'active', 'dynamic', 'disabled']],
            'ip-dns'            => ['DNS', '/ip/dns/print', ['servers', 'dynamic-servers', 'allow-remote-requests', 'cache-size', 'cache-used']],
            'ip-arp'            => ['ARP', '/ip/arp/print', ['address', 'mac-address', 'interface', 'comment', 'dynamic', 'complete']],
            'ip-dhcp-lease'     => ['DHCP Lease', '/ip/dhcp-server/lease/print', ['address', 'mac-address', 'host-name', 'server', 'status', 'expires-after', 'last-seen', 'comment', 'dynamic', 'disabled']],
            'ip-dhcp-client'    => ['DHCP Client', '/ip/dhcp-client/print', ['interface', 'status', 'address', 'gateway', 'dhcp-server', 'expires-after', 'disabled']],
            'ip-pool'           => ['Pool', '/ip/pool/print', ['name', 'ranges', 'next-pool', 'comment']],
            'ip-neighbor'       => ['Neighbors', '/ip/neighbor/print', ['interface', 'address', 'mac-address', 'identity', 'platform', 'board', 'version', 'uptime']],
            'ip-service'        => ['Services', '/ip/service/print', ['name', 'port', 'address', 'certificate', 'disabled']],
            'ip-hotspot-active' => ['Hotspot Active', '/ip/hotspot/active/print', ['user', 'address', 'mac-address', 'uptime', 'bytes-in', 'bytes-out', 'server']],
        ],
        'Firewall' => [
            'fw-filter'         => ['Filter', '/ip/firewall/filter/print', ['chain', 'action', 'protocol', 'src-address', 'dst-address', 'dst-port', 'in-interface', 'src-address-list', 'dst-address-list', 'comment', 'bytes', 'packets', 'disabled', 'dynamic']],
            'fw-nat'            => ['NAT', '/ip/firewall/nat/print', ['chain', 'action', 'protocol', 'src-address', 'dst-address', 'dst-port', 'out-interface', 'to-addresses', 'to-ports', 'comment', 'bytes', 'packets', 'disabled']],
            'fw-mangle'         => ['Mangle', '/ip/firewall/mangle/print', ['chain', 'action', 'new-packet-mark', 'new-connection-mark', 'new-routing-mark', 'src-address', 'dst-address', 'comment', 'bytes', 'packets', 'disabled']],
            'fw-raw'            => ['Raw', '/ip/firewall/raw/print', ['chain', 'action', 'src-address', 'dst-address', 'protocol', 'comment', 'bytes', 'packets', 'disabled']],
            'fw-address-list'   => ['Address List', '/ip/firewall/address-list/print', ['list', 'address', 'comment', 'timeout', 'creation-time', 'dynamic', 'disabled']],
        ],
        'Interface' => [
            'if-all'            => ['Interface', '/interface/print', ['name', 'type', 'comment', 'mac-address', 'actual-mtu', 'last-link-up-time', 'link-downs', 'running', 'disabled']],
            'if-ethernet'       => ['Ethernet', '/interface/ethernet/print', ['name', 'default-name', 'speed', 'auto-negotiation', 'mac-address', 'comment', 'running', 'disabled']],
            'if-vlan'           => ['VLAN', '/interface/vlan/print', ['name', 'vlan-id', 'interface', 'comment', 'running', 'disabled']],
            'if-bridge'         => ['Bridge', '/interface/bridge/print', ['name', 'protocol-mode', 'vlan-filtering', 'comment', 'running', 'disabled']],
            'if-bridge-port'    => ['Bridge Port', '/interface/bridge/port/print', ['interface', 'bridge', 'pvid', 'comment', 'disabled']],
            'if-list-member'    => ['Interface List', '/interface/list/member/print', ['list', 'interface', 'comment', 'disabled']],
            'if-pppoe-client'   => ['PPPoE Client', '/interface/pppoe-client/print', ['name', 'interface', 'user', 'service-name', 'comment', 'running', 'disabled']],
            'if-wireless-reg'   => ['Wireless Clients', '/interface/wireless/registration-table/print', ['interface', 'mac-address', 'signal-strength', 'tx-rate', 'rx-rate', 'uptime', 'last-ip']],
        ],
        'Routing' => [
            'bgp-peer-6'        => ['BGP Peer', '/routing/bgp/peer/print', ['name', 'remote-address', 'remote-as', 'state', 'uptime', 'prefix-count', 'disabled'], 'ROS 6'],
            'bgp-session-7'     => ['BGP Session', '/routing/bgp/session/print', ['name', 'remote.address', 'remote.as', 'established', 'uptime', 'prefix-count'], 'ROS 7'],
            'ospf-neighbor'     => ['OSPF Neighbor', '/routing/ospf/neighbor/print', ['instance', 'router-id', 'address', 'interface', 'state', 'adjacency']],
            'routing-filter-6'  => ['Filter', '/routing/filter/print', ['chain', 'action', 'prefix', 'prefix-length', 'comment', 'disabled'], 'ROS 6'],
            'routing-filter-7'  => ['Filter Rule', '/routing/filter/rule/print', ['chain', 'rule', 'comment', 'disabled'], 'ROS 7'],
        ],
        'PPP' => [
            'ppp-active'        => ['Active', '/ppp/active/print', ['name', 'service', 'caller-id', 'address', 'uptime', 'encoding']],
            'ppp-secret'        => ['Secret', '/ppp/secret/print', ['name', 'password', 'service', 'profile', 'remote-address', 'last-logged-out', 'last-disconnect-reason', 'comment', 'disabled']],
            'ppp-profile'       => ['Profile', '/ppp/profile/print', ['name', 'local-address', 'remote-address', 'rate-limit', 'parent-queue', 'dns-server', 'only-one', 'comment']],
        ],
        'Queue' => [
            'queue-simple'      => ['Simple Queue', '/queue/simple/print', ['name', 'target', 'max-limit', 'limit-at', 'burst-limit', 'parent', 'rate', 'bytes', 'comment', 'disabled', 'dynamic']],
            'queue-tree'        => ['Queue Tree', '/queue/tree/print', ['name', 'parent', 'packet-mark', 'max-limit', 'limit-at', 'priority', 'rate', 'bytes', 'comment', 'disabled']],
            'queue-type'        => ['Queue Type', '/queue/type/print', ['name', 'kind', 'pcq-rate', 'pcq-classifier']],
        ],
        'Tools' => [
            'tool-netwatch'     => ['Netwatch', '/tool/netwatch/print', ['host', 'type', 'interval', 'status', 'since', 'comment', 'disabled']],
        ],
    ];

    /** Satu item berdasarkan key, atau null bila tidak ada di daftar. */
    public static function find(?string $key): ?array
    {
        foreach (self::GROUPS as $group => $items) {
            if ($key !== null && isset($items[$key])) {
                [$label, $path, $columns] = $items[$key];

                return [
                    'key' => $key,
                    'group' => $group,
                    'label' => $label,
                    'path' => $path,
                    'columns' => $columns,
                    'note' => $items[$key][3] ?? null,
                ];
            }
        }

        return null;
    }

    /** Key berdasarkan path (kompatibel dengan request lama yang mengirim path). */
    public static function keyForPath(?string $path): ?string
    {
        foreach (self::GROUPS as $items) {
            foreach ($items as $key => $item) {
                if ($item[1] === $path) {
                    return $key;
                }
            }
        }

        return null;
    }
}
