import sys
import telnetlib
import time
import socket


ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = sys.argv[4]
timeout = sys.argv[5]
pon_int = sys.argv[6]
onu_int = sys.argv[7]
onu_num = sys.argv[8]
sn = sys.argv[9]
onutype = sys.argv[10]
vlan = sys.argv[11]
username_pppoe = sys.argv[12]
password_pppoe = sys.argv[13]
description = sys.argv[14]
vlanname = sys.argv[15]
tconprofile = sys.argv[16]
gemportprofileup = sys.argv[17]
gemportprofiledown = sys.argv[18]
customer_name = sys.argv[19]

def telnet_olt(host, port, username, password, commands):
    try:
        # Connect to the OLT via Telnet
        tn = telnetlib.Telnet(host, port, timeout=10)

        # Wait for the initial welcome message
        output = tn.read_until(b"Username:", timeout=10).decode('ascii')

        # Send the username and password
        tn.write(username.encode('ascii') + b"\n")
        output = tn.read_until(b"Password:", timeout=10).decode('ascii')
        tn.write(password.encode('ascii') + b"\n")

        # Wait for the final prompt
        output = tn.read_until(b"#", timeout=10).decode('ascii')

        # Execute commands
        success = True
        for command in commands:
            tn.write(command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=10).decode('ascii')

            if "GPON ONU sn already exists" in output:
                print("Error: GPON ONU sn already exists!")
                success = False
                break
            elif "The entry is existed" in output:
                print("Error: The ONU ID Already Used!")
                success = False
                break

        # Get final output after executing all commands
        output = tn.read_until(b"#", timeout=10).decode('ascii')

        if success:
            # Verify configuration
            verify_command = f"show run interface {onu_int}:{onu_num}"
            tn.write(verify_command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=10).decode('ascii')

            # Check if the configuration is successful
            if f"description {description}" in output and f"tcont 1 profile {tconprofile}" in output and "gemport 1 tcont 1" in output and f"service-port 1 vport 1 user-vlan {vlan} vlan {vlan}" in output:
                print("success:Configuration successful!")
                write = "write"
                tn.write(write.encode('ascii') + b"\n")
            else:
                print("error:Configuration is Failed!")

        # Close the Telnet connection
        tn.close()

    except (EOFError, OSError, socket.error) as e:
        # Handle Telnet/socket-related exceptions
        print(f"Telnet error: {e}")
    except Exception as e:
        # Handle other exceptions
        print(f"Error: {e}")


# Define the commands
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
f"gemport 1 traffic-limit upstream {gemportprofileup} downstream {gemportprofiledown}",
f"service-port 1 vport 1 user-vlan {vlan} vlan {vlan}",
"exit",
#f"pon-onu-mng {onu_int}:{onu_num}",
#f"service internet gemport 1 vlan {vlan}",
#f"wan-ip 1 mode pppoe username {username_pppoe} password {password_pppoe} vlan-profile {vlanname} host 1",
#"wan-ip 1 ping-response enable traceroute-response enable",
#"security-mgmt 1 state enable mode forward protocol web",
#"exit",
"end"
#"write"
]

# Call the telnet_olt function with the defined variables
telnet_olt(ip, port, login, password, commands)
