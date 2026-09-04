import sys
import socket
import time
import subprocess
import re

# ==========================================================
# Usage:
# python3 reboot_hsgq_ont.py <ip> <username> <password> <port> <timeout> <pon> <onu>
# Example:
# python3 reboot_hsgq_ont.py 172.30.10.5 root media123 23 10 2 1
# ==========================================================

ip       = sys.argv[1]
login    = sys.argv[2]
password = sys.argv[3]
port     = int(sys.argv[4])
timeout  = int(sys.argv[5])
pon_int  = sys.argv[6]
onu_num  = sys.argv[7]

# ---------- Helpers ----------

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

def encode_hsgq_index(pon, onu):
    return 0x01000000 + (int(pon) << 8) + int(onu)

def get_onu_uptime_ticks(host, community, pon, onu):
    """Return ONU uptime timeticks (integer) via SNMP, or None if unavailable."""
    try:
        idx = encode_hsgq_index(pon, onu)
        oid = f".1.3.6.1.4.1.50224.3.12.2.1.21.{idx}"
        result = subprocess.run(
            ["snmpget", "-v2c", "-c", community, "-t", "2", "-r", "1", host, oid],
            capture_output=True,
            text=True,
            timeout=5,
        )
        output = (result.stdout or result.stderr or "").strip()
        match = re.search(r"\((\d+)\)", output)
        if not match:
            return None
        return int(match.group(1))
    except Exception:
        return None

# ---------- Main ----------

def telnet_hsgq(host, port, username, pwd, pon, onu):
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.connect((host, port))

        # Wait for username prompt (OLT sends IAC negotiation + banner first)
        banner = recv_until(s, 'username:', wait=15)
        if b'username:' not in banner.lower():
            print("error:No username prompt from OLT")
            return

        # Send login
        s.sendall((username + '\r\n').encode())
        out = recv_until(s, 'password:', wait=12)
        if b'password:' not in out.lower():
            print("error:No password prompt from OLT")
            return

        s.sendall((pwd + '\r\n').encode())
        out = recv_until(s, '>', wait=12)
        if b'>' not in out:
            print("error:Login failed - no prompt")
            return

        # Enable mode
        s.sendall(b'enable\r\n')
        out = recv_until(s, '#', wait=10)
        if b'#' not in out:
            print("error:Cannot enter enable mode")
            return

        # Config mode
        s.sendall(b'config\r\n')
        out = recv_until(s, '#', wait=10)

        # Capture uptime before command for reboot verification fallback.
        before_ticks = get_onu_uptime_ticks(host, "public_ro", pon, onu)

        # HSGQ firmware on this OLT accepts ONT reset command in config mode.
        # Use TAB between tokens so CLI parses token boundaries reliably.
        reset_cmd = f'ont\treset\t{pon}\t{onu}\r\n'.encode()
        s.sendall(reset_cmd)

        # Wait and capture all OLT response
        time.sleep(10)
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

        # Fallback verification: if no explicit CLI confirmation, check SNMP uptime reset.
        after_ticks = get_onu_uptime_ticks(host, "public_ro", pon, onu)

        # Remove echo of our command from output
        cleaned = decoded.replace(f'ont reset {pon} {onu}', '').replace(f'ont reset {pon}{onu}', '').strip()

        lowered = decoded.lower()
        if (
            "error" in lowered
            or "invalid" in lowered
            or "fail" in lowered
            or "not exist" in lowered
            or "unknown command" in lowered
        ):
            print(f"error:Failed to reboot ONU PON{pon}/{onu} - {cleaned[:100]}")
        elif "reset ont fail" in lowered:
            print(f"error:Reboot rejected by OLT for ONU PON{pon}/{onu} - {cleaned[:100]}")
        elif "link down" in lowered or "offline" in lowered:
            print(f"success:ONU PON{pon}/{onu} rebooted successfully!")
        elif before_ticks is not None and after_ticks is not None and after_ticks < before_ticks:
            print(f"success:ONU PON{pon}/{onu} rebooted successfully! (uptime reset)")
        else:
            print(f"warning:ONU PON{pon}/{onu} reboot command sent (not confirmed)")

        s.sendall(b'exit\r\n')
        time.sleep(0.5)
        s.sendall(b'exit\r\n')
        time.sleep(0.5)
        s.close()

    except ConnectionRefusedError:
        print("error:Telnet connection refused")
    except socket.timeout:
        print("error:Connection timeout")
    except Exception as e:
        print(f"error:Unexpected - {e}")

# ---------- RUN ----------
telnet_hsgq(ip, port, login, password, pon_int, onu_num)
