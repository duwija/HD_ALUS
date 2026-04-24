require("dotenv").config();
const fs = require("fs");
const path = require("path");
const express = require("express");
const cors = require("cors");
const bodyParser = require("body-parser");
const { Client, LocalAuth } = require("whatsapp-web.js");

// =============================
// KONFIGURASI
// =============================
const PORT = 30020;
const APP_URL = process.env.APP_URL || "http://localhost";

const AUTH_DIR = path.resolve(__dirname, `.wwebjs_auth_${PORT}`);
const CHROME_DIR = `/tmp/chrome-wa-${PORT}`;
const SESSIONS_FILE = path.resolve(__dirname, `sessions_${PORT}.json`);

if (!fs.existsSync(AUTH_DIR)) fs.mkdirSync(AUTH_DIR, { recursive: true });
if (!fs.existsSync(CHROME_DIR)) fs.mkdirSync(CHROME_DIR, { recursive: true });

if (!fs.existsSync(SESSIONS_FILE))
    fs.writeFileSync(SESSIONS_FILE, JSON.stringify([]));

let sessionList = JSON.parse(fs.readFileSync(SESSIONS_FILE, "utf8") || "[]");

// =============================
// EXPRESS SERVER
// =============================
const app = express();
const router = express.Router();
app.use(cors({ origin: "*" }));
app.use(bodyParser.json());
app.use("/api", router);

// =============================
// INTERNAL STORAGE
// =============================
const clients = {};
const qrCodes = {};
const statuses = {};
const meta = {};
const authFailCounts = {};    // track auth failure retries
const reconnectTimers = {};   // track pending reconnect timers
const readyAt = {};           // track when client became ready

const MAX_AUTH_RETRIES = 5;
const RECONNECT_DELAY = 5000;
const SEND_TIMEOUT_MS = 30000;

function log(level, msg) {
    const ts = new Date().toISOString();
    console[level === "error" ? "error" : "log"](`[${ts}] [${level.toUpperCase()}] ${msg}`);
}

function saveSessionList() {
    try {
        fs.writeFileSync(SESSIONS_FILE, JSON.stringify(sessionList, null, 2));
    } catch (e) {
        log("error", `Failed to save sessions file: ${e.message}`);
    }
}

function cleanupSessionFolders(sessionName) {
    const authPath = `${AUTH_DIR}/session-${PORT}_${sessionName}`;
    if (fs.existsSync(authPath)) {
        fs.rmSync(authPath, { recursive: true, force: true });
        log("info", `Deleted auth folder: ${authPath}`);
    }

    const chromePath = `${CHROME_DIR}/${sessionName}`;
    if (fs.existsSync(chromePath)) {
        fs.rmSync(chromePath, { recursive: true, force: true });
        log("info", `Deleted chrome folder: ${chromePath}`);
    }
}

// ==================================================
//      SAFELY DESTROY CLIENT (kill orphan chrome)
// ==================================================
async function safeDestroy(client) {
    try {
        if (client && client.pupBrowser) {
            const proc = client.pupBrowser.process();
            if (proc) {
                proc.kill("SIGKILL");
            }
        }
    } catch (_) {}
    try {
        await client.destroy();
    } catch (_) {}
}

// ==================================================
//        MEMBUAT CLIENT WHATSAPP
// ==================================================
function createClient(sessionName) {
    // Cancel any pending reconnect timer
    if (reconnectTimers[sessionName]) {
        clearTimeout(reconnectTimers[sessionName]);
        delete reconnectTimers[sessionName];
    }

    // Destroy old client if exists
    if (clients[sessionName]) {
        const old = clients[sessionName];
        delete clients[sessionName];
        safeDestroy(old);
    }

    log("info", `Creating client: ${sessionName}`);

    const chromeProfile = `${CHROME_DIR}/${sessionName}`;
    if (!fs.existsSync(chromeProfile)) fs.mkdirSync(chromeProfile, { recursive: true });

    const client = new Client({
        authStrategy: new LocalAuth({
            clientId: `${PORT}_${sessionName}`,
            dataPath: AUTH_DIR
        }),
        puppeteer: {
            executablePath: "/usr/bin/chromium",
            headless: true,
            args: [
                "--no-sandbox",
                "--disable-setuid-sandbox",
                "--disable-dev-shm-usage",
                "--disable-gpu",
                "--single-process",
                "--no-zygote",
                "--disable-extensions",
                "--disable-background-timer-throttling",
                "--disable-renderer-backgrounding"
            ]
        }
    });

    clients[sessionName] = client;
    statuses[sessionName] = "initializing";

    // QR Event
    client.on("qr", (qr) => {
        if (clients[sessionName] !== client) return;
        qrCodes[sessionName] = qr;
        statuses[sessionName] = "not_authenticated";
        log("info", `QR GENERATED: ${sessionName}`);
    });

    // Ready
    client.on("ready", () => {
        if (clients[sessionName] !== client) return;
        statuses[sessionName] = "authenticated";
        qrCodes[sessionName] = null;
        authFailCounts[sessionName] = 0;
        readyAt[sessionName] = Date.now();

        try {
            const info = client.info;
            meta[sessionName] = {
                number: info.wid.user,
                name: info.pushname || "-",
                platform: info.platform
            };
        } catch (e) {}

        log("info", `AUTHENTICATED & READY: ${sessionName}`);
    });

    // Authenticated (session saved)
    client.on("authenticated", () => {
        if (clients[sessionName] !== client) return;
        statuses[sessionName] = "authenticated";
        authFailCounts[sessionName] = 0;
        log("info", `AUTHENTICATED EVENT: ${sessionName}`);
    });

    // Auth failure
    client.on("auth_failure", async (message) => {
        if (clients[sessionName] !== client) return;

        statuses[sessionName] = "auth_failure";
        qrCodes[sessionName] = null;
        log("error", `AUTH FAILURE [${sessionName}] => ${message}`);

        delete clients[sessionName];
        await safeDestroy(client);
        cleanupSessionFolders(sessionName);

        // Retry with limit
        const count = (authFailCounts[sessionName] || 0) + 1;
        authFailCounts[sessionName] = count;

        if (count <= MAX_AUTH_RETRIES) {
            const delay = Math.min(count * 2000, 15000);
            log("info", `Auth retry ${count}/${MAX_AUTH_RETRIES} for ${sessionName} in ${delay}ms`);
            reconnectTimers[sessionName] = setTimeout(() => createClient(sessionName), delay);
        } else {
            log("error", `Auth retries exhausted for ${sessionName}. Manual restart needed.`);
            statuses[sessionName] = "auth_failed_permanent";
        }
    });

    // Disconnected — auto-reconnect
    client.on("disconnected", async (reason) => {
        if (clients[sessionName] !== client) return;

        statuses[sessionName] = "not_authenticated";
        qrCodes[sessionName] = null;
        log("warn", `Disconnected ${sessionName}, reason: ${reason}`);

        delete clients[sessionName];
        await safeDestroy(client);

        // Auto-reconnect after delay (keep session/auth intact for re-login)
        log("info", `Auto-reconnecting ${sessionName} in ${RECONNECT_DELAY}ms...`);
        reconnectTimers[sessionName] = setTimeout(() => createClient(sessionName), RECONNECT_DELAY);
    });

    // Loading screen progress
    client.on("loading_screen", (percent, message) => {
        if (clients[sessionName] !== client) return;
        log("info", `Loading [${sessionName}]: ${percent}% - ${message}`);
    });

    client.initialize().catch((err) => {
        log("error", `Initialize failed [${sessionName}]: ${err.message}`);
        if (clients[sessionName] === client) {
            delete clients[sessionName];
            statuses[sessionName] = "init_failed";

            // Retry initialization after delay
            reconnectTimers[sessionName] = setTimeout(() => {
                log("info", `Retrying initialize for ${sessionName}...`);
                createClient(sessionName);
            }, RECONNECT_DELAY);
        }
    });
}

// ==================================================
//             LOAD SESSIONS OTOMATIS
// ==================================================
// Stagger session startup to avoid resource spikes
sessionList.forEach((s, i) => {
    setTimeout(() => createClient(s), i * 3000);
});

// ==================================================
//                    API ROUTES
// ==================================================

// HEALTH
router.get("/health", (req, res) => {
    const sessionInfo = {};
    sessionList.forEach(s => {
        sessionInfo[s] = {
            status: statuses[s] || "unknown",
            hasClient: !!clients[s],
            meta: meta[s] || null
        };
    });
    res.json({
        status: "ok",
        port: PORT,
        uptime: process.uptime(),
        memory: Math.round(process.memoryUsage().rss / 1024 / 1024) + "MB",
        sessions: sessionInfo
    });
});

// START SESSION
router.post("/start", (req, res) => {
    const session = req.body.session;

    if (!session) return res.status(400).json({ error: "session required" });

    // Prevent duplicate session entries
    if (!sessionList.includes(session)) {
        sessionList.push(session);
        saveSessionList();
    }

    if (!clients[session]) {
        createClient(session);
        return res.json({ status: "started", session });
    }

    return res.json({ status: "already_running", session });
});

// STATUS / QR
router.get("/:session/qr", (req, res) => {
    const session = req.params.session;

    if (!clients[session] && !statuses[session])
        return res.status(404).json({ error: "not found" });

    res.json({
        status: statuses[session] || "unknown",
        qr: qrCodes[session] || null,
        ...(meta[session] || {})
    });
});

// SEND MESSAGE (with timeout)
router.post("/:session/send", async (req, res) => {
    const session = req.params.session;
    const client = clients[session];

    if (!client) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });

    const number = req.body.number;
    const message = req.body.message;

    if (!number || !message) {
        return res.status(400).json({ error: "number and message required" });
    }

    const jid = number.includes("@") ? number : `${number}@c.us`;

    try {
        // Send with timeout to prevent hanging
        const sendPromise = client.sendMessage(jid, message);
        const timeoutPromise = new Promise((_, reject) =>
            setTimeout(() => reject(new Error("Send timeout after " + SEND_TIMEOUT_MS + "ms")), SEND_TIMEOUT_MS)
        );

        const sent = await Promise.race([sendPromise, timeoutPromise]);
        res.json({ status: "sent", id: sent.id._serialized });
    } catch (err) {
        log("error", `SEND ERROR [${session}] => ${err.message}`);

        // Detect detached/crashed Puppeteer browser and auto-restart
        const isBrowserDead = err.message && (
            err.message.includes("detached Frame") ||
            err.message.includes("getChat") ||
            err.message.includes("Session closed") ||
            err.message.includes("Target closed") ||
            err.message.includes("Protocol error") ||
            err.message.includes("Send timeout")
        );

        if (isBrowserDead) {
            log("warn", `Browser crash detected on [${session}], restarting client...`);
            statuses[session] = "not_authenticated";
            qrCodes[session] = null;
            delete clients[session];
            await safeDestroy(client);
            reconnectTimers[session] = setTimeout(() => createClient(session), 2000);
        }

        res.status(500).json({ error: err.message });
    }
});

// ==================================================
//              CHATS LIST
// ==================================================
router.get("/:session/chats", async (req, res) => {
    const session = req.params.session;
    const client = clients[session];

    if (!client) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });

    // Wait if just recently became ready (WWeb internal needs time to load)
    const elapsed = Date.now() - (readyAt[session] || 0);
    if (elapsed < 10000) {
        await new Promise(r => setTimeout(r, Math.min(10000 - elapsed, 8000)));
    }

    try {
        const chats = await client.getChats();
        const result = chats.map(chat => ({
            id: chat.id._serialized,
            name: chat.name || chat.id.user,
            isGroup: chat.isGroup,
            unreadCount: chat.unreadCount || 0,
            timestamp: chat.timestamp || 0,
            lastMessage: chat.lastMessage ? {
                body: chat.lastMessage.body,
                timestamp: chat.lastMessage.timestamp,
                fromMe: chat.lastMessage.fromMe
            } : null
        }));

        // Sort by timestamp descending (most recent first)
        result.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

        res.json(result);
    } catch (err) {
        log("error", `CHATS ERROR [${session}] => ${err.message}`);

        // Retry once after 3 seconds (WWeb may need more time)
        try {
            await new Promise(r => setTimeout(r, 3000));
            const chats = await client.getChats();
            const result = chats.map(chat => ({
                id: chat.id._serialized,
                name: chat.name || chat.id.user,
                isGroup: chat.isGroup,
                unreadCount: chat.unreadCount || 0,
                timestamp: chat.timestamp || 0
            }));
            result.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));
            res.json(result);
        } catch (retryErr) {
            log("error", `CHATS RETRY FAILED [${session}] => ${retryErr.message}`);
            res.status(500).json({ error: "Chat list not ready yet, please try again in a few seconds" });
        }
    }
});

// ==================================================
//              CHAT HISTORY
// ==================================================
router.get("/:session/history", async (req, res) => {
    const session = req.params.session;
    const client = clients[session];
    const chatId = req.query.chatId;

    if (!client) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });
    if (!chatId) return res.status(400).json({ error: "chatId required" });

    try {
        const chat = await client.getChatById(chatId);
        const messages = await chat.fetchMessages({ limit: 50 });

        const result = messages.map(msg => ({
            id: msg.id._serialized,
            body: msg.body,
            fromMe: msg.fromMe,
            timestamp: msg.timestamp,
            from: msg.from,
            to: msg.to,
            type: msg.type,
            hasMedia: msg.hasMedia
        }));

        res.json(result);
    } catch (err) {
        log("error", `HISTORY ERROR [${session}] => ${err.message}`);
        res.status(500).json({ error: err.message });
    }
});

// ==================================================
//              GROUPS LIST
// ==================================================
router.get("/:session/groups", async (req, res) => {
    const session = req.params.session;
    const client = clients[session];

    if (!client) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });

    try {
        const chats = await client.getChats();
        const groups = chats
            .filter(c => c.isGroup)
            .map(g => ({
                id: g.id._serialized,
                name: g.name,
                participantCount: g.participants ? g.participants.length : 0
            }));

        res.json(groups);
    } catch (err) {
        log("error", `GROUPS ERROR [${session}] => ${err.message}`);
        res.status(500).json({ error: err.message });
    }
});

// ==================================================
//                LOGOUT (QR MUNCUL)
// ==================================================
router.post("/:session/logout", async (req, res) => {
    const session = req.params.session;
    log("info", `LOGOUT REQUEST for ${session}`);

    const client = clients[session];

    // Delete reference FIRST to prevent stale events
    delete clients[session];
    statuses[session] = "not_authenticated";
    qrCodes[session] = null;
    delete meta[session];

    // Logout then destroy
    if (client) {
        try { await client.logout(); } catch (_) {}
        await safeDestroy(client);
    }

    // Cleanup auth folders
    cleanupSessionFolders(session);

    log("info", `LOGOUT SUCCESS for ${session}`);

    // Recreate client for new QR
    reconnectTimers[session] = setTimeout(() => createClient(session), 1500);

    return res.json({ status: "logged_out", session });
});

// ==================================================
//                   RESTART SESSION
// ==================================================
router.post("/:session/restart", async (req, res) => {
    const session = req.params.session;
    log("info", `RESTART REQUEST for ${session}`);

    if (clients[session]) {
        const old = clients[session];
        delete clients[session];
        await safeDestroy(old);
    }

    createClient(session);
    res.json({ status: "restarted" });
});

// ==================================================
//              DELETE SESSION (PERMANENT)
// ==================================================
router.delete("/:session", async (req, res) => {
    const session = req.params.session;
    log("info", `DELETE REQUEST for ${session}`);

    if (reconnectTimers[session]) {
        clearTimeout(reconnectTimers[session]);
        delete reconnectTimers[session];
    }

    if (clients[session]) {
        const old = clients[session];
        delete clients[session];
        try { await old.logout(); } catch (_) {}
        await safeDestroy(old);
    }

    delete statuses[session];
    delete qrCodes[session];
    delete meta[session];
    delete authFailCounts[session];

    // Remove from session list
    sessionList = sessionList.filter(s => s !== session);
    saveSessionList();

    cleanupSessionFolders(session);

    res.json({ status: "deleted", session });
});

// ==================================================
//           GRACEFUL SHUTDOWN (PM2 SIGTERM)
// ==================================================
async function gracefulShutdown(signal) {
    log("info", `Received ${signal}, shutting down gracefully...`);

    // Cancel all reconnect timers
    Object.keys(reconnectTimers).forEach(k => {
        clearTimeout(reconnectTimers[k]);
        delete reconnectTimers[k];
    });

    // Destroy all clients
    const destroyPromises = Object.keys(clients).map(async (s) => {
        try {
            log("info", `Destroying client: ${s}`);
            await safeDestroy(clients[s]);
        } catch (_) {}
    });

    await Promise.allSettled(destroyPromises);
    log("info", "All clients destroyed. Exiting.");
    process.exit(0);
}

process.on("SIGTERM", () => gracefulShutdown("SIGTERM"));
process.on("SIGINT", () => gracefulShutdown("SIGINT"));

// ==================================================
//           PREVENT UNHANDLED CRASH
// ==================================================
process.on("uncaughtException", (err) => {
    log("error", `UNCAUGHT EXCEPTION: ${err.message}`);
    log("error", err.stack);
    // Don't exit — let PM2 decide to restart if needed
});

process.on("unhandledRejection", (reason) => {
    log("error", `UNHANDLED REJECTION: ${reason}`);
});

// ==================================================
app.listen(PORT, "0.0.0.0", () =>
    log("info", `WA Gateway running on port ${PORT}`)
);
