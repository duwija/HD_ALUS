import sys
import telnetlib
import time
import datetime
import socket
import os

# ==========================================================
# Usage:
# python3 reboot_hsgq_ont.py <ip> <username> <password> <port> <timeout> <pon> <onu>
# Example:
# python3 reboot_hsgq_ont.py 103.153.149.200 root media123 23 20 7 46
# ==========================================================

ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = int(sys.argv[4])
timeout = int(sys.argv[5])
pon_int = sys.argv[6]
onu_num = sys.argv[7]

log_path = os.path.dirname(os.path.abspath(__file__)) + "/logs"
os.makedirs(log_path, exist_ok=True)

# ---------- Helpers ----------

def read_until_prompt(tn, prompt, timeout=5):
    if isinstance(prompt, str):
        prompt = prompt.encode()
    return tn.read_until(prompt, timeout).decode('ascii', errors='ignore')

def log(msg, logfile):
    line = f"{datetime.datetime.now()} {msg}"
    print(line)
    logfile.write(line + "\n")
    logfile.flush()

def send_slow(tn, cmd, delay=0.03):
    """Send command char-by-char to simulate human typing"""
    for c in cmd:
        tn.write(c.encode())
        time.sleep(delay)

# ---------- Main Function ----------

def telnet_hsgq(host, port, username, password, pon, onu, log_path):
    try:
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_file_path = f"{log_path}/hsgq_olt_log_{today}.log"

        with open(log_file_path, 'a') as log_file:

            log("CONNECTING TO OLT ...", log_file)
            tn = telnetlib.Telnet(host, port, timeout=timeout)

            # ---- LOGIN ----
            read_until_prompt(tn, "Login:", timeout=5)
            send_slow(tn, username + "\n")

            read_until_prompt(tn, "Password:", timeout=5)
            send_slow(tn, password + "\n")

            read_until_prompt(tn, ">", timeout=5)
            log("LOGIN SUCCESS", log_file)

            # ---- ENABLE ----
            send_slow(tn, "enable\n")
            read_until_prompt(tn, "#", timeout=5)
            log("ENTER ENABLE MODE", log_file)

            # ---- CONFIG ----
            send_slow(tn, "configure\n")
            read_until_prompt(tn, "#", timeout=5)
            log("ENTER CONFIG MODE", log_file)

            # ---- SEND REBOOT COMMAND (NOT in interface mode) ----
            # HSGQ reboot syntax: ont reset {pon} {onu} (from config mode, NOT interface mode)
            log(f"SEND REBOOT COMMAND: ont reset {pon} {onu}", log_file)
            send_slow(tn, f"ont reset {pon} {onu}\n")
            
            # Wait and capture all output including async messages
            log("WAITING FOR RESPONSE AND ASYNC MESSAGES...", log_file)
            time.sleep(8)  # Wait longer for async message
            
            # Read all output
            full_output = tn.read_very_eager().decode('ascii', errors='ignore')
            log("FULL OUTPUT:", log_file)
            for l in full_output.splitlines():
                if l.strip():
                    log("  " + l, log_file)

            # Check for error in response
            if "error" in full_output.lower() or "invalid" in full_output.lower() or "fail" in full_output.lower() or "not exist" in full_output.lower():
                log("REBOOT COMMAND FAILED", log_file)
                print(f"error:Failed to reboot ONU PON{pon}/{onu}")
            elif "manual reboot" in full_output.lower() or "link down" in full_output.lower():
                log("REBOOT CONFIRMED - ONU LINK DOWN", log_file)
                print(f"success:ONU PON{pon}/{onu} rebooted successfully!")
            else:
                log("REBOOT COMMAND SENT (no async confirmation received)", log_file)
                print(f"warning:ONU PON{pon}/{onu} reboot command sent but no confirmation received")
            
            # ---- EXIT CONFIG MODE ----
            send_slow(tn, "exit\n")
            time.sleep(1)
            send_slow(tn, "exit\n")
            tn.close()
            log("SESSION CLOSED", log_file)

    except socket.timeout:
        print("error:Connection timeout")
    except ConnectionRefusedError:
        print("error:Telnet refused by host")
    except EOFError:
        print("error:Connection closed by OLT")
    except Exception as e:
        print(f"error:Unexpected - {e}")

# ---------- RUN ----------
telnet_hsgq(ip, port, login, password, pon_int, onu_num, log_path)
