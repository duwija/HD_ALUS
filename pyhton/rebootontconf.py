import sys
import telnetlib
import time
import datetime
import socket


ip = sys.argv[1]
login = sys.argv[2]
password = sys.argv[3]
port = sys.argv[4]
timeout = sys.argv[5]
pon_int = sys.argv[6]
onu_num = sys.argv[7]


def telnet_olt(host, port, username, password):
    try:
        # Connect to the OLT via Telnet
        tn = telnetlib.Telnet(host, port, timeout=10)

        # Wait for the initial welcome message
        output = tn.read_until(b"Username:", timeout=15).decode('ascii')

        # Send the username and password
        tn.write(username.encode('ascii') + b"\n")
        output = tn.read_until(b"Password:", timeout=15).decode('ascii')
        tn.write(password.encode('ascii') + b"\n")

        # Wait for the final prompt
        output = tn.read_until(b"#", timeout=10).decode('ascii')

        command = "configure terminal"
        tn.write(command.encode('ascii') + b"\n")
        output = tn.read_until(b"#", timeout=25).decode('ascii')
        command = f"pon-onu-mng gpon-onu_{pon_int}:{onu_num}"
        tn.write(command.encode('ascii') + b"\n")
        output = tn.read_until(b"#", timeout=15).decode('ascii')
        command = "reboot"
        tn.write(command.encode('ascii') + b"\n")
        output = tn.read_until(b"#", timeout=15).decode('ascii')
        command = "yes"
        tn.write(command.encode('ascii') + b"\n")
        output = tn.read_until(b"#", timeout=15).decode('ascii')

        if "Invalid" in output:
            print("error:Failed to Reboot ONU")
        else:
            print("success:ONU Rebooted successfuly!")
        command = "end"
        tn.write(command.encode('ascii') + b"\n")
        # Get final output after executing all commands
        output = tn.read_until(b"#", timeout=20).decode('ascii')

        tn.close()

    except (EOFError, OSError, socket.error) as e:
        # Handle Telnet/socket-related exceptions
        print(f"Telnet error: {e}")
    except Exception as e:
        # Handle other exceptions
        print(f"Error: {e}")


# Call the telnet_olt function with the defined variables
telnet_olt(ip, port, login, password)
