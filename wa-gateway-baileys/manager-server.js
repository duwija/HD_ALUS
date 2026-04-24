const fs = require("fs");
const path = require("path");
const express = require("express");
const bodyParser = require("body-parser");
const { execFile } = require("child_process");

const HOST = process.env.MANAGER_HOST || "0.0.0.0";
const PORT = parseInt(process.env.MANAGER_PORT || "30120", 10);
const MANAGER_TOKEN = process.env.MANAGER_TOKEN || "";
const GATEWAY_FILE = path.resolve(__dirname, "gateway.js");
const STORE_FILE = path.resolve(__dirname, "manager-store.json");

const app = express();
app.use(bodyParser.json());

function ensureStore() {
  if (!fs.existsSync(STORE_FILE)) {
    fs.writeFileSync(STORE_FILE, JSON.stringify({ instances: [] }, null, 2));
  }
}

function readStore() {
  ensureStore();
  const raw = JSON.parse(fs.readFileSync(STORE_FILE, "utf8") || "{}");
  const instances = Array.isArray(raw.instances) ? raw.instances : [];
  return { instances };
}

function writeStore(store) {
  fs.writeFileSync(STORE_FILE, JSON.stringify(store, null, 2));
}

function sanitizeName(input) {
  return String(input || "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9-_]/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
}

function makeProcessName(instance) {
  if (instance.pm2Name) return instance.pm2Name;
  return `wa-${sanitizeName(instance.tenant) || "tenant"}-${instance.port}`;
}

function runPm2(args, extraEnv = {}) {
  return new Promise((resolve, reject) => {
    execFile(
      "pm2",
      args,
      {
        cwd: __dirname,
        env: {
          ...process.env,
          ...extraEnv,
        },
      },
      (error, stdout, stderr) => {
      if (error) {
        reject(new Error((stderr || error.message || "pm2 error").trim()));
        return;
      }
      resolve((stdout || "").trim());
      }
    );
  });
}

async function getPm2Names() {
  try {
    const out = await runPm2(["jlist"]);
    const rows = JSON.parse(out || "[]");
    return new Set(rows.map((r) => r.name));
  } catch (_) {
    return new Set();
  }
}

function auth(req, res, next) {
  if (!MANAGER_TOKEN) return next();
  const token = req.headers["x-manager-token"] || req.query.token || "";
  if (token !== MANAGER_TOKEN) {
    return res.status(401).json({ ok: false, message: "Unauthorized" });
  }
  next();
}

app.use("/api", auth);

app.get("/health", (_req, res) => {
  res.json({ ok: true, service: "wa-gateway-manager", port: PORT });
});

app.get("/api/config", async (_req, res) => {
  const store = readStore();
  const pm2Names = await getPm2Names();
  const data = store.instances.map((i) => ({
    ...i,
    pm2Name: makeProcessName(i),
    running: pm2Names.has(makeProcessName(i)),
  }));
  res.json({ ok: true, instances: data });
});

app.post("/api/config", async (req, res) => {
  const tenant = sanitizeName(req.body.tenant);
  const port = parseInt(req.body.port, 10);

  if (!tenant) return res.status(400).json({ ok: false, message: "tenant wajib diisi" });
  if (!Number.isInteger(port) || port < 1024 || port > 65535) {
    return res.status(400).json({ ok: false, message: "port harus antara 1024-65535" });
  }

  const store = readStore();
  if (store.instances.find((i) => i.tenant === tenant)) {
    return res.status(409).json({ ok: false, message: "tenant sudah ada" });
  }
  if (store.instances.find((i) => Number(i.port) === port)) {
    return res.status(409).json({ ok: false, message: "port sudah dipakai" });
  }

  const instance = {
    tenant,
    port,
    pm2Name: `wa-${tenant}-${port}`,
    createdAt: new Date().toISOString(),
  };

  store.instances.push(instance);
  writeStore(store);
  res.json({ ok: true, instance });
});

app.post("/api/instance/:tenant/start", async (req, res) => {
  const tenant = sanitizeName(req.params.tenant);
  const store = readStore();
  const instance = store.instances.find((i) => i.tenant === tenant);
  if (!instance) return res.status(404).json({ ok: false, message: "tenant tidak ditemukan" });

  const pm2Name = makeProcessName(instance);
  await runPm2(
    ["start", GATEWAY_FILE, "--name", pm2Name, "--time", "--update-env"],
    { WA_PORT: String(instance.port) }
  );
  res.json({ ok: true, message: `started ${pm2Name}` });
});

app.post("/api/instance/:tenant/restart", async (req, res) => {
  const tenant = sanitizeName(req.params.tenant);
  const store = readStore();
  const instance = store.instances.find((i) => i.tenant === tenant);
  if (!instance) return res.status(404).json({ ok: false, message: "tenant tidak ditemukan" });

  const pm2Name = makeProcessName(instance);
  await runPm2(["restart", pm2Name, "--update-env"], {
    WA_PORT: String(instance.port),
  });
  res.json({ ok: true, message: `restarted ${pm2Name}` });
});

app.post("/api/instance/:tenant/stop", async (req, res) => {
  const tenant = sanitizeName(req.params.tenant);
  const store = readStore();
  const instance = store.instances.find((i) => i.tenant === tenant);
  if (!instance) return res.status(404).json({ ok: false, message: "tenant tidak ditemukan" });

  const pm2Name = makeProcessName(instance);
  await runPm2(["stop", pm2Name]);
  res.json({ ok: true, message: `stopped ${pm2Name}` });
});

app.delete("/api/instance/:tenant", async (req, res) => {
  const tenant = sanitizeName(req.params.tenant);
  const store = readStore();
  const idx = store.instances.findIndex((i) => i.tenant === tenant);
  if (idx < 0) return res.status(404).json({ ok: false, message: "tenant tidak ditemukan" });

  const instance = store.instances[idx];
  const pm2Name = makeProcessName(instance);

  try {
    await runPm2(["delete", pm2Name]);
  } catch (_) {}

  store.instances.splice(idx, 1);
  writeStore(store);
  res.json({ ok: true, message: `deleted ${tenant}` });
});

app.get("/", (_req, res) => {
  res.type("html").send(`<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>WA Baileys Manager</title>
  <style>
    :root { --bg:#0f172a; --card:#111827; --muted:#94a3b8; --txt:#e2e8f0; --ok:#22c55e; --warn:#f59e0b; --danger:#ef4444; --line:#1f2937; }
    *{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:linear-gradient(120deg,#0b1020,#111827);color:var(--txt);padding:20px}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:rgba(17,24,39,.9);border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:14px}
    h1{font-size:20px;margin:0 0 12px} .muted{color:var(--muted);font-size:13px}
    .row{display:grid;grid-template-columns:1fr 140px 120px;gap:10px}
    input{width:100%;padding:10px;border-radius:10px;border:1px solid var(--line);background:#0b1220;color:var(--txt)}
    button{padding:10px 12px;border:0;border-radius:10px;font-weight:700;cursor:pointer}
    .btn{background:#2563eb;color:#fff}.ok{background:var(--ok)}.warn{background:var(--warn)}.danger{background:var(--danger);color:#fff}.ghost{background:#334155;color:#fff}
    table{width:100%;border-collapse:collapse;font-size:14px} th,td{padding:10px;border-bottom:1px solid var(--line);text-align:left}
    .chip{padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700}
    .running{background:#14532d;color:#bbf7d0}.stopped{background:#3f3f46;color:#d4d4d8}
    .actions{display:flex;gap:6px;flex-wrap:wrap}
    @media (max-width:760px){.row{grid-template-columns:1fr}.actions button{flex:1 1 auto}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>WA Gateway Baileys Manager</h1>
      <div class="muted">Tambah tenant+port, lalu start/restart/stop process gateway via PM2.</div>
    </div>
    <div class="card">
      <div class="row">
        <input id="tenant" placeholder="tenant (contoh: onemedia)" />
        <input id="port" placeholder="30020" type="number" />
        <button class="btn" data-role="add-tenant">Tambah</button>
      </div>
      <div style="margin-top:10px" class="muted">Jika MANAGER_TOKEN diaktifkan, isi token di localStorage key <b>wa_manager_token</b>.</div>
    </div>
    <div class="card">
      <table>
        <thead><tr><th>Tenant</th><th>Port</th><th>Status</th><th>PM2</th><th>Aksi</th></tr></thead>
        <tbody id="rows"></tbody>
      </table>
    </div>
  </div>
<script>
const headers = () => {
  const token = localStorage.getItem('wa_manager_token') || '';
  return token ? {'Content-Type':'application/json','x-manager-token':token} : {'Content-Type':'application/json'};
};
async function api(path, opts={}){
  const res = await fetch(path, { ...opts, headers:{...headers(), ...(opts.headers||{})} });
  const data = await res.json().catch(()=>({ok:false,message:'invalid json'}));
  if(!res.ok || data.ok===false) throw new Error(data.message || ('HTTP '+res.status));
  return data;
}
function rowHtml(it){
  const st = it.running ? '<span class="chip running">RUNNING</span>' : '<span class="chip stopped">STOPPED</span>';
  const tenant = encodeURIComponent(it.tenant);
  return '<tr>' +
    '<td>' + it.tenant + '</td>' +
    '<td>' + it.port + '</td>' +
    '<td>' + st + '</td>' +
    '<td>' + it.pm2Name + '</td>' +
    '<td><div class="actions">' +
      '<button class="ok" data-action="start" data-tenant="' + tenant + '">Start</button>' +
      '<button class="warn" data-action="restart" data-tenant="' + tenant + '">Restart</button>' +
      '<button class="ghost" data-action="stop" data-tenant="' + tenant + '">Stop</button>' +
      '<button class="danger" data-action="delete" data-tenant="' + tenant + '">Hapus</button>' +
    '</div></td>' +
  '</tr>';
}
async function load(){
  try {
    const data = await api('/api/config');
    document.getElementById('rows').innerHTML = data.instances.map(rowHtml).join('') || '<tr><td colspan="5" class="muted">Belum ada tenant.</td></tr>';
  } catch (e) {
    document.getElementById('rows').innerHTML = '<tr><td colspan="5" style="color:#fca5a5">' + e.message + '</td></tr>';
  }
}
async function addTenant(){
  const tenant = document.getElementById('tenant').value.trim();
  const port = Number(document.getElementById('port').value || 0);
  if(!tenant || !port) return alert('tenant dan port wajib diisi');
  try { await api('/api/config',{method:'POST',body:JSON.stringify({tenant,port})}); await load(); }
  catch(e){ alert(e.message); }
}
async function act(tenant, action){
  try { await api('/api/instance/' + tenant + '/' + action,{method:'POST'}); await load(); }
  catch(e){ alert(e.message); }
}
async function delTenant(tenant){
  if(!confirm('Hapus tenant '+tenant+' dari manager?')) return;
  try { await api('/api/instance/' + tenant,{method:'DELETE'}); await load(); }
  catch(e){ alert(e.message); }
}
document.addEventListener('click', (event) => {
  const addButton = event.target.closest('button[data-role="add-tenant"]');
  if (addButton) {
    addTenant();
    return;
  }

  const button = event.target.closest('button[data-action][data-tenant]');
  if (!button) return;

  const tenant = decodeURIComponent(button.dataset.tenant || '');
  const action = button.dataset.action || '';

  if (!tenant || !action) return;
  if (action === 'delete') {
    delTenant(tenant);
    return;
  }

  act(tenant, action);
});
load();
</script>
</body>
</html>`);
});

ensureStore();
app.listen(PORT, HOST, () => {
  console.log(`[MANAGER] Running on http://${HOST}:${PORT}`);
});
