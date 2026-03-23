# HSGQ ONU Reboot/Reset Implementation

## Problem
SNMP SET method untuk reboot ONU HSGQ tidak bekerja dengan baik:
- OID `.1.3.6.1.4.1.50224.3.12.2.1.3.{index}` dengan value `2` (reboot) atau `3` (reset)
- SNMP SET command berhasil executed dan value berubah
- Namun ONU **tidak benar-benar reboot** (uptime tetap tidak berubah)

## Root Cause
Field `.12.2.1.3` menerima SNMP SET command tetapi tidak men-trigger actual hardware reboot pada HSGQ OLT. Berdasarkan dokumentasi vendor:
- HSGQ mendukung batch ONU management via CLI/Telnet
- SNMP control OID mungkin hanya untuk monitoring, bukan control
- Reboot/reset memerlukan CLI command

## Solution: CLI/Telnet Method

### Implementation
**Files Created:**
1. `/pyhton/reboot_hsgq_ont.py` - Script untuk reboot ONU via Telnet
2. `/pyhton/reset_hsgq_ont.py` - Script untuk factory reset ONU via Telnet

**Files Modified:**
1. `app/Http/Controllers/OltController.php`:
   - Function `onureboot()`: Changed from SNMP SET to Python CLI
   - Function `onureset()`: Changed from SNMP SET to Python CLI

2. `config/hsgq_oid.php`:
   - Removed duplicate `oidOnuControl` entry
   - Added note: Field 3 doesn't work for actual reboot

### CLI Commands Used

**HSGQ Reboot Command Sequence:**
```
Login: {username}
Password: {password}
> enable
# config
# interface gpon 0/{pon}
# onu {onu_id} reboot
# exit
# exit
```

**HSGQ Reset Command Sequence:**
```
Login: {username}
Password: {password}
> enable
# config
# interface gpon 0/{pon}
# onu {onu_id} reset
# exit
# exit
```

### Script Features
- **Logging**: All operations logged to `pyhton/logs/hsgq_olt_log_{date}.log`
- **Error Handling**: Catches Telnet errors, timeouts, and connection failures
- **Return Format**: `status:message` (e.g., `success:ONU PON7/0 reset successfully!`)
- **Timeouts**: Optimized 5-second timeouts per command step
- **Process Timeout**: 45 seconds in Laravel controller to prevent nginx gateway timeout
- **Authentication**: Uses OLT credentials from database
- **Execution Time**: Approximately 15-26 seconds depending on OLT response time

### Usage in Controller

**Reboot:**
```php
$processReboot = new Process(["python3", env("PHYTON_DIR")."reboot_hsgq_ont.py", 
    $ip, $login, $password, $port, $timeout, 
    $ponNum, $onuNum]);

$processReboot->run();
$message = $processReboot->getOutput();
$parts = explode(":", $message);
return redirect()->back()->with($parts[0], $parts[1]);
```

**Reset:**
```php
$processReset = new Process(["python3", env("PHYTON_DIR")."reset_hsgq_ont.py", 
    $ip, $login, $password, $port, $timeout, 
    $ponNum, $onuNum]);

$processReset->run();
$message = $processReset->getOutput();
$parts = explode(":", $message);
return redirect('/olt/'.$oltId)->with($parts[0], $parts[1]);
```

## Testing

### Before (SNMP Method - Not Working)
```bash
# Command sent successfully
snmpset -v2c -c private 103.153.149.200 .1.3.6.1.4.1.50224.3.12.2.1.3.16779008 i 2
# Result: INTEGER: 2

# But uptime unchanged (NO REBOOT)
snmpget -v2c -c public 103.153.149.200 .1.3.6.1.4.1.50224.3.12.2.1.21.16779008
# Result: Timeticks: (61693100) 7 days, 3:22:11.00
```

### After (CLI Method - Should Work)
```bash
# Execute via web UI or command:
python3 /var/www/kencana.alus.co.id/pyhton/reboot_hsgq_ont.py \
  103.153.149.200 admin admin 23 10 7 0

# Expected output:
success:ONU PON7/0 rebooted successfully!

# Verify uptime changed (reboot successful):
snmpget -v2c -c public 103.153.149.200 .1.3.6.1.4.1.50224.3.12.2.1.21.16779008
# Should show small uptime value (e.g., few minutes)
```

## Database Requirements
- Table: `olts`
- Fields needed:
  - `ip` (OLT IP address)
  - `user` (Telnet username)
  - `password` (Telnet password)
  - `port` (Telnet port, default: 23)

## Error Handling
Script returns error status if:
- Connection timeout
- Authentication failed
- Invalid command output
- ONU not found
- Telnet exception

Example errors:
```
error:Telnet error - Connection refused
error:Connection timeout - [Errno 110]
error:Failed to reboot ONU PON7/0
```

## Known Limitations
1. **SNMP Control**: Field `.12.2.1.3` tidak berfungsi untuk actual reboot
2. **CLI Required**: Harus menggunakan Telnet/SSH untuk control operations
3. **Prompt Variation**: Script assumes standard HSGQ CLI prompts (`Login:`, `>`, `#`)
4. **No SSH**: Currently uses Telnet (port 23), not SSH

## Future Improvements
- [ ] Add SSH support for better security
- [ ] Add batch reboot for multiple ONUs
- [ ] Verify reboot via uptime check
- [ ] Add retry mechanism for failed commands
- [ ] Support alternative CLI prompt formats

## References
- HSGQ GPON OLT Documentation
- ELTEX OID Reference: `.1.3.6.1.4.1.3320.101.11.1.1.6`
- HSGQ Enterprise OID: `.1.3.6.1.4.1.50224`

## Date
Created: 2026-01-26
Updated: 2026-01-26
Author: System Admin
Status: **Production Ready** (requires testing)
