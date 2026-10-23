'use strict';

// OLT telnet bridge: WebSocket (browser xterm.js) <-> raw telnet TCP to an OLT.
//
// Laravel (OltController@telnetToken) issues a short-lived, single-use token
// encrypted with AES-256-GCM under a key derived from APP_KEY. The token holds
// the OLT target (ip/port) and credentials, so the browser can never pick an
// arbitrary host and never sees the OLT password. This process only listens on
// 127.0.0.1; nginx proxies /olt-telnet-ws to it (snippet olt-telnet-ws.conf).
//
// Run by supervisor as user apache (olt-telnet-bridge.conf).

const fs = require('fs');
const path = require('path');
const net = require('net');
const http = require('http');
const crypto = require('crypto');
const { WebSocketServer } = require('ws');

const ROOT = path.resolve(__dirname, '..');
const LISTEN_HOST = process.env.OLT_TELNET_HOST || '127.0.0.1';
const LISTEN_PORT = parseInt(process.env.OLT_TELNET_PORT || '8790', 10);
const WS_PATH = '/olt-telnet-ws';

const CONNECT_TIMEOUT_MS = 10 * 1000;
const IDLE_TIMEOUT_MS = 15 * 60 * 1000;      // no keystroke from the user
const MAX_SESSION_MS = 2 * 60 * 60 * 1000;   // hard cap per session
const AUTOLOGIN_WINDOW_MS = 20 * 1000;
const MAX_SESSIONS = 50;

// ---------------------------------------------------------------- key / token

function readAppKey() {
    const env = fs.readFileSync(path.join(ROOT, '.env'), 'utf8');
    const m = env.match(/^APP_KEY\s*=\s*["']?([^"'\r\n]+)["']?\s*$/m);
    if (!m) throw new Error('APP_KEY not found in .env');
    const raw = m[1].trim();
    return raw.startsWith('base64:') ? Buffer.from(raw.slice(7), 'base64') : Buffer.from(raw, 'utf8');
}

// Must match App\Services\OltTelnetBridge::key().
const KEY = crypto.createHmac('sha256', readAppKey()).update('olt-telnet-bridge').digest();

const usedNonces = new Map(); // nonce => exp (unix seconds)
setInterval(() => {
    const now = Date.now() / 1000;
    for (const [n, exp] of usedNonces) if (exp < now) usedNonces.delete(n);
}, 60 * 1000).unref();

function decodeToken(token) {
    const buf = Buffer.from(String(token || ''), 'base64url');
    if (buf.length < 12 + 16 + 2) throw new Error('malformed token');
    const iv = buf.subarray(0, 12);
    const tag = buf.subarray(12, 28);
    const decipher = crypto.createDecipheriv('aes-256-gcm', KEY, iv);
    decipher.setAuthTag(tag);
    const json = Buffer.concat([decipher.update(buf.subarray(28)), decipher.final()]).toString('utf8');
    const p = JSON.parse(json);
    if (!p.exp || p.exp < Date.now() / 1000) throw new Error('token expired');
    if (!p.nonce || usedNonces.has(p.nonce)) throw new Error('token already used');
    usedNonces.set(p.nonce, p.exp);
    return p;
}

// ---------------------------------------------------------------- audit log

function audit(entry) {
    const now = new Date();
    // Local date in the filename, like Laravel's daily logs.
    const pad = (n) => String(n).padStart(2, '0');
    const day = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    const line = JSON.stringify(Object.assign({ ts: now.toISOString() }, entry)) + '\n';
    fs.appendFile(path.join(ROOT, 'storage', 'logs', `olt-telnet-${day}.log`), line, (err) => {
        if (err) console.error('audit write failed:', err.message);
    });
}

// ---------------------------------------------------------------- telnet

const IAC = 255, DONT = 254, DO = 253, WONT = 252, WILL = 251, SB = 250, SE = 240;
const OPT_ECHO = 1, OPT_SGA = 3, OPT_TTYPE = 24, OPT_NAWS = 31;

// Minimal NVT client: answers option negotiation (accepts remote ECHO/SGA,
// offers TTYPE=XTERM and NAWS window size, refuses everything else) and
// returns the plain data bytes.
class TelnetParser {
    constructor(socket) {
        this.socket = socket;
        this.state = 'data';
        this.cmd = 0;
        this.sb = [];
        this.answered = new Set();
        this.nawsOn = false;
        this.cols = 80;
        this.rows = 24;
    }

    send(bytes) {
        if (!this.socket.destroyed) this.socket.write(Buffer.from(bytes));
    }

    reply(cmd, opt) {
        const k = cmd + ':' + opt;
        if (this.answered.has(k)) return;
        this.answered.add(k);
        this.send([IAC, cmd, opt]);
    }

    sendNaws() {
        if (!this.nawsOn) return;
        const out = [IAC, SB, OPT_NAWS];
        for (const b of [this.cols >> 8, this.cols & 255, this.rows >> 8, this.rows & 255]) {
            out.push(b);
            if (b === IAC) out.push(IAC);
        }
        out.push(IAC, SE);
        this.send(out);
    }

    resize(cols, rows) {
        this.cols = Math.max(20, Math.min(500, cols | 0));
        this.rows = Math.max(5, Math.min(200, rows | 0));
        this.sendNaws();
    }

    handleOption(cmd, opt) {
        if (cmd === DO) {
            if (opt === OPT_NAWS) { this.reply(WILL, opt); this.nawsOn = true; this.sendNaws(); }
            else if (opt === OPT_TTYPE) this.reply(WILL, opt);
            else this.reply(WONT, opt);
        } else if (cmd === DONT) {
            this.reply(WONT, opt);
        } else if (cmd === WILL) {
            if (opt === OPT_ECHO || opt === OPT_SGA) this.reply(DO, opt);
            else this.reply(DONT, opt);
        } else if (cmd === WONT) {
            this.reply(DONT, opt);
        }
    }

    handleSubneg() {
        // TTYPE SEND -> TTYPE IS "XTERM"
        if (this.sb[0] === OPT_TTYPE && this.sb[1] === 1) {
            this.send([IAC, SB, OPT_TTYPE, 0, ...Buffer.from('XTERM'), IAC, SE]);
        }
        this.sb = [];
    }

    parse(chunk) {
        const out = [];
        for (const b of chunk) {
            switch (this.state) {
                case 'data':
                    if (b === IAC) this.state = 'iac'; else out.push(b);
                    break;
                case 'iac':
                    if (b === IAC) { out.push(IAC); this.state = 'data'; }
                    else if (b === DO || b === DONT || b === WILL || b === WONT) { this.cmd = b; this.state = 'opt'; }
                    else if (b === SB) { this.sb = []; this.state = 'sb'; }
                    else this.state = 'data'; // NOP, GA, etc.
                    break;
                case 'opt':
                    this.handleOption(this.cmd, b);
                    this.state = 'data';
                    break;
                case 'sb':
                    if (b === IAC) this.state = 'sbiac';
                    else if (this.sb.length < 256) this.sb.push(b);
                    break;
                case 'sbiac':
                    if (b === SE) { this.handleSubneg(); this.state = 'data'; }
                    else { if (b === IAC && this.sb.length < 256) this.sb.push(IAC); this.state = 'sb'; }
                    break;
            }
        }
        return Buffer.from(out);
    }

    // Browser keystrokes -> telnet: Enter (CR) becomes CR LF, 0xFF is escaped.
    encodeInput(str) {
        const bytes = Buffer.from(str.replace(/\r(?!\n)/g, '\r\n'), 'utf8');
        const out = [];
        for (const b of bytes) { out.push(b); if (b === IAC) out.push(IAC); }
        return Buffer.from(out);
    }
}

const stripAnsi = (s) => s.replace(/\x1b\[[0-9;?]*[A-Za-z]/g, '').replace(/\x1b./g, '');
const PASSWORD_PROMPT = /pass(word)?\s*:\s*$/i;
const USER_PROMPT = /(user\s*name|login|user)\s*:\s*$/i;

// ---------------------------------------------------------------- sessions

let activeSessions = 0;

function startSession(ws, req, p) {
    const sid = crypto.randomBytes(6).toString('hex');
    const who = { sid, tenant: p.tenant, host: p.host, user_id: p.uid, user: p.uname, olt_id: p.olt_id, olt: p.olt_name, target: `${p.ip}:${p.port}`, client_ip: req.headers['x-real-ip'] || req.socket.remoteAddress };
    const startedAt = Date.now();
    let closed = false;
    let outTail = '';
    let inLine = '';
    let sentUser = false, sentPass = false;
    const autologinUntil = startedAt + AUTOLOGIN_WINDOW_MS;

    activeSessions++;
    audit(Object.assign({ event: 'open' }, who));

    const status = (state, message) => {
        if (ws.readyState === ws.OPEN) ws.send(JSON.stringify({ t: 'status', state, message }));
    };

    const socket = net.connect({ host: p.ip, port: p.port });
    const telnet = new TelnetParser(socket);
    socket.setTimeout(CONNECT_TIMEOUT_MS);
    status('connecting', `Menghubungkan ke ${p.ip}:${p.port} ...`);

    let idleTimer = null;
    const resetIdle = () => {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => finish('idle', 'Sesi ditutup: tidak ada aktivitas 15 menit.'), IDLE_TIMEOUT_MS);
    };
    const maxTimer = setTimeout(() => finish('max', 'Sesi ditutup: batas maksimum 2 jam.'), MAX_SESSION_MS);
    const pingTimer = setInterval(() => { if (ws.readyState === ws.OPEN) ws.ping(); }, 30 * 1000);
    resetIdle();

    function finish(reason, message) {
        if (closed) return;
        closed = true;
        activeSessions--;
        clearTimeout(idleTimer); clearTimeout(maxTimer); clearInterval(pingTimer);
        audit(Object.assign({ event: 'close', reason, duration_s: Math.round((Date.now() - startedAt) / 1000) }, who));
        status('closed', message);
        socket.destroy();
        if (ws.readyState === ws.OPEN) ws.close(1000);
    }

    socket.on('connect', () => {
        socket.setTimeout(0);
        socket.setKeepAlive(true, 60 * 1000);
        status('connected', `Terhubung ke ${p.olt_name} (${p.ip}:${p.port})`);
    });
    socket.on('timeout', () => finish('timeout', `Gagal terhubung: timeout ke ${p.ip}:${p.port}`));
    socket.on('error', (err) => finish('error', `Koneksi telnet error: ${err.message}`));
    socket.on('close', () => finish('remote', 'Koneksi ditutup oleh OLT.'));

    socket.on('data', (chunk) => {
        const data = telnet.parse(chunk);
        if (!data.length) return;
        if (ws.readyState === ws.OPEN) ws.send(data);

        outTail = (outTail + stripAnsi(data.toString('latin1'))).slice(-200);

        // Auto-login with the credentials stored on the OLT record.
        if (Date.now() < autologinUntil && !sentPass) {
            if (!sentUser && p.user && USER_PROMPT.test(outTail)) {
                sentUser = true; outTail = '';
                socket.write(telnet.encodeInput(p.user + '\r'));
            } else if (p.pass && PASSWORD_PROMPT.test(outTail)) {
                sentPass = true; outTail = '';
                socket.write(telnet.encodeInput(p.pass + '\r'));
            }
        }
    });

    ws.on('message', (raw, isBinary) => {
        if (closed || isBinary) return;
        let msg;
        try { msg = JSON.parse(raw.toString('utf8')); } catch (e) { return; }

        if (msg.t === 'resize') {
            telnet.resize(msg.cols, msg.rows);
        } else if (msg.t === 'in' && typeof msg.d === 'string' && msg.d.length <= 16384) {
            resetIdle();
            if (!socket.destroyed) socket.write(telnet.encodeInput(msg.d));

            // Audit: record each entered line (approximate — tab completion
            // happens on the OLT). Lines typed at a password prompt are masked.
            const typed = msg.d.replace(/\x1b\[[0-9;?]*[A-Za-z~]/g, '').replace(/\x1b./g, '');
            for (const ch of typed) {
                if (ch === '\r' || ch === '\n') {
                    if (ch === '\r') {
                        const masked = PASSWORD_PROMPT.test(outTail);
                        audit(Object.assign({ event: 'input', line: masked ? '********' : inLine.slice(0, 500) }, who));
                    }
                    inLine = '';
                } else if (ch === '\x7f' || ch === '\b') {
                    inLine = inLine.slice(0, -1);
                } else if (ch >= ' ') {
                    inLine += ch;
                }
            }
        }
    });

    ws.on('close', () => finish('client', 'Browser menutup sesi.'));
    ws.on('error', () => finish('client', 'WebSocket error.'));
}

// ---------------------------------------------------------------- server

const server = http.createServer((req, res) => {
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('not found');
});

const wss = new WebSocketServer({ server, path: WS_PATH, maxPayload: 64 * 1024 });

wss.on('connection', (ws, req) => {
    let payload;
    try {
        const url = new URL(req.url, 'http://localhost');
        payload = decodeToken(url.searchParams.get('token'));

        // The token is bound to the tenant host it was issued on.
        if (req.headers.origin !== `https://${payload.host}`) throw new Error('origin mismatch');
        if (activeSessions >= MAX_SESSIONS) throw new Error('too many active sessions');
        if (!net.isIP(payload.ip) && !/^[A-Za-z0-9.-]+$/.test(payload.ip)) throw new Error('invalid target');
    } catch (e) {
        audit({ event: 'reject', reason: e.message, origin: req.headers.origin || null, client_ip: req.headers['x-real-ip'] || req.socket.remoteAddress });
        ws.send(JSON.stringify({ t: 'status', state: 'closed', message: `Sesi ditolak: ${e.message}. Muat ulang halaman lalu coba lagi.` }));
        ws.close(1008);
        return;
    }
    startSession(ws, req, payload);
});

server.listen(LISTEN_PORT, LISTEN_HOST, () => {
    console.log(`olt-telnet-bridge listening on ${LISTEN_HOST}:${LISTEN_PORT}${WS_PATH}`);
});
