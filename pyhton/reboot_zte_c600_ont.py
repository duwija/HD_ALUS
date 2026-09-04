import sys
import telnetlib


def usage_and_exit() -> None:
    print(
        "error:usage python reboot_zte_c600_ont.py "
        "<ip> <user> <password> <port> <timeout> <shelf> <slot> <pon_port> <onu_id>"
    )
    sys.exit(1)


def read_prompt(tn: telnetlib.Telnet, timeout: int = 15) -> str:
    return tn.read_until(b"#", timeout=timeout).decode("ascii", errors="ignore")


def write_and_read_prompt(tn: telnetlib.Telnet, command: str, timeout: int = 15) -> str:
    tn.write(command.encode("ascii") + b"\n")
    return read_prompt(tn, timeout=timeout)


def try_enter_onu_mng_mode(
    tn: telnetlib.Telnet,
    shelf: str,
    slot: str,
    pon_port: str,
    onu_id: str,
) -> tuple[bool, str, str]:
    candidates = [
        f"gpon_onu-{shelf}/{slot}/{pon_port}:{onu_id}",
        f"gpon_onu_{shelf}/{slot}/{pon_port}:{onu_id}",
        f"gpon-onu-{shelf}/{slot}/{pon_port}:{onu_id}",
        f"gpon-onu_{shelf}/{slot}/{pon_port}:{onu_id}",
    ]

    last_output = ""
    for iface in candidates:
        command = f"pon-onu-mng {iface}"
        output = write_and_read_prompt(tn, command, timeout=20)
        last_output = output

        lowered = output.lower()
        if "%error" in lowered or "invalid" in lowered or "incomplete" in lowered:
            continue

        if "config-gpon-onu-mng" in lowered:
            return True, iface, output

    return False, "", last_output


def do_reboot(
    ip: str,
    username: str,
    password: str,
    port: int,
    timeout: int,
    shelf: str,
    slot: str,
    pon_port: str,
    onu_id: str,
) -> tuple[bool, str]:
    tn = telnetlib.Telnet(ip, port, timeout=10)
    try:
        tn.read_until(b"Username:", timeout=15)
        tn.write(username.encode("ascii") + b"\n")
        tn.read_until(b"Password:", timeout=15)
        tn.write(password.encode("ascii") + b"\n")

        output = read_prompt(tn, timeout=20)

        output = write_and_read_prompt(tn, "configure terminal", timeout=20)

        entered, iface, output = try_enter_onu_mng_mode(
            tn, shelf, slot, pon_port, onu_id
        )

        if not entered:
            return False, "failed to enter pon-onu-mng mode"

        tn.write(b"reboot\n")
        idx, _, reboot_output = tn.expect(
            [b"\\[yes/no\\]:", b"#", b"%Error", b"Invalid"],
            timeout=timeout,
        )
        decoded_reboot = reboot_output.decode("ascii", errors="ignore")

        if idx in (2, 3):
            return False, "reboot command rejected by OLT"

        if idx == 0:
            tn.write(b"yes\n")
            confirm_output = read_prompt(tn, timeout=timeout)

            lowered_confirm = confirm_output.lower()
            if "%error" in lowered_confirm or "invalid" in lowered_confirm:
                return False, "confirmation failed on OLT"

        elif idx == -1:
            return False, "no response after reboot command"

        # Exit config mode cleanly
        output = write_and_read_prompt(tn, "end", timeout=15)

        return True, f"ONU {shelf}/{slot}/{pon_port}:{onu_id} reboot sent"
    finally:
        tn.close()


if __name__ == "__main__":
    if len(sys.argv) < 10:
        usage_and_exit()

    ip_arg = sys.argv[1]
    user_arg = sys.argv[2]
    pass_arg = sys.argv[3]

    try:
        port_arg = int(sys.argv[4])
        timeout_arg = int(sys.argv[5])
    except ValueError:
        print("error:port and timeout must be integer")
        sys.exit(1)

    shelf_arg = sys.argv[6]
    slot_arg = sys.argv[7]
    pon_port_arg = sys.argv[8]
    onu_id_arg = sys.argv[9]

    try:
        ok, message = do_reboot(
            ip_arg,
            user_arg,
            pass_arg,
            port_arg,
            timeout_arg,
            shelf_arg,
            slot_arg,
            pon_port_arg,
            onu_id_arg,
        )
        if ok:
            print(f"success:{message}")
            sys.exit(0)

        print(f"error:{message}")
        sys.exit(1)
    except Exception as exc:
        print(f"error:{exc}")
        sys.exit(1)
