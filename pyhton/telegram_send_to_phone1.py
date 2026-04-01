from telethon import TelegramClient
import sys
import asyncio

# Ganti dengan API ID dan API Hash milikmu
api_id = 27100075
api_hash = "8231487461400441459a400320c34cfd"

# Inisialisasi klien Telethon
client = TelegramClient('../telegram/session_telegram', api_id, api_hash)

async def send_message(phone_number, message):
    await client.start()
#    print(f"Mencari pengguna dengan nomor {phone_number}...")

    try:
        user = await client.get_entity(phone_number)
#        print(f"Pengguna ditemukan: {user.id}")
        await client.send_message(user.id, message)
        print("✅ Pesan berhasil dikirim!")
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Gunakan: python3 send_to_phone.py '+628123456789' 'Pesan Anda'")
        sys.exit(1)

    phone_number = sys.argv[1]  # Ambil nomor dari argumen CLI
    message = sys.argv[2]       # Ambil pesan dari argumen CLI

#    print("🚀 Memulai Telethon...")
    with client:
        client.loop.run_until_complete(send_message(phone_number, message))
