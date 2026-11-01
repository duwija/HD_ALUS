import sys
import socket
import time
import re
import datetime

# ==========================================================
# Usage:
# python3 addont_cdata.py <ip> <username> <password> <port> <timeout> <pon> <onu> <sn> \
#                         <description> <vlan> <pppoe_user> <pppoe_pass> [log_file]
# Example:
# python3 addont_cdata.py 172.30.10.13 root secret 232 10 2 45 ZTEGCFEB4AEE \
#         TegalLinggahPondok_IWayanAdiDarmita 115 TGLP_IWayanAdiDarmita TGLP_IWayanAdiDarmita
#
# Register + provision a new ONT on a CDATA GPON OLT (FD16xx). The command set below is
# copied from a real running-config of a working ONT supplied by the user (OLT 12,
# "OLT CDATA PESAGI 02", ONT 2/45) — only pon/onu/sn/description/vlan/pppoe are variable.
# Login / enable / config / "interface gpon 0/0" sequence is the same as
# reboot_cdata_ont.py (confirmed against the same hardware).
#
# Prints exactly one final line: "success:<msg>" or "error:<msg>" (OltController splits on ':').
# Full CLI transcript goes to [log_file] when given.
# ==========================================================

ip       = sys.argv[1]
login    = sys.argv[2]
password = sys.argv[3]
port     = int(sys.argv[4])
timeout  = int(sys.argv[5])
pon      = sys.argv[6]
onu      = sys.argv[7]
sn       = sys.argv[8]
desc     = sys.argv[9]
vlan     = sys.argv[10]
ppp_user = sys.argv[11]
ppp_pass = sys.argv[12]
log_file = sys.argv[13] if len(sys.argv) > 13 else None

IAC, DO, WILL, WONT, DONT = 255, 253, 251, 252, 254

transcript = []

def log(text):
    transcript.append(text)

def flush_log():
    if not log_file:
        return
    try:
        with open(log_file, 'a') as f:
            f.write(f"===== {datetime.datetime.now():%Y-%m-%d %H:%M:%S} {ip} PON{pon}/{onu} SN {sn} =====\n")
            f.write('\n'.join(transcript) + '\n')
    except Exception:
        pass

def recv_until(s, expect, wait=12):
    """Read until expected bytes found or timeout; declines telnet option negotiation
    (this CDATA firmware withholds the login banner until IAC is answered)."""
    if isinstance(expect, str):
        expect = expect.encode()
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
        if expect.lower() in buf.lower():
            return buf
    return buf

# "fail" alone would also match summary lines like "failed: 0", so exclude a zero count.
ERROR_RE = re.compile(r'error|invalid|unknown|not exist|already|incomplete|unrecognized|fail(?!\w*\s*[:=]\s*0\b)', re.I)

def run(s, cmd, wait=15):
    s.sendall((cmd + '\r\n').encode())
    out = recv_until(s, '#', wait=wait)
    text = out.decode('ascii', errors='ignore')
    # Answer any confirmation prompt with yes.
    if re.search(r'[\(\[]y/n[\)\]]', text, re.I):
        s.sendall(b'y\r\n')
        more = recv_until(s, '#', wait=wait)
        text += more.decode('ascii', errors='ignore')
    log(f"> {cmd}\n{text}")
    body = text.replace(cmd, '', 1)
    # Drop the trailing CLI prompt line, flatten the rest for a one-line flash message.
    lines = [l.strip() for l in body.splitlines() if l.strip() and not l.strip().endswith('#')]
    body = ' '.join(lines)
    return (ERROR_RE.search(body) is None), body

def main():
    commands = [
        f'ont add {pon} {onu} sn-auth "{sn}"',
        f'ont description {pon} {onu} {desc}',
        f'ont ont-port {pon} {onu} eth adaptive pots adaptive catv adaptive iphost adaptive wifi adaptive',
        f'ont mac-learning {pon} {onu} enable',
        f'ont mac-aging {pon} {onu} 300',
        f'ont veip {pon} {onu} trunk',
        f'ont loopdetect {pon} {onu} disable detect-frequency 8 resume-interval 300 auto-shutdown enable',
        f'ont native-vlan {pon} {onu} concern',
        f'ont multicast mode {pon} {onu} unconcern',
        f'ont ipconfig {pon} {onu} ip-index 0 pppoe username {ppp_user} password {ppp_pass} vlan {vlan} priority 0',
        f'ont tcont {pon} {onu} 0 dba-profile-id 0',
        f'ont tcont {pon} {onu} 1 dba-profile-id 1',
        f'ont mapping-mode {pon} {onu} vlan',
        f'ont gemport {pon} {onu} 1 tcont 1 encrypt disable priority-queue-upstream 1 priority-queue-downstream 1',
        f'ont gemport mapping {pon} {onu} 1 1 vlan {vlan}',
        f'ont gemport {pon} {onu} 1 tag-action vlan-transparent',
        f'ont fec {pon} {onu} disable',
        f'ont omcc encrypt {pon} {onu} disable',
        f'ont qos-mode {pon} {onu} priority-queue',
        f'ont port attribute {pon} {onu} catv 1 unconcern',
        f'ont security-mgmt {pon} {onu} 1 state enable mode forward protocol web ingress-type wan',
    ]

    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.settimeout(timeout)
    s.connect((ip, port))

    banner = recv_until(s, 'login:', wait=15)
    low = banner.lower()
    if b'login:' not in low and b'username:' not in low and b'user name:' not in low:
        return "error:No login prompt from OLT"

    s.sendall((login + '\r\n').encode())
    out = recv_until(s, 'password:', wait=12)
    if b'password:' not in out.lower():
        return "error:No password prompt from OLT"

    s.sendall((password + '\r\n').encode())
    out = recv_until(s, '>', wait=12)
    if b'>' not in out:
        return "error:Login failed - incorrect username or password"

    s.sendall(b'enable\r\n')
    if b'#' not in recv_until(s, '#', wait=10):
        return "error:Cannot enter enable mode"

    run(s, 'config', wait=10)
    run(s, 'interface gpon 0/0', wait=10)

    result = None
    for i, cmd in enumerate(commands):
        ok, body = run(s, cmd)
        if not ok:
            if i == 0:
                result = f"error:Register ONT PON{pon}/{onu} SN {sn} gagal - {body[:150]}"
            else:
                result = (f"error:ONT PON{pon}/{onu} sudah ditambahkan tapi config gagal di perintah "
                          f"'{cmd}' - {body[:150]}. Cek/hapus ONT ini di OLT.")
            break

    run(s, 'exit', wait=8)
    if result is None:
        run(s, 'save', wait=30)
        result = f"success:ONT PON{pon}/{onu} SN {sn} berhasil diregister (VLAN {vlan}, PPPoE {ppp_user})"

    s.sendall(b'exit\r\n')
    time.sleep(0.5)
    s.close()
    return result

try:
    final = main()
except ConnectionRefusedError:
    final = "error:Telnet connection refused"
except socket.timeout:
    final = "error:Connection timeout"
except Exception as e:
    final = f"error:Unexpected - {e}"

log(final)
flush_log()
print(final)
