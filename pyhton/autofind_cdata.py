import sys
import socket
import time
import re
import json

# ==========================================================
# Usage:
# python3 autofind_cdata.py <ip> <username> <password> <port> <timeout>
#
# Lists unregistered (auto-found) ONTs on a CDATA GPON OLT (FD16xx) via the CLI, because
# the SNMP autofind table (34592 ...18.5) returns "No Such Object" on this firmware.
# Read-only: only "show ont autofind all" is sent. Output block format (confirmed live on
# OLT CDATA PESAGI 01, FD1608Y-B1):
#   Frame/Slot          : 0/0
#   Port                : 7
#   SN                  : ZTEGCFEAF9B1 (ZTEG-CFEAF9B1)
#   ...
#   Equipment ID        : F609V9.0
#   Last autofind time  : Sat Oct 31 22:25:47 2026
#
# Prints JSON: {"ok": true, "onus": [{"port","sn","equipment","time"}]} or {"ok": false, "error": "..."}
# ==========================================================

ip       = sys.argv[1]
login    = sys.argv[2]
password = sys.argv[3]
port     = int(sys.argv[4])
timeout  = int(sys.argv[5])

IAC, DO, WILL, WONT, DONT = 255, 253, 251, 252, 254

def recv_until(s, expect, wait=12):
    """Read until the buffer tail contains one of `expect`; answers telnet negotiation and pages through --More--."""
    expects = [e.encode().lower() for e in (expect if isinstance(expect, (list, tuple)) else [expect])]
    buf = b''
    deadline = time.time() + wait
    while time.time() < deadline:
        s.settimeout(2)
        try:
            chunk = s.recv(4096)
        except socket.timeout:
            continue
        if not chunk:
            break
        i = 0
        while i < len(chunk):
            b = chunk[i]
            if b == IAC and i + 2 < len(chunk):
                cmd, opt = chunk[i + 1], chunk[i + 2]
                if cmd == DO:
                    s.sendall(bytes([IAC, WONT, opt]))
                elif cmd == WILL:
                    s.sendall(bytes([IAC, DONT, opt]))
                i += 3
            else:
                buf += bytes([b])
                i += 1
        if b'--More--' in buf[-200:]:
            buf = buf.replace(b'--More--', b'')
            s.sendall(b' ')
            deadline = time.time() + wait
            continue
        tail = buf.rstrip()[-40:].lower()
        if any(e in tail for e in expects):
            return buf
    return buf

def main():
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.settimeout(timeout)
    s.connect((ip, port))

    banner = recv_until(s, ['login:', 'username:', 'user name:'], wait=15).lower()
    if b'login:' not in banner and b'username:' not in banner and b'user name:' not in banner:
        return {"ok": False, "error": "No login prompt from OLT"}
    s.sendall((login + '\r\n').encode())
    if b'password:' not in recv_until(s, 'password:', wait=12).lower():
        return {"ok": False, "error": "No password prompt from OLT"}
    s.sendall((password + '\r\n').encode())
    if b'>' not in recv_until(s, '>', wait=12):
        return {"ok": False, "error": "Login failed - incorrect username or password"}

    s.sendall(b'enable\r\n')
    recv_until(s, '#', wait=10)
    s.sendall(b'show ont autofind all\r\n')
    text = recv_until(s, '#', wait=30).decode('ascii', errors='ignore').replace('\x08', '')
    s.sendall(b'exit\r\nexit\r\n')
    time.sleep(0.3)
    s.close()

    onus = []
    for block in re.split(r'-{20,}', text):
        port_m = re.search(r'^\s*Port\s*:\s*(\d+)', block, re.M)
        sn_m = re.search(r'^\s*SN\s*:\s*([A-Za-z0-9]{12})', block, re.M)
        if not (port_m and sn_m):
            continue
        eq_m = re.search(r'^\s*Equipment ID\s*:\s*(\S+)', block, re.M)
        tm_m = re.search(r'^\s*Last autofind time\s*:\s*(.+?)\s*$', block, re.M)
        onus.append({
            "port": int(port_m.group(1)),
            "sn": sn_m.group(1).upper(),
            "equipment": eq_m.group(1) if eq_m else '',
            "time": tm_m.group(1) if tm_m else '',
        })
    return {"ok": True, "onus": onus}

try:
    result = main()
except ConnectionRefusedError:
    result = {"ok": False, "error": "Telnet connection refused"}
except socket.timeout:
    result = {"ok": False, "error": "Connection timeout"}
except Exception as e:
    result = {"ok": False, "error": f"Unexpected - {e}"}

print(json.dumps(result))
