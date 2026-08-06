import sys
import socket
import time
import datetime
import os

# ==========================================================
# Usage:
# python3 delete_cdata_epon_ont.py <ip> <username> <password> <port> <timeout> <card> <pon> <onu>
# Example:
# python3 delete_cdata_epon_ont.py 103.156.75.196 root public 1612 10 1 1 1
#
# CLI sequence on the device (as confirmed by the user against a real CDATA EPON
# OLT, model FD1304E):
#   User name:root
#   Password: *****
#   OLT> enable
#   OLT# config
#   OLT(config)# interface epon 0/<card>
#   OLT(config-epon-0/<card>)# ont delete <pon> <onu>
#   OLT(config-epon-0/<card>)# exit
#   OLT(config)# save
# Unlike CDATA GPON (config/cdata_gpon_17409_oid.php's fixed "interface gpon 0/0"
# covering all ports on one flat context), CDATA EPON selects a specific PON CARD
# via the interface command itself ("epon 0/<card>", card matches the SNMP tree's
# card component — see decode_cdata_epon17409_index() in config/zteoid.php); <pon>
# in "ont delete <pon> <onu>" is then the port NUMBER WITHIN that card (1-4 on
# units seen so far), and <onu> the ONU id — same two-argument shape as GPON's
# "ont delete <pon> <onu>", just scoped to the already-selected card.
# See reboot_cdata_ont.py (CDATA GPON) for the shared connection/prompt-handling
# notes — identical Telnet IAC negotiation and login-prompt handling confirmed
# live on this EPON unit too ("User name:" prompt, not "login:").
# ==========================================================

ip       = sys.argv[1]
login    = sys.argv[2]
password = sys.argv[3]
port     = int(sys.argv[4])
timeout  = int(sys.argv[5])
card_int = sys.argv[6]
pon_int  = sys.argv[7]
onu_num  = sys.argv[8]

log_path = os.path.dirname(os.path.abspath(__file__)) + "/logs"
os.makedirs(log_path, exist_ok=True)

# ---------- Helpers ----------

def log(msg, logfile):
    line = f"{datetime.datetime.now()} {msg}"
    logfile.write(line + "\n")
    logfile.flush()

IAC, DO, WILL, WONT, DONT = 255, 253, 251, 252, 254

def recv_until(s, expect, wait=12):
    """Read from socket until expected bytes found or timeout.

    Also answers Telnet IAC option negotiation (decline every DO/WILL with
    WONT/DONT) — this CDATA firmware withholds the login banner entirely
    until the client acknowledges negotiation, confirmed live on both the
    GPON and EPON units in this fleet.
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

def telnet_cdata_epon_delete(host, port, username, pwd, card, pon, onu, log_path):
    today = datetime.datetime.now().strftime("%Y-%m-%d")
    log_file_path = f"{log_path}/cdata_epon_olt_log_{today}.log"

    with open(log_file_path, 'a') as log_file:
        try:
            log("CONNECTING TO OLT ...", log_file)
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.settimeout(timeout)
            s.connect((host, port))

            # Confirmed live: this EPON unit prompts "User name:", not "login:".
            banner = recv_until(s, 'name:', wait=15)
            if b'login:' not in banner.lower() and b'username:' not in banner.lower() and b'user name:' not in banner.lower() and b'name:' not in banner.lower():
                log(f"ERROR: No login prompt. Got: {repr(banner[-80:])}", log_file)
                print("error:No login prompt from OLT")
                return
            log("GOT LOGIN PROMPT", log_file)

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
                lowered_out = out.lower()
                if b'incorrect' in lowered_out or b'login failed' in lowered_out or b'denied' in lowered_out:
                    log(f"ERROR: Login rejected. Got: {repr(out[-80:])}", log_file)
                    print("error:Login failed - incorrect username or password")
                else:
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

            s.sendall(b'config\r\n')
            out = recv_until(s, '#', wait=10)
            log("ENTER CONFIG MODE", log_file)

            interface_cmd = f'interface epon 0/{card}\r\n'.encode()
            s.sendall(interface_cmd)
            out = recv_until(s, '#', wait=10)
            log(f"ENTER INTERFACE EPON 0/{card}", log_file)

            delete_cmd = f'ont delete {pon} {onu}\r\n'.encode()
            log(f"SEND DELETE COMMAND: ont delete {pon} {onu}", log_file)
            s.sendall(delete_cmd)
            out = recv_until(s, '#', wait=15)
            decoded = out.decode('ascii', errors='ignore')
            log(f"DELETE COMMAND OUTPUT: {repr(decoded)}", log_file)

            cleaned = decoded.replace(f'ont delete {pon} {onu}', '').strip()
            lowered = decoded.lower()
            if any(w in lowered for w in ('error', 'invalid', 'fail', 'not exist', 'unknown command')):
                log("DELETE COMMAND FAILED", log_file)
                print(f"error:Failed to delete ONU EPON 0/{card}/{pon}/{onu} - {cleaned[:150]}")
            else:
                log("DELETE COMMAND SENT", log_file)
                print(f"success:ONU EPON 0/{card}/{pon}/{onu} delete command sent!")

            s.sendall(b'exit\r\n')
            recv_until(s, '#', wait=8)
            s.sendall(b'save\r\n')
            recv_until(s, '#', wait=10)
            log("CONFIG SAVED", log_file)

            s.sendall(b'exit\r\n')
            time.sleep(0.5)
            s.close()
            log("SESSION CLOSED", log_file)

        except ConnectionRefusedError:
            print("error:Telnet connection refused")
        except socket.timeout:
            print("error:Connection timeout")
        except Exception as e:
            print(f"error:Unexpected - {e}")

# ---------- RUN ----------
telnet_cdata_epon_delete(ip, port, login, password, card_int, pon_int, onu_num, log_path)
