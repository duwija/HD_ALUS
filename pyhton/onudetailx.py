import sys
import telnetlib
import time
import datetime
import logging
import socket
import re
ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = sys.argv[4]
timeout = sys.argv[5]
#pon_int = sys.argv[6]
onu_num = sys.argv[6]
# Set up logging
#logging.basicConfig(filename='olt_log.log', level=logging.INFO)
def telnet_olt(host, port, username, password, commands, log_path):
    try:
        # Generate log filename based on current date
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_filename = f"olt_log_{today}.log"
        log_file_path = f"{log_path}/{log_filename}"

        # Open the log file
        with open(log_file_path, 'a') as log_file:
            # Connect to the OLT via Telnet
            tn = telnetlib.Telnet(host, port, timeout=5)
            #log_file.write(f"{datetime.datetime.now()}: Initial output from OLT:\n{output}\n")
            # Wait for the initial welcome message
            output = tn.read_until(b"Username:", timeout=20).decode('ascii')
            #log_file.write(f"{datetime.datetime.now()}: Initial output from OLT:\n{output}\n")

            # Send the username and password
            tn.write(username.encode('ascii') + b"\n")
            tn.write(password.encode('ascii') + b"\n")

            # Wait for the final prompt
            output = tn.read_until(b"#", timeout=20).decode('ascii')
            #log_file.write(f"{datetime.datetime.now()}: Output after sending password:\n{output}\n")

            # Execute commands
            for command in commands:
                tn.write(command.encode('ascii') + b"\n")
                output =tn.read_until(b"#", timeout=5).decode('ascii')


                if "Invalid" in output:
                  # log_file.write(f"{datetime.datetime.now()}: Failed reboor the ONU!.\n")
                   print("error:Failed get the ONU Info!")
                   break
                   success = False

                else:
                   #log_file.write(f"{datetime.datetime.now()}: ONT rebooted successfully.\n")
                   clean_text = re.sub(r'\x1b\[[0-9;]*m', '', output)  # Removes ANSI escape sequences if any
                   clean_text = clean_text.replace('\r', '').strip()
                   clean_text = re.sub(r'\S*#\S*', '', clean_text)
                   clean_text = re.sub('!', '', clean_text)
#                   formatted_output = f"<pre>{clean_text}</pre>" 
                   print(clean_text.replace('\n', '<br>'))
#                   print(output)
                   success = True


         #   if success:
          #      print("success:ONU rebooted successfully")
           # else:
            #    print("error:Failed Get Data")


            # Get final output after executing all commands
#            output = tn.read_until(b"#", timeout=20).decode('ascii')
#            log_file.write(f"{datetime.datetime.now()}: Final command output:\n{output}\n")	

        # Close the Telnet connection
#        print("done")
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


commands = [
"terminal length 0",
f"show run interface gpon-onu_{onu_num}",
f"show onu running config gpon-onu_{onu_num}",
f"show gpon onu detail-info gpon-onu_{onu_num}",
"end"
]

# Define the log path
log_path = ''
# Call the telnet_olt function with the defined variables
telnet_olt(ip, port, login, password, commands, log_path)
