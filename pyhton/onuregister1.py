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

# Define the variables
ip = '202.169.255.10'
login = 'duwija'
password = 'rh4ps0dy'
port = 23
timeout = 10
pon_int = 'gpon-olt_1/2/4'
onu_int = 'gpon-onu_1/2/4'
onu_num = '128'
sn = 'ZTEGD33BA05B'
onutype = 'ZTE_ALL'
vlan = '200'
username_pppoe = 'TEST'
password_pppoe = 'TEST'
description = 'ini test aja'
vlanname = 'vlan200'
tconprofile = '100M'
gemportprofileup = '100M'
gemportprofiledown = '100M'

# Define the commands
commands = [
"configure terminal",
f"interface {pon_int}",
f"onu {onu_num} type {onutype} sn {sn}",
"exit",
f"interface {onu_int}:{onu_num}",
f"description {description}",
f"tcont 1 profile {tconprofile}",
"gemport 1 tcont 1",
f"gemport 1 traffic-limit upstream {gemportprofileup} downstream {gemportprofiledown}",
f"service-port 1 vport 1 user-vlan {vlan} vlan {vlan}",
"exit",
f"pon-onu-mng {onu_int}:{onu_num}",
f"service internet gemport 1 vlan {vlan}",
f"wan-ip 1 mode pppoe username {username_pppoe} password {password_pppoe} vlan-profile {vlanname} host 1",
"wan-ip 1 ping-response enable traceroute-response enable",
"security-mgmt 1 state enable mode forward protocol web",
"end",
"exit"
]

# Define the log path
log_path = '/var/www/html/paslink.trikamedia.com/pyhton'

# Call the telnet_olt function with the defined variables
telnet_olt(ip, port, login, password, commands, log_path)
