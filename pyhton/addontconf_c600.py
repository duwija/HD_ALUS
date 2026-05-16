#!/usr/bin/env python3
"""
addontconf_c600.py — Register / configure a new ONU on a ZTE C600 / C620 / C650 OLT.

CLI syntax (per user's reference example):

    configure terminal
    interface gpon_olt-<F/S/P>
    onu <ID> type <TYPE> sn <SN>
    exit
    interface gpon_onu-<F/S/P>:<ID>
    name <CUSTOMER_NAME>
    description <DESCRIPTION>
    tcont 1 profile <TCONT_PROFILE>
    gemport 1 tcont 1
    exit
    interface vport-<F/S/P>.<ID>:1
    service-port 1 user-vlan <VLAN> vlan <VLAN>
    exit
    pon-onu-mng gpon_onu-<F/S/P>:<ID>
    service pppoe gemport 1 vlan <VLAN>
    wan-ip ipv4 mode pppoe username <USR> password <PWD> vlan-profile <VLAN_NAME> host 1
    security-mgmt <ID> state enable mode forward protocol web
    exit
    end
    write

Argument layout MATCHES addontconf.py (C300) so the controller only needs to
swap the script path + interface naming.
"""

import sys
import telnetlib
import datetime
import logging
import os

# --- args (identical positions to addontconf.py) ---
ip                  = sys.argv[1]
login               = sys.argv[2]
password            = sys.argv[3]
port                = sys.argv[4]
timeout             = sys.argv[5]
pon_int             = sys.argv[6]   # e.g. gpon_olt-1/17/5
onu_int             = sys.argv[7]   # e.g. gpon_onu-1/17/5
onu_num             = sys.argv[8]
sn                  = sys.argv[9]
onutype             = sys.argv[10]
vlan                = sys.argv[11]
username_pppoe      = sys.argv[12]
password_pppoe      = sys.argv[13]
description         = sys.argv[14]
vlanname            = sys.argv[15]
tconprofile         = sys.argv[16]
gemportprofileup    = sys.argv[17]  # not used in C600 (kept for arg compat)
gemportprofiledown  = sys.argv[18]  # not used in C600 (kept for arg compat)
customer_name       = sys.argv[19]

logging.basicConfig(filename='olt_log.log', level=logging.INFO)


def _vport_from_pon(pon_int_str, onu_num_str):
    """Derive vport interface name from pon_int.
    pon_int example: 'gpon_olt-1/17/5'  -> vport: 'vport-1/17/5.<onu_num>:1'
    """
    # Strip the prefix up to the first dash; keep everything after first '-'
    suffix = pon_int_str.split('-', 1)[-1] if '-' in pon_int_str else pon_int_str
    return f"vport-{suffix}.{onu_num_str}:1"


def telnet_olt(host, port, username, password, commands, log_path):
    success = False
    tn = None
    try:
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_filename = f"olt_log_{today}.log"
        log_file_path = f"{log_path}/{log_filename}"

        with open(log_file_path, 'a') as log_file:
            tn = telnetlib.Telnet(host, port, timeout=15)

            tn.read_until(b"Username:", timeout=10)
            tn.write(username.encode('ascii') + b"\n")
            tn.read_until(b"Password:", timeout=10)
            tn.write(password.encode('ascii') + b"\n")
            tn.read_until(b"#", timeout=10)

            success = True
            cli_error = None
            for command in commands:
                tn.write(command.encode('ascii') + b"\n")
                output = tn.read_until(b"#", timeout=15).decode('ascii', errors='ignore')
                log_file.write(f"{datetime.datetime.now()}: Output after sending {command}:\n{output}\n")

                low = output.lower()
                if "gpon onu sn already exists" in low or "already exist" in low:
                    log_file.write(f"{datetime.datetime.now()}: Error: ONU SN already exists. cmd='{command}'\n")
                    print("Error: GPON ONU sn already exists!")
                    success = False
                    break
                if "the entry is existed" in low:
                    log_file.write(f"{datetime.datetime.now()}: Error: entry existed. cmd='{command}'\n")
                    print("Error: The ONU ID Already Used!")
                    success = False
                    break
                if "%error" in low or "invalid input" in low or "bad command" in low or "ambiguous command" in low:
                    cli_error = (command, output.strip())
                    log_file.write(f"{datetime.datetime.now()}: CLI error on '{command}':\n{output}\n")
                    success = False
                    # don't break; let remaining cleanup commands flush

            # final flush
            tn.read_until(b"#", timeout=5)

            # Best-effort verification (non-fatal). C600 reject 'show run' shortening, so we use
            # the full form and ignore failures — we already track CLI errors per-command above.
            try:
                verify_command = f"show running-config interface {onu_int}:{onu_num}"
                tn.write(verify_command.encode('ascii') + b"\n")
                verify_output = tn.read_until(b"#", timeout=15).decode('ascii', errors='ignore')
                log_file.write(f"{datetime.datetime.now()}: Verify output:\n{verify_output}\n")
            except Exception as ve:
                log_file.write(f"{datetime.datetime.now()}: Verify skipped: {ve}\n")

            if success:
                print("success:Configuration successful!")
                tn.write(b"write\n")
                tn.read_until(b"#", timeout=20)
            else:
                if cli_error is not None:
                    cmd_str, err_str = cli_error
                    # Trim error message to single line
                    msg = err_str.replace("\r", " ").replace("\n", " ").strip()
                    if len(msg) > 180:
                        msg = msg[:180] + "..."
                    log_file.write(f"{datetime.datetime.now()}: Done with failure on '{cmd_str}': {msg}\n")
                    print(f"error:CLI failed on '{cmd_str}': {msg}")
                else:
                    log_file.write(f"{datetime.datetime.now()}: Done with failure.\n")

    except telnetlib.TelnetException as e:
        logging.error(f"Telnet error: {e}")
        print(f"error:Telnet error: {e}")
    except Exception as e:
        logging.error(f"Error: {e}")
        print(f"error:{e}")
    finally:
        try:
            if tn is not None:
                tn.close()
        except Exception:
            pass


vport_int = _vport_from_pon(pon_int, onu_num)

commands = [
    "configure terminal",
    f"interface {pon_int}",
    f"onu {onu_num} type {onutype} sn {sn}",
    "exit",
    f"interface {onu_int}:{onu_num}",
    f"name {customer_name}",
    f"description {description}",
    f"tcont 1 profile {tconprofile}",
    "gemport 1 tcont 1",
    "exit",
    f"interface {vport_int}",
    f"service-port 1 user-vlan {vlan} vlan {vlan}",
    "exit",
    f"pon-onu-mng {onu_int}:{onu_num}",
    f"service pppoe gemport 1 vlan {vlan}",
    f"wan-ip ipv4 mode pppoe username {username_pppoe} password {password_pppoe} vlan-profile {vlanname} host 1",
    "security-mgmt 1 state enable mode forward protocol web",
    "exit",
    "end",
]

log_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'storage', 'logs'))
telnet_olt(ip, port, login, password, commands, log_path)
