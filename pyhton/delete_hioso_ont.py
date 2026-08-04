import sys
import socket
import time
import datetime
import os
import subprocess
import re

# ==========================================================
# Usage:
# python3 delete_hioso_ont.py <ip> <username> <password> <port> <timeout> <pon> <onu> [community_ro]
# Example:
# python3 delete_hioso_ont.py 172.30.10.29 admin secret 232 10 1 1 public
#
# Delete/unregister variant of reboot_hioso_ont.py — same login/enable/
# configure terminal/interface epon 1/<pon> sequence, only the final command
# differs and the word order is reversed from reboot/factory:
#   EPON(epon_0/<pon>)# delete onu <onu>
# (compare "onu <onu> reboot" / "onu <onu> factory" — this one is
# "delete onu <onu>", action-first, per explicit instruction.)
#
# NOT run against a real device — like factory reset, this is the most
# destructive of the three HIOSO actions (likely unregisters the ONU
# entirely, probably requiring re-provisioning to bring it back, unlike a
# plain reboot) and was intentionally not live-tested. Applied by analogy
# from the confirmed reboot sequence only.
#
# Confirmation logic is DELIBERATELY DIFFERENT from reboot/reset: a reboot
# or factory-reset leaves the ONU's SNMP table row in place (status just
# flips 1->2 while it restarts), but a genuine delete/unregister should
# make that row disappear entirely — oidOnuStatus for this index would
# start returning "No Such Instance" instead of any INTEGER value. So
# success here is inferred from the status OID going from a real value
# (1 or 2) to unreadable (None), polled a few times to rule out a transient
# SNMP hiccup — not from a 1->2 flip. This has NOT been verified live either
# way; treat the first real run as the actual validation and check
# pyhton/logs/hioso_olt_log_*.log (FULL OUTPUT line) for the true CLI
# response.
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

def get_onu_status_raw(host, community, pon, onu):
    """Return raw snmpget stdout/stderr text for oidOnuStatus (board=1), for
    inspecting both a value (INTEGER: n) and absence (No Such Instance)."""
    try:
        oid = f".1.3.6.1.4.1.25355.3.2.6.3.2.1.39.1.{pon}.{onu}"
        result = subprocess.run(
            ["snmpget", "-v2c", "-c", community, "-t", "2", "-r", "1", host, oid],
            capture_output=True,
            text=True,
            timeout=6,
        )
        return (result.stdout or result.stderr or "").strip()
    except Exception:
        return ""

def parse_status_value(raw):
    """Extract INTEGER value from raw snmpget text, or None if not present
    (covers both real SNMP errors and a genuine 'No Such Instance')."""
    m = re.search(r"INTEGER:\s*(\d+)", raw)
    return int(m.group(1)) if m else None

# ---------- Main ----------

def telnet_hioso_delete(host, port, username, pwd, pon, onu, log_path):
    today = datetime.datetime.now().strftime("%Y-%m-%d")
    log_file_path = f"{log_path}/hioso_olt_log_{today}.log"

    with open(log_file_path, 'a') as log_file:
        try:
            log("CONNECTING TO OLT (DELETE) ...", log_file)
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

            before_raw = get_onu_status_raw(host, community_ro, pon, onu)
            before_status = parse_status_value(before_raw)
            log(f"SNMP STATUS BEFORE: {before_status} (raw: {before_raw!r})", log_file)

            delete_cmd = f'delete onu {onu}\r\n'
            log(f"SEND DELETE COMMAND (UNTESTED by analogy with reboot): delete onu {onu}", log_file)
            s.sendall(delete_cmd.encode())

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
            cleaned = decoded.replace(delete_cmd.strip(), '').strip()

            lowered = decoded.lower()
            if (
                "error" in lowered
                or "invalid" in lowered
                or "fail" in lowered
                or "not exist" in lowered
                or "unknown command" in lowered
                or "% " in decoded
            ):
                log("DELETE COMMAND FAILED", log_file)
                print(f"error:Failed to delete ONU PON{pon}/{onu} - {cleaned[:100]}")
            else:
                # Confirm by SNMP row disappearing (see module docstring) rather than
                # a status-value flip. Poll a few times to rule out a transient
                # SNMP hiccup before concluding the row is genuinely gone.
                after_status = before_status
                after_raw = before_raw
                for _ in range(4):
                    time.sleep(3)
                    after_raw = get_onu_status_raw(host, community_ro, pon, onu)
                    after_status = parse_status_value(after_raw)
                    if after_status is None:
                        break
                log(f"SNMP STATUS AFTER: {after_status} (raw: {after_raw!r})", log_file)

                if before_status is not None and after_status is None:
                    log("DELETE LIKELY CONFIRMED - SNMP ROW NO LONGER PRESENT", log_file)
                    print(f"success:ONU PON{pon}/{onu} delete confirmed via SNMP (entry no longer present)")
                else:
                    log("DELETE COMMAND SENT (SNMP row still present or unreadable before/after)", log_file)
                    print(f"warning:ONU PON{pon}/{onu} delete command sent (not confirmed) - {cleaned[:100]}")

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
telnet_hioso_delete(ip, port, login, password, pon_int, onu_num, log_path)
