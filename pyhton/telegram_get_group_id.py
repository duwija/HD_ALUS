from telethon import TelegramClient

# Ganti dengan API ID dan API Hash milikmu
api_id = 26874936
api_hash = "4e3ed0a504c042cf8e3eff2d9be3351d"

# Inisialisasi klien Telethon
client = TelegramClient("session_name", api_id, api_hash)

async def list_groups():
    """Menampilkan daftar grup yang diikuti beserta ID-nya"""
    try:
        # Mendapatkan semua entitas yang terkait dengan grup
        all_chats = await client.get_dialogs()
        for chat in all_chats:
            if chat.is_group:  # Memastikan hanya grup yang ditampilkan
                print(f"Nama Grup: {chat.name}, ID Grup: {chat.id}")
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    with client:
        client.loop.run_until_complete(list_groups())
