import sys
import socket
import time

# ==========================================================
# Usage:
# python3 reboot_cdata_ont.py <ip> <username> <password> <port> <timeout> <pon> <onu>
# Example:
# python3 reboot_cdata_ont.py 172.30.10.13 root media123 1611 10 1 1
#
# CLI sequence on the device (as confirmed by the user against a real CDATA GPON OLT):
#   OLT> enable
#   OLT# config
#   OLT(config)# interface gpon 0/0
#   OLT(config-gpon-0/0)# ont reboot <pon> <onu>
#   OLT(config-gpon-0/0)# exit
#   OLT(config)# save
# "interface gpon 0/0" is a fixed frame/slot context covering all physical GPON ports on
# this chassis (0/0/1..0/0/8) — the actual PON port is selected via the first argument to
# "ont reboot <pon> <onu>", not via the interface command itself.
# ==========================================================

ip       = sys.argv[1]
login    = sys.argv[2]
password = sys.argv[3]
port     = int(sys.argv[4])
timeout  = int(sys.argv[5])
pon_int  = sys.argv[6]
onu_num  = sys.argv[7]

# ---------- Helpers ----------

IAC, DO, WILL, WONT, DONT = 255, 253, 251, 252, 254

def recv_until(s, expect, wait=12):
    """Read from socket until expected bytes found or timeout.

    Also answers Telnet IAC option negotiation (decline every DO/WILL with
    WONT/DONT) — this CDATA firmware withholds the login banner entirely
    until the client acknowledges negotiation, confirmed live: without this,
    the socket just sits on raw 0xFF 0xFD... bytes and the login prompt never
    arrives, which is what caused the earlier "No login prompt from OLT" error.
    """
    if isinstance(expect, str):
        expect = expect.encode()
    buf = b''
    deadline = time.time() + wait
    while time.time() < deadline:
        s.settimeout(2)
        try:
            chunk = s.recv(1024)
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
        if expect.lower() in buf.lower():
            return buf
    return buf

# ---------- Main ----------

def telnet_cdata(host, port, username, pwd, pon, onu):
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(timeout)
        s.connect((host, port))

        # Login prompt text isn't confirmed for this specific unit — accept either
        # "username:" or "login:" (case-insensitive) rather than hardcoding one.
        banner = recv_until(s, 'login:', wait=15)
        if b'login:' not in banner.lower() and b'username:' not in banner.lower() and b'user name:' not in banner.lower():
            print("error:No login prompt from OLT")
            return

        s.sendall((username + '\r\n').encode())
        out = recv_until(s, 'password:', wait=12)
        if b'password:' not in out.lower():
            print("error:No password prompt from OLT")
            return

        s.sendall((pwd + '\r\n').encode())
        out = recv_until(s, '>', wait=12)
        if b'>' not in out:
            lowered_out = out.lower()
            if b'incorrect' in lowered_out or b'login failed' in lowered_out or b'denied' in lowered_out:
                print("error:Login failed - incorrect username or password")
            else:
                print("error:Login failed - no prompt")
            return

        s.sendall(b'enable\r\n')
        out = recv_until(s, '#', wait=10)
        if b'#' not in out:
            print("error:Cannot enter enable mode")
            return

        s.sendall(b'config\r\n')
        out = recv_until(s, '#', wait=10)

        s.sendall(b'interface gpon 0/0\r\n')
        out = recv_until(s, '#', wait=10)

        reboot_cmd = f'ont reboot {pon} {onu}\r\n'.encode()
        s.sendall(reboot_cmd)
        out = recv_until(s, '#', wait=15)
        decoded = out.decode('ascii', errors='ignore')

        cleaned = decoded.replace(f'ont reboot {pon} {onu}', '').strip()
        lowered = decoded.lower()
        if any(w in lowered for w in ('error', 'invalid', 'fail', 'not exist', 'unknown command')):
            print(f"error:Failed to reboot ONU PON{pon}/{onu} - {cleaned[:150]}")
        else:
            print(f"success:ONU PON{pon}/{onu} reboot command sent!")

        s.sendall(b'exit\r\n')
        recv_until(s, '#', wait=8)
        s.sendall(b'save\r\n')
        recv_until(s, '#', wait=10)

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
telnet_cdata(ip, port, login, password, pon_int, onu_num)
