# WA Baileys Manager

Web manager untuk mengelola banyak instance `gateway.js` berdasarkan tenant + port.

## Fitur

- Tambah tenant + port baru
- Start / Restart / Stop process gateway per tenant
- Hapus tenant dari daftar manager
- Menampilkan status running dari PM2
- Opsional auth token via `MANAGER_TOKEN`

## File

- `manager-server.js` : web server manager
- `manager-store.json` : daftar tenant instance
- `gateway.js` : proses WA gateway utama (tetap dipakai)

## Jalankan Lokal

```bash
cd /home/lubax/wa-gateway-baileys
npm install
npm run manager
```

Default manager port: `30120`

Akses:

```text
http://SERVER_IP:30120
```

## Environment Opsional

- `MANAGER_PORT` default `30120`
- `MANAGER_HOST` default `0.0.0.0`
- `MANAGER_TOKEN` default kosong (tanpa auth)

Contoh dengan token:

```bash
MANAGER_PORT=30120 MANAGER_TOKEN='ganti-token-kuat' npm run manager
```

Jika token aktif, set di browser console:

```js
localStorage.setItem('wa_manager_token', 'ganti-token-kuat')
```

## Jalankan via PM2

```bash
cd /home/lubax/wa-gateway-baileys
pm2 start manager-server.js --name wa-manager --time
pm2 save
pm2 startup
```

## Rollout ke Server 103.156.74.19

Disediakan script deploy: `deploy-manager.sh`.

Jalankan dari folder project:

```bash
cd /home/lubax/wa-gateway-baileys
./deploy-manager.sh
```

Jika perlu override user/host/token:

```bash
REMOTE_USER=lubax \
REMOTE_HOST=103.156.74.19 \
MANAGER_PORT=30120 \
MANAGER_TOKEN='ganti-token-kuat' \
./deploy-manager.sh
```

Script akan:

1. `rsync` file manager ke remote path.
2. `npm install --omit=dev` di server remote.
3. start/restart PM2 process `wa-manager` dengan env terbaru.
4. health check `http://127.0.0.1:30120/health` di remote.

## Operasional

1. Tambah tenant + port di UI manager.
2. Klik `Start` untuk menjalankan `gateway.js` tenant tsb.
3. Jika gateway hang, klik `Restart`.
4. Untuk stop sementara, klik `Stop`.

## Catatan Penting

- Process name PM2 dibentuk: `wa-<tenant>-<port>`.
- `WA_PORT` diset otomatis per process saat `Start/Restart`.
- Pastikan port instance tidak bentrok antar tenant.
- `manager-store.json` hanya menyimpan metadata tenant manager; auth whatsapp tetap disimpan di folder `auth_<PORT>` oleh `gateway.js`.
