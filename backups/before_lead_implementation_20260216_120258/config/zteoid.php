<?php

// Multi-Vendor OLT OID Configuration System
// Supports: ZTE (C300/C320/C600/C620/C650), CDATA, HSGQ, Huawei, Fiberhome

/**
 * Get OID configuration based on OLT object
 * Priority: vendor field > type field > name field
 */
if (!function_exists('get_olt_oid_config')) {
    function get_olt_oid_config($olt = null) {
        if (!$olt) {
            return config('zteoid');
        }

        $oltVendor = strtolower($olt->vendor ?? '');
        $oltType = strtolower($olt->type ?? '');
        $oltName = strtolower($olt->name ?? '');
        
        // CDATA Detection
        if (str_contains($oltVendor, 'cdata') || str_contains($oltType, 'cdata') || str_contains($oltName, 'cdata') || str_contains($oltType, 'fdd')) {
            if (config('cdata_oid')) return config('cdata_oid');
            \Log::warning("CDATA OLT detected but config/cdata_oid.php not found");
        }
        
        // HSGQ Detection (EPON vs GPON)
        if (str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq')) {
            // Check if EPON
            if (str_contains($oltType, 'epon') || str_contains($oltName, 'epon')) {
                if (config('hsgq_epon_oid')) return config('hsgq_epon_oid');
                \Log::warning("HSGQ EPON OLT detected but config/hsgq_epon_oid.php not found");
            }
            // Default to GPON
            if (config('hsgq_oid')) return config('hsgq_oid');
            \Log::warning("HSGQ OLT detected but config/hsgq_oid.php not found");
        }
        
        // Huawei Detection
        if (str_contains($oltVendor, 'huawei') || str_contains($oltType, 'huawei') || str_contains($oltName, 'huawei') || str_contains($oltType, 'ma5')) {
            if (config('huawei_oid')) return config('huawei_oid');
            \Log::warning("Huawei OLT detected but config/huawei_oid.php not found");
        }
        
        // Fiberhome Detection
        if (str_contains($oltVendor, 'fiberhome') || str_contains($oltType, 'fiberhome') || str_contains($oltName, 'fiberhome') || str_contains($oltType, 'an5')) {
            if (config('fiberhome_oid')) return config('fiberhome_oid');
            \Log::warning("Fiberhome OLT detected but config/fiberhome_oid.php not found");
        }
        
        // ZTE C600/C620/C650 Detection
        if (str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
            str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650')) {
            return config('zte_c600_oid');
        }
        
        // Default: ZTE C300/C320
        return config('zteoid');
    }
}

/**
 * Get frame/slot/port configuration
 */
if (!function_exists('get_olt_frameslotport_config')) {
    function get_olt_frameslotport_config($olt = null) {
        if (!$olt) return config('zteframeslotportid');

        $oltVendor = strtolower($olt->vendor ?? '');
        $oltType = strtolower($olt->type ?? '');
        $oltName = strtolower($olt->name ?? '');
        
        if (str_contains($oltVendor, 'cdata') || str_contains($oltType, 'cdata') || str_contains($oltName, 'cdata') || str_contains($oltType, 'fdd')) {
            if (config('cdata_frameslotportid')) return config('cdata_frameslotportid');
        }
        
        if (str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq')) {
            // Check if EPON
            if (str_contains($oltType, 'epon') || str_contains($oltName, 'epon')) {
                if (config('hsgq_epon_frameslotportid')) return config('hsgq_epon_frameslotportid');
            }
            if (config('hsgq_frameslotportid')) return config('hsgq_frameslotportid');
        }
        
        if (str_contains($oltVendor, 'huawei') || str_contains($oltType, 'huawei') || str_contains($oltName, 'huawei') || str_contains($oltType, 'ma5')) {
            if (config('huawei_frameslotportid')) return config('huawei_frameslotportid');
        }
        
        if (str_contains($oltVendor, 'fiberhome') || str_contains($oltType, 'fiberhome') || str_contains($oltName, 'fiberhome') || str_contains($oltType, 'an5')) {
            if (config('fiberhome_frameslotportid')) return config('fiberhome_frameslotportid');
        }
        
        if (str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
            str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650')) {
            return config('zte_c600_frameslotportid');
        }
        
        return config('zteframeslotportid');
    }
}

/**
 * Get ONU status mapping configuration
 */
if (!function_exists('get_olt_status_config')) {
    function get_olt_status_config($olt = null) {
        if (!$olt) return config('zteontstatus');

        $oltVendor = strtolower($olt->vendor ?? '');
        $oltType = strtolower($olt->type ?? '');
        $oltName = strtolower($olt->name ?? '');
        
        if (str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq')) {
            // Check if EPON
            if (str_contains($oltType, 'epon') || str_contains($oltName, 'epon')) {
                if (config('hsgq_epon_onustatus')) return config('hsgq_epon_onustatus');
            }
            if (config('hsgq_onustatus')) return config('hsgq_onustatus');
        }
        
        if (str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq')) {
            if (config('hsgq_onustatus')) return config('hsgq_onustatus');
        }
        
        if (str_contains($oltVendor, 'huawei') || str_contains($oltType, 'huawei') || str_contains($oltName, 'huawei') || str_contains($oltType, 'ma5')) {
            if (config('huawei_onustatus')) return config('huawei_onustatus');
        }
        
        if (str_contains($oltVendor, 'fiberhome') || str_contains($oltType, 'fiberhome') || str_contains($oltName, 'fiberhome') || str_contains($oltType, 'an5')) {
            if (config('fiberhome_onustatus')) return config('fiberhome_onustatus');
        }
        
        if (str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
            str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650')) {
            return config('zte_c600_ontstatus');
        }
        
        return config('zteontstatus');
    }
}

/**
 * Get detected vendor name
 */
if (!function_exists('get_olt_vendor')) {
    function get_olt_vendor($olt = null) {
        if (!$olt) return 'ZTE C300/C320';

        $oltVendor = strtolower($olt->vendor ?? '');
        $oltType = strtolower($olt->type ?? '');
        $oltName = strtolower($olt->name ?? '');
        
        if (str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq')) {
            if (str_contains($oltType, 'epon') || str_contains($oltName, 'epon')) {
                return 'HSGQ EPON';
            }
            return 'HSGQ GPON';
        }
        
        if (str_contains($oltVendor, 'hsgq') || str_contains($oltType, 'hsgq') || str_contains($oltName, 'hsgq')) {
            return 'HSGQ';
        }
        
        if (str_contains($oltVendor, 'huawei') || str_contains($oltType, 'huawei') || str_contains($oltName, 'huawei') || str_contains($oltType, 'ma5')) {
            return 'Huawei';
        }
        
        if (str_contains($oltVendor, 'fiberhome') || str_contains($oltType, 'fiberhome') || str_contains($oltName, 'fiberhome') || str_contains($oltType, 'an5')) {
            return 'Fiberhome';
        }
        
        if (str_contains($oltType, 'c600') || str_contains($oltType, 'c620') || str_contains($oltType, 'c650') ||
            str_contains($oltName, 'c600') || str_contains($oltName, 'c620') || str_contains($oltName, 'c650')) {
            return 'ZTE C600/C620/C650';
        }
        
        return 'ZTE C300/C320';
    }
}

/**
 * Encode frame/slot/port to ZTE C600/C620/C650 index format
 * Used for OID branch .1082.500.* which requires encoded index
 * 
 * Formula discovered from C620 hardware (103.156.74.17):
 * (17 << 24) + (frame << 16) + (card_index << 8) + port
 * 
 * Real example:
 * - Port 1/1/1 → 0x11010201 = 285278721
 *   where: magic=17(0x11), frame=1(0x01), card=2(0x02), port=1(0x01)
 * 
 * Note: card_index appears to be slot+1, but may vary by hardware config
 * The magic number 17 (0x11) is constant for PON interface type
 * 
 * @param string $frameSlotPort Format: "frame/slot/port" (e.g., "1/1/1")
 * @return int Encoded index
 */
if (!function_exists('encode_zte_c600_index')) {
    function encode_zte_c600_index($frameSlotPort) {
        $parts = explode('/', $frameSlotPort);
        if (count($parts) !== 3) {
            return 0;
        }
        
        $frame = (int)$parts[0];
        $slot = (int)$parts[1];
        $port = (int)$parts[2];
        
        // Magic number for PON type
        $magic = 17;
        
        // FIX: Use slot directly (slot = cardIndex)
        // Port 1/2/1 in CLI = encoded 0x11010201 = slot 2
        $cardIndex = $slot;
        
        return ($magic << 24) + ($frame << 16) + ($cardIndex << 8) + $port;
    }
}

/**
 * Encode PON/ONU to HSGQ index format
 * HSGQ uses hex format: 0x0100XXYY where XX=PON, YY=ONU
 * 
 * Formula: 0x01000000 + (pon << 8) + onu
 * 
 * Real examples from HSGQ G008:
 * - PON01 ONU01 → 0x01000101 = 16777473
 * - PON02 ONU01 → 0x01000201 = 16777729
 * - PON08 ONU01 → 0x01000801 = 16779265
 * - PON01 ONU128 → 0x01000180 = 16777600
 * 
 * @param int $pon PON port number (1-16)
 * @param int $onu ONU ID (1-128)
 * @return int Encoded index
 */
if (!function_exists('encode_hsgq_index')) {
    function encode_hsgq_index($pon, $onu) {
        $base = 0x01000000; // Fixed prefix for HSGQ
        return $base + ($pon << 8) + $onu;
    }
}

/**
 * Decode HSGQ index to PON/ONU
 * 
 * @param int $index Encoded index (e.g., 16777473)
 * @return array ['pon' => 1, 'onu' => 1]
 */
if (!function_exists('decode_hsgq_index')) {
    function decode_hsgq_index($index) {
        $pon = ($index >> 8) & 0xFF;
        $onu = $index & 0xFF;
        return ['pon' => $pon, 'onu' => $onu];
    }
}

/**
 * Encode PON/ONU to HSGQ EPON index format
 * HSGQ EPON uses simpler index: PON*1000000 + ONU
 * 
 * Note: EPON index format may vary by hardware model
 * This is the most common format observed in HSGQ EPON devices
 * 
 * Real examples from HSGQ EPON:
 * - PON01 ONU01 → 1000001
 * - PON02 ONU01 → 2000001
 * - PON01 ONU64 → 1000064
 * 
 * @param int $pon PON port number (1-16)
 * @param int $onu ONU ID (1-64)
 * @return int Encoded index
 */
if (!function_exists('encode_hsgq_epon_index')) {
    function encode_hsgq_epon_index($pon, $onu) {
        return ($pon * 1000000) + $onu;
    }
}

/**
 * Decode HSGQ EPON index to PON/ONU
 * 
 * @param int $index Encoded index (e.g., 1000001)
 * @return array ['pon' => 1, 'onu' => 1]
 */
if (!function_exists('decode_hsgq_epon_index')) {
    function decode_hsgq_epon_index($index) {
        $pon = (int)floor($index / 1000000);
        $onu = $index % 1000000;
        return ['pon' => $pon, 'onu' => $onu];
    }
}

// Default ZTE C300/C320 OID Configuration
return [
    'oidOltName' => '.1.3.6.1.2.1.1.5.0',
    'oidOltVersion' => '.1.3.6.1.2.1.1.1.0',
    'oidOltUptime' => '.1.3.6.1.2.1.1.3.0',
    'oidOltDesc' => '.1.3.6.1.2.1.1.6.0',
    'oidOltRxPower' => '.1.3.6.1.4.1.3902.1012.3.50.12.1.1.6',
    
    'oidOnuName' => '.1.3.6.1.4.1.3902.1012.3.28.1.1.2',
    'oidOnuStatus' => '.1.3.6.1.4.1.3902.1012.3.28.2.1.4',
    'oidOnuUptime' => '.1.3.6.1.4.1.3902.1012.3.50.11.2.1.20',
    'oidOnuModel' => '.1.3.6.1.4.1.3902.1012.3.50.11.2.1.9',
    'oidOnuSn' => '.1.3.6.1.4.1.3902.1012.3.28.1.1.5',
    'oidOnuDistance' => '.1.3.6.1.4.1.3902.1012.3.11.4.1.2',
    
    'oidOnuRxPower' => '.1.3.6.1.4.1.3902.1012.3.50.12.1.1.10',
    'oidOnuTxPower' => '.1.3.6.1.4.1.3902.1012.3.50.12.1.1.14',
    
    'oidOnuLastOffline' => '.1.3.6.1.4.1.3902.1012.3.28.2.1.6',
    'oidOnuLastOnline' => '.1.3.6.1.4.1.3902.1012.3.28.2.1.5',
    
    'oidOnuGmUp' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.10',
    'oidOnuGm' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.10',
    
    'oidOnuUncfgSn' => '.1.3.6.1.4.1.3902.1012.3.13.3.1.2',
    'oidOnuUncfgSnG' => '.1.3.6.1.4.1.3902.1012.3.13.3.1.3',
    'oidOnuUncfgType' => '.1.3.6.1.4.1.3902.1012.3.13.3.1.10',
    
    'oidOltVlanId' => '.1.3.6.1.4.1.3902.1015.20.2.1.2',
    'oidOltVlanName' => '.1.3.6.1.4.1.3902.1012.3.26.2.1.2',
    'oidOltGmportProfile' => '.1.3.6.1.4.1.3902.1012.3.26.2.1.2',
    'oidOltTconProfile' => '.1.3.6.1.4.1.3902.1012.3.26.1.1.2',
];
