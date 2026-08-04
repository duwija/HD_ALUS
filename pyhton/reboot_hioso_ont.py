import sys
import socket
import time
import datetime
import os
import subprocess
import re

# ==========================================================
# Usage:
# python3 reboot_hioso_ont.py <ip> <username> <password> <port> <timeout> <pon> <onu> [community_ro]
# Example:
# python3 reboot_hioso_ont.py 172.30.10.29 admin secret 232 10 1 1 public
#
# CLI sequence confirmed live (2026-08-03) against a real HIOSO Epon 7304
# unit (OLT id=10 "OLT HIOSO BLAHBATUH", cnetgoup tenant, telnet port 232):
#   Username: admin
#   Password: ****
#   EPON> enable
#   EPON# configure terminal
#   EPON(config)# interface epon 1/<pon>
#   EPON(epon_0/<pon>)# onu <onu> reboot
# Board is hardcoded to 1 in "interface epon 1/<pon>" — matches the board=1
# assumption already used throughout config/hioso_oid.php's SNMP index.
#
# The CLI itself prints NO confirmation text after "onu <onu> reboot" —
# confirmed live: the session just silently returns to the same prompt
# (e.g. "EPON(epon_0/1)#"), nothing more. So unlike reboot_hsgq_ont.py (which
# parses "link down"/"offline" strings from the CLI), success here is
# confirmed via SNMP: read oidOnuStatus (.25355.3.2.6.3.2.1.39) for this
# ONU right after sending the command — a flip from 1 (online) to 2
# (offline) means the reboot genuinely happened. This exact check was
# manually verified live on 2026-08-03 (ONU PON1/1: status 1->2, RX power
# "-22.15"->"na" within ~15s of sending the command). There's no working
# per-ONU uptime OID to fall back on for already-offline ONUs (checked
# live, see config/hioso_oid.php's doc header) — if the ONU was already
# offline before the command, this script can't confirm anything beyond
# "command sent, no CLI error", since there's no online->offline edge to see.
# ==========================================================

ip          = sys.argv[1]
login       = sys.argv[2]
password    = sys.argv[3]
port        = int(sys.argv[4])
timeout     = int(sys.argv[5])
pon_int     = sys.argv[6]
onu_num     = sys.argv[7]
community_ro = sys.argv[8] if len(sys.argv) > 8 and sys.argv[8] else 'public'

log_path = os.path.dirname(os.path.abspath(__file__)) + "/logs"
os.makedirs(log_path, exist_ok=True)

# ---------- Helpers ----------

def log(msg, logfile):
    line = f"{datetime.datetime.now()} {msg}"
    logfile.write(line + "\n")
    logfile.flush()

def recv_until(s, expect, wait=12):
    """Read from socket until expected bytes found or timeout."""
    if isinstance(expect, str):
        expect = expect.encode()
    buf = b''
    deadline = time.time() + wait
    while time.time() < deadline:
        s.settimeout(2)
        try:
            chunk = s.recv(1024)
            if not chunk:
                break
            buf += chunk
            if expect.lower() in buf.lower():
                return buf
        except socket.timeout:
            pass
    return buf

def get_onu_status(host, community, pon, onu):
    """Return oidOnuStatus (1=online, 2=offline) via SNMP for board=1, or None on failure."""
    try:
        oid = f".1.3.6.1.4.1.25355.3.2.6.3.2.1.39.1.{pon}.{onu}"
        result = subprocess.run(
            ["snmpget", "-v2c", "-c", community, "-t", "2", "-r", "1", host, oid],
            capture_output=True,
            text=True,
            timeout=6,
        )
        output = (result.stdout or result.stderr or "").strip()
        m = re.search(r"INTEGER:\s*(\d+)", output)
        if not m:
            return None
        return int(m.group(1))
    except Exception:
        return None

# ---------- Main ----------

def telnet_hioso(host, port, username, pwd, pon, onu, log_path):
    today = datetime.datetime.now().strftime("%Y-%m-%d")
    log_file_path = f"{log_path}/hioso_olt_log_{today}.log"

    with open(log_file_path, 'a') as log_file:
        try:
            log("CONNECTING TO OLT ...", log_file)
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.settimeout(timeout)
            s.connect((host, port))

            banner = recv_until(s, 'username:', wait=15)
            if b'username:' not in banner.lower():
                log(f"ERROR: No username prompt. Got: {repr(banner[-80:])}", log_file)
                print("error:No username prompt from OLT")
                return
            log("GOT USERNAME PROMPT", log_file)

            s.sendall((username + '\r\n').encode())
            out = recv_until(s, 'password:', wait=12)
            if b'password:' not in out.lower():
                log(f"ERROR: No password prompt. Got: {repr(out[-80:])}", log_file)
                print("error:No password prompt from OLT")
                return
            log("GOT PASSWORD PROMPT", log_file)

            s.sendall((pwd + '\r\n').encode())
            out = recv_until(s, '>', wait=12)
            if b'>' not in out:
                log(f"ERROR: No > prompt after login. Got: {repr(out[-80:])}", log_file)
                print("error:Login failed - no prompt")
                return
            log("LOGIN SUCCESS", log_file)

            s.sendall(b'enable\r\n')
            out = recv_until(s, '#', wait=10)
            if b'#' not in out:
                log(f"ERROR: No # prompt after enable. Got: {repr(out[-80:])}", log_file)
                print("error:Cannot enter enable mode")
                return
            log("ENTER ENABLE MODE", log_file)

            s.sendall(b'configure terminal\r\n')
            out = recv_until(s, '#', wait=10)
            if b'#' not in out:
                log(f"ERROR: No # prompt after configure terminal. Got: {repr(out[-80:])}", log_file)
                print("error:Cannot enter config mode")
                return
            log("ENTER CONFIG MODE", log_file)

            iface_cmd = f'interface epon 1/{pon}\r\n'
            s.sendall(iface_cmd.encode())
            out = recv_until(s, '#', wait=10)
            if b'#' not in out:
                log(f"ERROR: No # prompt after '{iface_cmd.strip()}'. Got: {repr(out[-80:])}", log_file)
                print(f"error:Cannot select interface epon 1/{pon}")
                return
            log(f"ENTERED INTERFACE MODE: {iface_cmd.strip()}", log_file)

            before_status = get_onu_status(host, community_ro, pon, onu)
            log(f"SNMP STATUS BEFORE: {before_status}", log_file)

            reboot_cmd = f'onu {onu} reboot\r\n'
            log(f"SEND REBOOT COMMAND: onu {onu} reboot", log_file)
            s.sendall(reboot_cmd.encode())

            log("WAITING FOR RESPONSE (8s)...", log_file)
            time.sleep(8)
            s.settimeout(2)
            full_output = b''
            try:
                while True:
                    chunk = s.recv(1024)
                    if not chunk:
                        break
                    full_output += chunk
            except socket.timeout:
                pass

            decoded = full_output.decode('ascii', errors='ignore')
            log(f"FULL OUTPUT: {repr(decoded)}", log_file)
            cleaned = decoded.replace(reboot_cmd.strip(), '').strip()

            lowered = decoded.lower()
            if (
                "error" in lowered
                or "invalid" in lowered
                or "fail" in lowered
                or "not exist" in lowered
                or "unknown command" in lowered
                or "% " in decoded
            ):
                log("REBOOT COMMAND FAILED", log_file)
                print(f"error:Failed to reboot ONU PON{pon}/{onu} - {cleaned[:100]}")
            else:
                # CLI prints no confirmation text (verified live) — confirm via SNMP
                # status flip instead. Poll briefly since the link-down can lag a
                # few seconds behind the CLI prompt returning.
                after_status = before_status
                for _ in range(4):
                    time.sleep(3)
                    after_status = get_onu_status(host, community_ro, pon, onu)
                    if after_status == 2:
                        break
                log(f"SNMP STATUS AFTER: {after_status}", log_file)

                if before_status == 1 and after_status == 2:
                    log("REBOOT CONFIRMED - SNMP STATUS FLIPPED ONLINE->OFFLINE", log_file)
                    print(f"success:ONU PON{pon}/{onu} rebooted successfully! (status confirmed via SNMP)")
                else:
                    # Either the ONU was already offline before the command (no
                    # edge to observe) or the flip hasn't happened yet — don't
                    # claim success we haven't actually verified.
                    log("REBOOT COMMAND SENT (SNMP status did not confirm)", log_file)
                    print(f"warning:ONU PON{pon}/{onu} reboot command sent (not confirmed) - {cleaned[:100]}")

            s.sendall(b'exit\r\n')
            time.sleep(0.3)
            s.sendall(b'exit\r\n')
            time.sleep(0.3)
            s.sendall(b'exit\r\n')
            time.sleep(0.3)
            s.close()
            log("SESSION CLOSED", log_file)

        except ConnectionRefusedError:
            print("error:Telnet connection refused")
        except socket.timeout:
            print("error:Connection timeout")
        except Exception as e:
            print(f"error:Unexpected - {e}")

# ---------- RUN ----------
telnet_hioso(ip, port, login, password, pon_int, onu_num, log_path)
