import sys
import socket
import time
import datetime
import os

# ==========================================================
# Usage:
# python3 reset_cdata_ont.py <ip> <username> <password> <port> <timeout> <pon> <onu>
# Factory-resets (restore-factory) a CDATA GPON ONU via Telnet CLI:
#   OLT> enable
#   OLT# config
#   OLT(config)# interface gpon 0/0
#   OLT(config-gpon-0/0)# ont restore-factory <pon> <onu>
#   OLT(config-gpon-0/0)# exit
#   OLT(config)# save
# See reboot_cdata_ont.py for the shared connection/prompt-handling notes.
# ==========================================================

ip       = sys.argv[1]
login    = sys.argv[2]
password = sys.argv[3]
port     = int(sys.argv[4])
timeout  = int(sys.argv[5])
pon_int  = sys.argv[6]
onu_num  = sys.argv[7]

log_path = os.path.dirname(os.path.abspath(__file__)) + "/logs"
os.makedirs(log_path, exist_ok=True)

def log(msg, logfile):
    line = f"{datetime.datetime.now()} {msg}"
    logfile.write(line + "\n")
    logfile.flush()

IAC, DO, WILL, WONT, DONT = 255, 253, 251, 252, 254

def recv_until(s, expect, wait=12):
    """Read until expected bytes found, answering Telnet IAC negotiation along the
    way (decline every DO/WILL) — this device withholds its login banner until the
    client acknowledges negotiation, confirmed live against the real OLT."""
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

def telnet_cdata_reset(host, port, username, pwd, pon, onu, log_path):
    today = datetime.datetime.now().strftime("%Y-%m-%d")
    log_file_path = f"{log_path}/cdata_olt_log_{today}.log"

    with open(log_file_path, 'a') as log_file:
        try:
            log("CONNECTING TO OLT ...", log_file)
            s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            s.settimeout(timeout)
            s.connect((host, port))

            banner = recv_until(s, 'login:', wait=15)
            if b'login:' not in banner.lower() and b'username:' not in banner.lower() and b'user name:' not in banner.lower():
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

            s.sendall(b'interface gpon 0/0\r\n')
            out = recv_until(s, '#', wait=10)
            log("ENTER INTERFACE GPON 0/0", log_file)

            cmd = f'ont restore-factory {pon} {onu}\r\n'.encode()
            log(f"SEND RESTORE-FACTORY COMMAND: ont restore-factory {pon} {onu}", log_file)
            s.sendall(cmd)
            out = recv_until(s, '#', wait=15)
            decoded = out.decode('ascii', errors='ignore')
            log(f"RESTORE-FACTORY OUTPUT: {repr(decoded)}", log_file)

            cleaned = decoded.replace(f'ont restore-factory {pon} {onu}', '').strip()
            lowered = decoded.lower()
            if any(w in lowered for w in ('error', 'invalid', 'fail', 'not exist', 'unknown command')):
                log("RESTORE-FACTORY COMMAND FAILED", log_file)
                print(f"error:Failed to factory reset ONU PON{pon}/{onu} - {cleaned[:150]}")
            else:
                log("RESTORE-FACTORY COMMAND SENT", log_file)
                print(f"success:ONU PON{pon}/{onu} factory reset command sent!")

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

telnet_cdata_reset(ip, port, login, password, pon_int, onu_num, log_path)
