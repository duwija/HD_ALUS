from telethon import TelegramClient, events

# Ganti dengan api_id dan api_hash Anda
api_id = '26874936'
api_hash = '4e3ed0a504c042cf8e3eff2d9be3351d'

# Ganti dengan nomor telepon Anda
phone_number = '+6281934331371'

# Ganti dengan username atau ID grup tujuan
group_username_or_id = '-4670721201'

# Pesan yang ingin dikirim
message = 'Halo, ini pesan dari Telethon!'

# Buat client
client = TelegramClient('session_name', api_id, api_hash)

async def main():
    # Masuk ke akun Telegram
    await client.start(phone_number)

    # Cari grup berdasarkan username atau ID
    group = await client.get_entity(group_username_or_id)

    # Kirim pesan ke grup
    await client.send_message(group, message)

    print("Pesan berhasil dikirim!")

# Jalankan client
with client:
    client.loop.run_until_complete(main())
