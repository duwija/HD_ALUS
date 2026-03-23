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
#success = False
# Set up logging
logging.basicConfig(filename='olt_log.log', level=logging.INFO)

def telnet_olt(host, port, username, password, log_path):
    try:
        # Generate log filename based on current date
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_filename = f"olt_log_{today}.log"
        log_file_path = f"{log_path}/{log_filename}"

        # Open the log file
        with open(log_file_path, 'a') as log_file:
            # Connect to the OLT via Telnet
            tn = telnetlib.Telnet(host, port, timeout=10)
            # Wait for the initial welcome message
            output = tn.read_until(b"Username: ", timeout=10).decode('ascii')
#            log_file.write(f"{datetime.datetime.now()}: Initial output from OLT:\n{output}\n")
            # Send the username and password
            tn.write(username.encode('ascii') + b"\n")
            output = tn.read_until(b"Password: ", timeout=20).decode('ascii')
            tn.write(password.encode('ascii') + b"\n")
            # Wait for the final prompt
            output = tn.read_until(b"#", timeout=20).decode('ascii')
#            log_file.write(f"{datetime.datetime.now()}: Output after sending password:\n{output}\n")
            # Execute commands
            command = "configure terminal"
            tn.write(command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=25).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Output after sending {command}:\n{output}\n")
            command = f"interface gpon-olt_{pon_int}"
            tn.write(command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=15).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Output after sending {command}:\n{output}\n")
            command = f"no onu {onu_num}"
            tn.write(command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=15).decode('ascii')
            log_file.write(f"{datetime.datetime.now()}: Output after sending {command}:\n{output}\n")
            if "Successful" in output:
                print("success:Successfully Deleted ONU")
                #log_file.write(f"{datetime.datetime.now()}: Success Deleted ONU.\n")
                #success = True
            else:
                print("error:Failed to Deleted ONU")
                log_file.write(f"{datetime.datetime.now()}: Failed Delete ONU.\n")
            command = "end"
            tn.write(command.encode('ascii') + b"\n")
            # Get final output after executing all commands
            output = tn.read_until(b"#", timeout=20).decode('ascii')
           # log_file.write(f"{datetime.datetime.now()}: Final command output:\n{output}\n")
            #if success:
             #   print("success:Successfully Deleted ONU")
            #else:
             #   print("error:Failed to Delete ONU!!")
        # Close the Telnet connection
        tn.close()

    except telnetlib.TelnetException as e:
        # Handle Telnet-specific exceptions
        logging.error(f"Telnet error: {e}")
        print(f"Telnet error: {e}")
        # Handle socket-related exceptions
        logging.error(f"Socket error: {e}")
        print(f"Socket error: {e}")
    except Exception as e:
        # Handle other exceptions
        logging.error(f"Error: {e}")
        print(f"Error: {e}")


# Define the log path
log_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'storage', 'logs'))
#print("Value of result:", commands)
# Call the telnet_olt function with the defined variables
telnet_olt(ip, port, login, password, log_path)
