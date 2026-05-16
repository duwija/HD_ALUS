#!/usr/bin/env python3
"""
delontconf_c600.py — Delete (unregister) an ONU on a ZTE C600 / C620 / C650 OLT.

Equivalent C600 CLI of the C300 delontconf.py:

    configure terminal
    interface gpon_olt-<F/S/P>
    no onu <ID>
    exit
    end
    write

Argument layout matches delontconf.py so the controller only needs to swap the
script path and pass the C600-style PON path (e.g. "1/16/16").
"""

import sys
import telnetlib
import datetime
import logging
import os

ip       = sys.argv[1]
login    = sys.argv[2]
password = sys.argv[3]
port     = sys.argv[4]
timeout  = sys.argv[5]
pon_int  = sys.argv[6]   # e.g. "1/16/16"
onu_num  = sys.argv[7]

logging.basicConfig(filename='olt_log.log', level=logging.INFO)


def telnet_olt(host, port, username, password, log_path):
    tn = None
    try:
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_file_path = f"{log_path}/olt_log_{today}.log"

        with open(log_file_path, 'a') as log_file:
            tn = telnetlib.Telnet(host, port, timeout=15)

            tn.read_until(b"Username:", timeout=10)
            tn.write(username.encode('ascii') + b"\n")
            tn.read_until(b"Password:", timeout=10)
            tn.write(password.encode('ascii') + b"\n")
            tn.read_until(b"#", timeout=10)

            def send(cmd, timeout=15):
                tn.write(cmd.encode('ascii') + b"\n")
                out = tn.read_until(b"#", timeout=timeout).decode('ascii', errors='ignore')
                log_file.write(f"{datetime.datetime.now()}: Output after sending {cmd}:\n{out}\n")
                return out

            send("configure terminal")
            send(f"interface gpon_olt-{pon_int}")
            out = send(f"no onu {onu_num}")

            if "Successful" in out or "successful" in out:
                print("success:Successfully Deleted ONU")
            else:
                # C600 sometimes returns no explicit message; treat absence of error as success.
                if "% Error" in out or "Invalid input" in out or "Bad command" in out or "Error" in out:
                    log_file.write(f"{datetime.datetime.now()}: Failed Delete ONU.\n")
                    print("error:Failed to Deleted ONU")
                else:
                    print("success:Successfully Deleted ONU")

            send("end")
            send("write")

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


log_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'storage', 'logs'))
telnet_olt(ip, port, login, password, log_path)
