#!/usr/bin/env python3
import sys
import telnetlib
import time
import datetime
import socket
import os

# ==========================================================
# Usage:
# python3 reboot_zte_c600_ont.py <ip> <username> <password> <port> <timeout> <shelf> <slot> <port> <onu>
# Example:
# python3 reboot_zte_c600_ont.py 103.156.74.17 zte zte 23 20 1 1 1 1
# ==========================================================

ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = int(sys.argv[4])
timeout = int(sys.argv[5])
shelf = sys.argv[6]      # Shelf/Rack (biasanya 1)
slot = sys.argv[7]       # Slot/Card
pon_port = sys.argv[8]   # PON Port
onu_id = sys.argv[9]     # ONU ID

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

# ---------- Main Function ----------

def telnet_zte_c600(host, port, username, password, shelf, slot, pon_port, onu_id, log_path):
    try:
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_file_path = f"{log_path}/zte_c600_olt_log_{today}.log"

        with open(log_file_path, 'a') as log_file:

            log("CONNECTING TO ZTE C600 OLT ...", log_file)
            tn = telnetlib.Telnet(host, port, timeout=timeout)

            # ---- LOGIN ----
            read_until_prompt(tn, "Username:", timeout=10)
            tn.write(username.encode() + b"\n")

            read_until_prompt(tn, "Password:", timeout=10)
            tn.write(password.encode() + b"\n")

            read_until_prompt(tn, "#", timeout=10)
            log("LOGIN SUCCESS", log_file)

            # ---- CONFIG MODE ----
            tn.write(b"configure terminal\n")
            read_until_prompt(tn, "#", timeout=5)
            log("ENTER CONFIG MODE", log_file)

            # ---- ENTER INTERFACE GPON-ONU ----
            # Format ZTE C600/C620: interface gpon_onu-1/2/1:3
            # gpon_onu-{shelf}/{slot}/{port}:{onu_id}
            interface_cmd = f"interface gpon_onu-{shelf}/{slot}/{pon_port}:{onu_id}"
            log(f"ENTER INTERFACE: {interface_cmd}", log_file)
            tn.write(interface_cmd.encode() + b"\n")
            
            output = read_until_prompt(tn, "#", timeout=5)
            log("INTERFACE RESPONSE:", log_file)
            for l in output.splitlines():
                if l.strip():
                    log("  " + l, log_file)
            
            # Check if interface not exist
            if "does not exist" in output.lower() or "invalid" in output.lower() or "error" in output.lower():
                log(f"ERROR: ONU {shelf}/{slot}/{pon_port}:{onu_id} NOT EXIST", log_file)
                print(f"error:ONU {shelf}/{slot}/{pon_port}:{onu_id} not exist or invalid")
                tn.close()
                return

            # ---- SEND RESET COMMAND ----
            log("SEND RESET COMMAND", log_file)
            tn.write(b"reset\n")
            time.sleep(2)
            
            # Read response
            reset_out = tn.read_very_eager().decode('ascii', errors='ignore')
            log("RESET RESPONSE:", log_file)
            for l in reset_out.splitlines():
                if l.strip():
                    log("  " + l, log_file)

            # ---- CHECK RESULT ----
            if "error" in reset_out.lower() or "invalid" in reset_out.lower() or "fail" in reset_out.lower():
                log("RESET COMMAND FAILED", log_file)
                print(f"error:Failed to reset ONU {shelf}/{slot}/{pon_port}:{onu_id}")
            else:
                log("RESET COMMAND SENT SUCCESSFULLY", log_file)
                print(f"success:ONU {shelf}/{slot}/{pon_port}:{onu_id} reset successfully!")

            # ---- EXIT ----
            tn.write(b"exit\n")
            time.sleep(1)
            tn.write(b"exit\n")
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
telnet_zte_c600(ip, port, login, password, shelf, slot, pon_port, onu_id, log_path)
