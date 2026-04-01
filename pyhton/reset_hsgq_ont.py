import sys
import telnetlib
import time
import datetime
import logging
import socket
import os


ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = sys.argv[4]
timeout = sys.argv[5]
pon_int = sys.argv[6]
onu_num = sys.argv[7]

# Set up logging
log_path = os.path.dirname(os.path.abspath(__file__)) + "/logs"
os.makedirs(log_path, exist_ok=True)

def telnet_hsgq_reset(host, port, username, password, pon, onu, log_path):
    try:
        # Generate log filename based on current date
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_filename = f"hsgq_olt_log_{today}.log"
        log_file_path = f"{log_path}/{log_filename}"

        # Open the log file
        with open(log_file_path, 'a') as log_file:
            # Connect to the OLT via Telnet with shorter timeout
            tn = telnetlib.Telnet(host, port, timeout=5)

            # Wait for the initial welcome message
            output = tn.read_until(b"Login:", timeout=5).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Connected to HSGQ OLT\n")

            # Send the username
            tn.write(username.encode('ascii') + b"\n")
            
            # Wait for password prompt
            output = tn.read_until(b"Password:", timeout=5).decode('ascii')
            
            # Send the password
            tn.write(password.encode('ascii') + b"\n")

            # Wait for the prompt
            output = tn.read_until(b">", timeout=5).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Logged in successfully\n")

            # Enter enable mode
            command = "enable"
            tn.write(command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=5).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Entered enable mode\n")

            # Enter configuration mode
            command = "configure"
            tn.write(command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=5).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Entered config mode\n")

            # Factory reset ONU command (HSGQ uses "ont restore-config" for factory reset)
            # Note: "ont reset" is for reboot, "ont restore-config" is for factory reset
            command = f"ont restore-config {pon} {onu}"
            tn.write(command.encode('ascii') + b"\n")
            time.sleep(1)  # Reduced wait time
            output = tn.read_until(b"#", timeout=5).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Command sent: {command}\n")
            log_file.write(f"{datetime.datetime.now()}: Output after factory reset:\n{output}\n")

            # Check if reset was successful
            if "error" in output.lower() or "invalid" in output.lower() or "fail" in output.lower():
                print(f"error:Failed to factory reset ONU PON{pon}/{onu}")
                log_file.write(f"{datetime.datetime.now()}: Failed to factory reset ONU\n")
            else:
                print(f"success:ONU PON{pon}/{onu} factory reset successfully!")
                log_file.write(f"{datetime.datetime.now()}: ONU factory reset successfully\n")

            # Exit configuration mode
            command = "exit"
            tn.write(command.encode('ascii') + b"\n")

            # Close connection immediately
            tn.close()
            log_file.write(f"{datetime.datetime.now()}: Connection closed\n")

    except ConnectionRefusedError as e:
        logging.error(f"Connection refused: {e}")
        print(f"error:Telnet connection refused. Check if Telnet is enabled on OLT")
    except socket.timeout as e:
        logging.error(f"Connection timeout: {e}")
        print(f"error:Connection timeout - {e}")
    except EOFError as e:
        logging.error(f"Connection closed by remote host: {e}")
        print(f"error:Connection closed by OLT - {e}")
    except Exception as e:
        logging.error(f"Unexpected error: {e}")
        print(f"error:Unexpected error - {e}")

# Run the telnet function
telnet_hsgq_reset(ip, int(port), login, password, pon_int, onu_num, log_path)
