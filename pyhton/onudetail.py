import sys
import telnetlib
import time
import socket
import re

ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = sys.argv[4]
timeout = sys.argv[5]
onu_num = sys.argv[6]
olt_type = sys.argv[7] if len(sys.argv) > 7 else 'c300'  # Default to c300 if not provided

def telnet_olt(host, port, username, password, commands):
    try:
        # Connect to the OLT via Telnet
        tn = telnetlib.Telnet(host, port, timeout=5)
        # Wait for the initial welcome message
        output = tn.read_until(b"Username:", timeout=25).decode('ascii')

        # Send the username and password
        tn.write(username.encode('ascii') + b"\n")
        output = tn.read_until(b"Password:", timeout=25).decode('ascii')
        tn.write(password.encode('ascii') + b"\n")

        # Wait for the final prompt
        output = tn.read_until(b"#", timeout=20).decode('ascii')

        # Execute commands
        for command in commands:
            tn.write(command.encode('ascii') + b"\n")
            output = tn.read_until(b"#", timeout=5).decode('ascii')

            if "Invalid" in output:
                print("error:Failed get the ONU Info!")
                break
            else:
                clean_text = re.sub(r'\x1b\[[0-9;]*m', '', output)  # Removes ANSI escape sequences if any
                clean_text = clean_text.replace('\r', '').strip()
                clean_text = re.sub(r'\S*#\S*', '', clean_text)
                clean_text = re.sub('!', '', clean_text)
                print(clean_text.replace('\n', '<br>'))

        # Close the Telnet connection
        tn.close()

    except (EOFError, OSError, socket.error) as e:
        # Handle Telnet/socket-related exceptions
        print(f"Telnet error: {e}")
    except Exception as e:
        # Handle other exceptions
        print(f"Error: {e}")


commands = [
"terminal length 0",
]

# Add appropriate command based on OLT type
if olt_type.lower() == 'c600':
    # ZXAN C600 command format
    # onu_num format: "frame/slot/port:onuId" -> need vport-frame/slot/port.onuId:1
    fsp, onuId = onu_num.rsplit(':', 1)
    commands.extend([
       
        f"show pon onu information gpon_onu-{onu_num}",
        f"show running-config-interface gpon_onu-{onu_num}",
        f"show running-config-interfac vport-{fsp}.{onuId}:1",
    ])
else:
    # C300/C320 command formats (using underscores in gpon-onu_xx/xx/xx:xx)
    commands.extend([
        f"show run interface gpon-onu_{onu_num}",
        f"show onu running config gpon-onu_{onu_num}",
        f"show gpon onu detail-info gpon-onu_{onu_num}",
        f"show gpon remote-onu interface eth gpon-onu_{onu_num}",
    ])

commands.append("end")

# Call the telnet_olt function with the defined variables
telnet_olt(ip, port, login, password, commands)
