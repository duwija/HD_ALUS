from telethon import TelegramClient
from telethon.tl.functions.contacts import ImportContactsRequest
from telethon.tl.types import InputPhoneContact
import sys
import asyncio

# Ganti dengan API ID dan API Hash milikmu
api_id = "26874936"
api_hash = "4e3ed0a504c042cf8e3eff2d9be3351d"

# Inisialisasi klien Telethon
client = TelegramClient('../telegram/session_telegram', api_id, api_hash)

async def send_message(phone_number, message):
    await client.start()

    try:
        # Tambahkan nomor ke kontak terlebih dahulu
        contact = InputPhoneContact(client_id=0, phone=phone_number, first_name="User", last_name="")
        result = await client(ImportContactsRequest([contact]))

        if result.users:
            user = result.users[0]
            await client.send_message(user.id, message)
            print(f"✅ Pesan berhasil dikirim ke {phone_number}")
        else:
            print(f"❌ Gagal menemukan user Telegram dengan nomor {phone_number}")
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Gunakan: python3 telegram_send_to_phone.py '+628123456789' 'Pesan Anda'")
        sys.exit(1)

    phone_number = sys.argv[1]  # Ambil nomor dari argumen CLI
    message = sys.argv[2]       # Ambil pesan dari argumen CLI

    with client:
        client.loop.run_until_complete(send_message(phone_number, message))
