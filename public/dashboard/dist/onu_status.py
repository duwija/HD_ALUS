import getpass
import telnetlib
import time
import re
import socket
import sys
host = sys.argv[1]
user = sys.argv[2]
password = sys.argv[3]
port = sys.argv[4]
onu = sys.argv[5]
onu_x =  onu.replace(":", " ")
# host = '202.169.255.10'
search = "^"+onu+" (\w+)"
# user = 'duwija'  # input("Enter username: ")
# password = 'rh4ps0dy'  # getpass.getpass()

try:
	tn = telnetlib.Telnet(host, port, 3)
	tn.read_until(b"Username:")
	tn.write(user.encode('ascii') + b"\n")
	if password:
	    tn.read_until(b"Password:")
	    tn.write(password.encode('ascii') + b"\n")

	cmd ="show gpon onu state gpon-olt_"+onu_x
	
	tn.write(cmd.encode('ascii') + b"\n")
	time.sleep(1)
	fetch = str(tn.read_very_eager().decode('ascii'))
	try:
		# up_db = re.search('Tx:\d+.\d+\d+\d+', fetch).group(0).replace('\r', '')
		# down_db = re.search('Rx:-\d+.\d+\d+', fetch).group(0).replace('\r', '')
		

		
		result = re.search(r'working', fetch)
		if result:
			cmds ="show pon power attenuation gpon-onu_"+onu
	
			tn.write(cmds.encode('ascii') + b"\n")
			time.sleep(1)
			fetch = str(tn.read_very_eager().decode('ascii'))
			
			try:
				up_db = re.search('Tx:\d+.\d+\d+\d+', fetch).group(0).replace('\r', '')
				down_db = re.search('Rx:-\d+.\d+\d+', fetch).group(0).replace('\r', '')

				tn.write("exit".encode('ascii') + b"\n")


				print('Working :', up_db, down_db)
			except AttributeError:
				print("Can't found the Onu ID")
		else:
			result = re.search(r'DyingGasp', fetch)
			if result:
				print ('DyingGasp')
			else:
				result = re.search(r'LOS', fetch)
				if result:
					print ('LOS')
		tn.write("exit".encode('ascii') + b"\n")
		# print (result.groups())
# print(tn.read_all().decode('ascii'))
		
	except AttributeError:
		print("Can't found the Onu ID")
except socket.timeout:
    print("Can't Connect to OLT")