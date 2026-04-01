import telnetlib
import time
import datetime

def telnet_olt(host, port, username, password, commands, log_path):
    try:
        # Generate log filename based on current date
        today = datetime.datetime.now().strftime("%Y-%m-%d")
        log_filename = f"olt_log_{today}.log"
        log_file_path = f"{log_path}/{log_filename}"

        # Open the log file
        with open(log_file_path, 'a') as log_file:
            # Connect to the OLT via Telnet
            tn = telnetlib.Telnet(host, port, timeout=20)

            # Wait for the initial welcome message
            output = tn.read_until(b"Username: ", timeout=20).decode('ascii')
            log_file.write(f"Initial output from OLT:\n{output}\n")

            # Send the username and password
            tn.write(username.encode('ascii') + b"\n")
            tn.write(password.encode('ascii') + b"\n")

            # Wait for the final prompt
            output = tn.read_until(b"#", timeout=20).decode('ascii')
            log_file.write(f"Output after sending password:\n{output}\n")

            # Execute commands
            for command in commands:
                tn.write(command.encode('ascii') + b"\n")
                time.sleep(2)  # Wait for the command to execute

                # Read output until final prompt
                output = tn.read_until(b"#", timeout=20).decode('ascii')
                log_file.write(f"Output after command '{command}':\n{output}\n")

            # Get final output after executing all commands
            output = tn.read_until(b"#", timeout=20).decode('ascii')
            log_file.write(f"Final command output:\n{output}\n")

        # Close the Telnet connection
        tn.close()

    except Exception as e:
        # Handle the exception and log it
        with open(log_file_path, 'a') as log_file:
            log_file.write(f"Error: {e}\n")
            print(f"Error: {e}")

# Example usage
host = '202.169.255.10'  # OLT IP address
port = 23             # Telnet default port
username = 'duwija'
password = 'rh4ps0dy'
commands = [
'config terminal',
'interface gpon-olt_1/2/4',
'onu 128 type ZTE_ALL sn ZTEGD33BA05B',
'exit',
]
log_path = '/var/www/html/twinnet.trikamedia.com/pyhton'  # Replace with your log directory path

telnet_olt(host, port, username, password, commands, log_path)
