/**
 * WA Gateway - Baileys Edition (Multi-Tenant)
 * Drop-in replacement for whatsapp-web.js gateway
 * API 100% compatible with onemedia_3002.js
 *
 * Memory: ~30-50 MB vs ~700-1700 MB (Chromium)
 */

const fs = require("fs");
const path = require("path");
const express = require("express");
const cors = require("cors");
const bodyParser = require("body-parser");
const multer = require("multer");
const pino = require("pino");
const {
    default: makeWASocket,
    downloadMediaMessage,
    useMultiFileAuthState,
    DisconnectReason,
    makeCacheableSignalKeyStore,
    fetchLatestBaileysVersion,
    Browsers,
} = require("@whiskeysockets/baileys");
const QRCode = require("qrcode");

// =============================
// KONFIGURASI
// =============================
const PORT = parseInt(process.env.WA_PORT || "30020", 10);
const AUTH_DIR = path.resolve(__dirname, `auth_${PORT}`);
const SESSIONS_FILE = path.resolve(__dirname, `sessions_${PORT}.json`);
const CACHE_FILE = path.resolve(__dirname, `cache_${PORT}.json`);

if (!fs.existsSync(AUTH_DIR)) fs.mkdirSync(AUTH_DIR, { recursive: true });
if (!fs.existsSync(SESSIONS_FILE))
    fs.writeFileSync(SESSIONS_FILE, JSON.stringify([]));

let sessionList = JSON.parse(fs.readFileSync(SESSIONS_FILE, "utf8") || "[]");

// =============================
// EXPRESS SERVER
// =============================
const app = express();
const router = express.Router();
const upload = multer({ storage: multer.memoryStorage() });
app.use(cors({ origin: "*" }));
app.use(bodyParser.json());
app.use("/api", router);

// =============================
// INTERNAL STORAGE
// =============================
const sockets = {};
const qrCodes = {};       // base64 QR images
const qrRaw = {};         // raw QR string for dashboard
const statuses = {};
const meta = {};
const chatMap = {};        // { sessionName: { chatId: chatData } }
const messageMap = {};     // { sessionName: { chatId: [messages] } }
const reconnectTimers = {};
const authFailCounts = {};
const historyFetchLocks = {};

const MAX_AUTH_RETRIES = 5;
const RECONNECT_DELAY = 5000;
const SEND_TIMEOUT_MS = 30000;
const MAX_MESSAGES_PER_CHAT = 100;
let cacheSaveTimer = null;

// Silent pino logger (suppress Baileys verbose output)
const logger = pino({ level: "silent" });

function log(level, msg) {
    const ts = new Date().toISOString();
    console[level === "error" ? "error" : "log"](
        `[${ts}] [${level.toUpperCase()}] ${msg}`
    );
}

function saveSessionList() {
    try {
        fs.writeFileSync(SESSIONS_FILE, JSON.stringify(sessionList, null, 2));
    } catch (e) {
        log("error", `Failed to save sessions file: ${e.message}`);
    }
}

function loadRuntimeCache() {
    if (!fs.existsSync(CACHE_FILE)) return;
    try {
        const raw = JSON.parse(fs.readFileSync(CACHE_FILE, "utf8") || "{}");
        Object.assign(chatMap, raw.chatMap || {});
        Object.assign(messageMap, raw.messageMap || {});
        log("info", `Loaded cache file: ${CACHE_FILE}`);
    } catch (e) {
        log("warn", `Failed to load cache file: ${e.message}`);
    }
}

function saveRuntimeCacheNow() {
    try {
        fs.writeFileSync(
            CACHE_FILE,
            JSON.stringify(
                {
                    updatedAt: new Date().toISOString(),
                    chatMap,
                    messageMap,
                },
                null,
                2
            )
        );
    } catch (e) {
        log("warn", `Failed to save cache file: ${e.message}`);
    }
}

function scheduleCacheSave() {
    if (cacheSaveTimer) return;
    cacheSaveTimer = setTimeout(() => {
        cacheSaveTimer = null;
        saveRuntimeCacheNow();
    }, 1000);
}

function cleanupSessionFolders(sessionName) {
    const authPath = path.join(AUTH_DIR, sessionName);
    if (fs.existsSync(authPath)) {
        fs.rmSync(authPath, { recursive: true, force: true });
        log("info", `Deleted auth folder: ${authPath}`);
    }
}

function normalizeJid(target) {
    if (!target) return null;
    if (target.includes("@")) return target;
    return target.replace(/[\+\s\-]/g, "") + "@s.whatsapp.net";
}

function normalizeTimestamp(ts) {
    if (!ts) return 0;
    if (typeof ts === "number") return ts;
    if (typeof ts === "string") {
        const num = Number(ts);
        return Number.isFinite(num) ? num : 0;
    }
    if (typeof ts === "object") {
        if (typeof ts.low === "number") return ts.low;
        if (typeof ts.seconds === "number") return ts.seconds;
        if (typeof ts._seconds === "number") return ts._seconds;
        if (typeof ts.toNumber === "function") {
            try {
                return ts.toNumber();
            } catch (_) {}
        }
    }
    return 0;
}

function extractMessageBody(message = {}) {
    return (
        message.conversation ||
        message.extendedTextMessage?.text ||
        message.imageMessage?.caption ||
        message.videoMessage?.caption ||
        message.documentMessage?.caption ||
        message.buttonsResponseMessage?.selectedDisplayText ||
        message.listResponseMessage?.title ||
        ""
    );
}

function detectMediaType(message = {}) {
    if (message.imageMessage) return "image";
    if (message.videoMessage) return "video";
    if (message.audioMessage) return "audio";
    if (message.documentMessage) return "document";
    if (message.stickerMessage) return "sticker";
    return null;
}

async function requestHistorySync(session, chatId, count = 30) {
    const sock = sockets[session];
    if (!sock || !chatId) return false;

    const lockKey = `${session}:${chatId}`;
    if (historyFetchLocks[lockKey]) return false;
    historyFetchLocks[lockKey] = true;

    try {
        const jid = normalizeJid(chatId);
        const list = (messageMap[session]?.[jid] || []).slice();
        list.sort((a, b) => normalizeTimestamp(a?.messageTimestamp) - normalizeTimestamp(b?.messageTimestamp));

        const oldest = list[0];
        const oldestKey = oldest?.key?.id
            ? {
                  remoteJid: oldest.key.remoteJid || jid,
                  id: oldest.key.id,
                  fromMe: !!oldest.key.fromMe,
                  participant: oldest.key.participant,
              }
            : {
                  remoteJid: jid,
                  id: `bootstrap-${Date.now()}`,
                  fromMe: false,
              };

        const oldestTs = oldest ? normalizeTimestamp(oldest.messageTimestamp) : Math.floor(Date.now() / 1000);

        await sock.fetchMessageHistory(count, oldestKey, oldestTs);
        await new Promise((resolve) => setTimeout(resolve, 1500));
        return true;
    } catch (err) {
        log("warn", `HISTORY FETCH REQUEST FAILED [${session}] ${chatId} => ${err.message}`);
        return false;
    } finally {
        delete historyFetchLocks[lockKey];
    }
}

function getMediaPayload(file, caption) {
    const mimeType = file.mimetype || "application/octet-stream";
    const category = mimeType.split("/")[0];
    const payload = {
        mimetype: mimeType,
        fileName: file.originalname,
        caption: caption || undefined,
    };

    if (category === "image") {
        return { image: file.buffer, ...payload };
    }
    if (category === "video") {
        return { video: file.buffer, ...payload };
    }
    if (category === "audio") {
        return { audio: file.buffer, ptt: false, ...payload };
    }
    return { document: file.buffer, ...payload };
}

function findMessageById(session, chatId, messageId) {
    const targetChatId = normalizeJid(chatId);
    const mediaRichnessScore = (msg) => {
        const media =
            msg?.message?.imageMessage ||
            msg?.message?.videoMessage ||
            msg?.message?.audioMessage ||
            msg?.message?.documentMessage;

        if (!media) return 0;

        let score = 1;
        if (media.mediaKey) score += 4;
        if (media.directPath) score += 4;
        if (media.url) score += 2;
        if (media.fileEncSha256) score += 2;
        if (media.fileSha256) score += 1;
        return score;
    };

    const pickBest = (list) => {
        const candidates = (list || []).filter((m) => m?.key?.id === messageId);
        if (candidates.length === 0) return null;

        candidates.sort((a, b) => {
            const synA = a?.__synthetic ? 1 : 0;
            const synB = b?.__synthetic ? 1 : 0;
            if (synA !== synB) return synA - synB;

            const richA = mediaRichnessScore(a);
            const richB = mediaRichnessScore(b);
            if (richA !== richB) return richB - richA;

            const tsA = normalizeTimestamp(a?.messageTimestamp);
            const tsB = normalizeTimestamp(b?.messageTimestamp);
            return tsB - tsA;
        });

        return candidates[0];
    };

    let message = pickBest(messageMap[session]?.[targetChatId] || []);
    if (message) return message;

    const allChats = Object.values(messageMap[session] || {});
    for (const msgs of allChats) {
        message = pickBest(msgs);
        if (message) return message;
    }

    return null;
}

function upsertMessageToCache(session, jid, msg) {
    if (!messageMap[session]) messageMap[session] = {};
    if (!messageMap[session][jid]) messageMap[session][jid] = [];

    const id = msg?.key?.id;
    if (id) {
        const idx = messageMap[session][jid].findIndex((m) => m?.key?.id === id);
        if (idx >= 0) {
            const existing = messageMap[session][jid][idx] || {};
            // Prefer real event payload over synthetic placeholder.
            messageMap[session][jid][idx] = {
                ...existing,
                ...msg,
                __synthetic: !!(existing.__synthetic && msg.__synthetic),
            };
        } else {
            messageMap[session][jid].push(msg);
        }
    } else {
        messageMap[session][jid].push(msg);
    }

    if (messageMap[session][jid].length > MAX_MESSAGES_PER_CHAT) {
        messageMap[session][jid] = messageMap[session][jid].slice(-MAX_MESSAGES_PER_CHAT);
    }

    if (!chatMap[session]) chatMap[session] = {};
    if (!chatMap[session][jid]) {
        chatMap[session][jid] = {
            id: jid,
            name: jid.split("@")[0],
            unreadCount: 0,
        };
    }

    chatMap[session][jid].conversationTimestamp = normalizeTimestamp(msg.messageTimestamp) || Math.floor(Date.now() / 1000);
    scheduleCacheSave();
}

// ==================================================
//        MEMBUAT CLIENT WHATSAPP (BAILEYS)
// ==================================================
async function createClient(sessionName) {
    // Cancel any pending reconnect timer
    if (reconnectTimers[sessionName]) {
        clearTimeout(reconnectTimers[sessionName]);
        delete reconnectTimers[sessionName];
    }

    // Close old socket if exists
    if (sockets[sessionName]) {
        try {
            sockets[sessionName].end();
        } catch (_) {}
        delete sockets[sessionName];
    }

    log("info", `Creating client: ${sessionName}`);
    statuses[sessionName] = "initializing";

    try {
        const authDir = path.join(AUTH_DIR, sessionName);
        if (!fs.existsSync(authDir)) fs.mkdirSync(authDir, { recursive: true });

        const { state, saveCreds } = await useMultiFileAuthState(authDir);
        const { version } = await fetchLatestBaileysVersion();

        // Initialize chat & message maps
        if (!chatMap[sessionName]) chatMap[sessionName] = {};
        if (!messageMap[sessionName]) messageMap[sessionName] = {};

        const sock = makeWASocket({
            version,
            logger,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, logger),
            },
            browser: Browsers.ubuntu("Chrome"),
            printQRInTerminal: false,
            generateHighQualityLinkPreview: false,
            // Pull richer history on connect so old chats/media can render in web UI.
            syncFullHistory: true,
            shouldSyncHistoryMessage: () => true,
            markOnlineOnConnect: false,
        });

        sockets[sessionName] = sock;

        // ---- CONNECTION UPDATE ----
        sock.ev.on("connection.update", async (update) => {
            const { connection, lastDisconnect, qr: qrCode } = update;

            // QR received
            if (qrCode) {
                qrRaw[sessionName] = qrCode;
                try {
                    qrCodes[sessionName] = await QRCode.toDataURL(qrCode);
                } catch (_) {
                    qrCodes[sessionName] = null;
                }
                statuses[sessionName] = "not_authenticated";
                log("info", `QR GENERATED: ${sessionName}`);
            }

            if (connection === "close") {
                const statusCode =
                    lastDisconnect?.error?.output?.statusCode;

                log(
                    "warn",
                    `Disconnected ${sessionName}, code: ${statusCode}, reason: ${lastDisconnect?.error?.message || "unknown"}`
                );

                // Logged out by phone — clean session & show QR
                if (statusCode === DisconnectReason.loggedOut) {
                    statuses[sessionName] = "not_authenticated";
                    qrCodes[sessionName] = null;
                    qrRaw[sessionName] = null;
                    delete meta[sessionName];
                    delete sockets[sessionName];
                    cleanupSessionFolders(sessionName);

                    log("info", `Session ${sessionName} logged out. Recreating for QR...`);
                    reconnectTimers[sessionName] = setTimeout(
                        () => createClient(sessionName),
                        2000
                    );
                    return;
                }

                // Restartable disconnect
                if (statusCode === DisconnectReason.restartRequired) {
                    log("info", `Restart required for ${sessionName}`);
                    delete sockets[sessionName];
                    await createClient(sessionName);
                    return;
                }

                // Connection lost / timeout — auto reconnect
                const count = (authFailCounts[sessionName] || 0) + 1;
                authFailCounts[sessionName] = count;

                if (count <= MAX_AUTH_RETRIES) {
                    statuses[sessionName] = "reconnecting";
                    const d = Math.min(count * RECONNECT_DELAY, 30000);
                    log("info", `Reconnecting ${sessionName} in ${d}ms (attempt ${count}/${MAX_AUTH_RETRIES})`);
                    delete sockets[sessionName];
                    reconnectTimers[sessionName] = setTimeout(
                        () => createClient(sessionName),
                        d
                    );
                } else {
                    statuses[sessionName] = "auth_failed_permanent";
                    log("error", `Reconnect retries exhausted for ${sessionName}`);
                }
            }

            if (connection === "open") {
                statuses[sessionName] = "authenticated";
                qrCodes[sessionName] = null;
                qrRaw[sessionName] = null;
                authFailCounts[sessionName] = 0;

                // Get user info
                try {
                    const user = sock.user;
                    meta[sessionName] = {
                        number: user.id.split(":")[0].split("@")[0],
                        name: user.name || "-",
                        platform: "baileys",
                    };
                } catch (_) {}

                log("info", `AUTHENTICATED & READY: ${sessionName}`);
            }
        });

        // ---- SAVE CREDS ----
        sock.ev.on("creds.update", saveCreds);

        // ---- CHAT UPSERT (track chats) ----
        sock.ev.on("chats.upsert", (chats) => {
            for (const chat of chats) {
                chatMap[sessionName][chat.id] = {
                    ...chatMap[sessionName][chat.id],
                    ...chat,
                };
            }
            scheduleCacheSave();
        });

        sock.ev.on("chats.update", (updates) => {
            for (const update of updates) {
                if (chatMap[sessionName][update.id]) {
                    Object.assign(chatMap[sessionName][update.id], update);
                }
            }
            scheduleCacheSave();
        });

        sock.ev.on("chats.delete", (deletions) => {
            for (const id of deletions) {
                delete chatMap[sessionName][id];
            }
            scheduleCacheSave();
        });

        // ---- CONTACTS UPDATE (get names) ----
        sock.ev.on("contacts.upsert", (contacts) => {
            for (const contact of contacts) {
                if (chatMap[sessionName][contact.id]) {
                    chatMap[sessionName][contact.id].name =
                        contact.notify || contact.name || chatMap[sessionName][contact.id].name;
                } else {
                    chatMap[sessionName][contact.id] = {
                        id: contact.id,
                        name: contact.notify || contact.name || contact.id.split("@")[0],
                    };
                }
            }
            scheduleCacheSave();
        });

        sock.ev.on("contacts.update", (updates) => {
            for (const update of updates) {
                if (chatMap[sessionName][update.id]) {
                    if (update.notify) chatMap[sessionName][update.id].name = update.notify;
                }
            }
            scheduleCacheSave();
        });

        // ---- MESSAGING HISTORY (initial sync) ----
        sock.ev.on("messaging-history.set", ({ chats, messages, isLatest }) => {
            log("info", `History sync [${sessionName}]: ${chats.length} chats, ${messages.length} msgs, isLatest=${isLatest}`);
            for (const chat of chats) {
                chatMap[sessionName][chat.id] = {
                    ...chatMap[sessionName][chat.id],
                    ...chat,
                };
            }
            for (const msg of messages) {
                const chatId = msg.key.remoteJid;
                if (!chatId) continue;
                upsertMessageToCache(sessionName, chatId, msg);
            }
            scheduleCacheSave();
        });

        // ---- MESSAGE UPSERT ----
        sock.ev.on("messages.upsert", (m) => {
            for (const msg of m.messages) {
                const chatId = msg.key.remoteJid;
                if (!chatId) continue;

                // Track message
                upsertMessageToCache(sessionName, chatId, msg);

                // Update chat last message timestamp
                if (chatMap[sessionName][chatId]) {
                    chatMap[sessionName][chatId].conversationTimestamp =
                        normalizeTimestamp(msg.messageTimestamp) || Math.floor(Date.now() / 1000);
                } else {
                    chatMap[sessionName][chatId] = {
                        id: chatId,
                        name: chatId.split("@")[0],
                        conversationTimestamp: normalizeTimestamp(msg.messageTimestamp) || Math.floor(Date.now() / 1000),
                    };
                }

                // Log incoming
                if (m.type === "notify" && !msg.key.fromMe && msg.message) {
                    const body =
                        extractMessageBody(msg.message) ||
                        (detectMediaType(msg.message) ? `[${detectMediaType(msg.message)}]` : "[system]");
                    log("info", `MSG IN [${sessionName}] from ${chatId}: ${body.substring(0, 50)}`);
                }
            }
            scheduleCacheSave();
        });

    } catch (err) {
        log("error", `Initialize failed [${sessionName}]: ${err.message}`);
        statuses[sessionName] = "init_failed";
        delete sockets[sessionName];

        reconnectTimers[sessionName] = setTimeout(() => {
            log("info", `Retrying initialize for ${sessionName}...`);
            createClient(sessionName);
        }, RECONNECT_DELAY);
    }
}

// ==================================================
//             LOAD SESSIONS OTOMATIS
// ==================================================
loadRuntimeCache();

sessionList.forEach((s, i) => {
    setTimeout(() => createClient(s), i * 3000);
});

// ==================================================
//                    API ROUTES
// ==================================================

// HEALTH
router.get("/health", (req, res) => {
    const sessionInfo = {};
    sessionList.forEach((s) => {
        sessionInfo[s] = {
            status: statuses[s] || "unknown",
            hasClient: !!sockets[s],
            meta: meta[s] || null,
        };
    });
    res.json({
        status: "ok",
        port: PORT,
        uptime: process.uptime(),
        memory: Math.round(process.memoryUsage().rss / 1024 / 1024) + "MB",
        engine: "baileys",
        sessions: sessionInfo,
    });
});

// START SESSION
router.post("/start", (req, res) => {
    const session = req.body.session;
    if (!session) return res.status(400).json({ error: "session required" });

    if (!sessionList.includes(session)) {
        sessionList.push(session);
        saveSessionList();
    }

    if (!sockets[session]) {
        createClient(session);
        return res.json({ status: "started", session });
    }

    return res.json({ status: "already_running", session });
});

// STATUS / QR
router.get("/:session/qr", (req, res) => {
    const session = req.params.session;

    if (!sockets[session] && !statuses[session])
        return res.status(404).json({ error: "not found" });

    res.json({
        status: statuses[session] || "unknown",
        qr: qrRaw[session] || null,
        qrImage: qrCodes[session] || null,
        ...(meta[session] || {}),
    });
});

// SEND MESSAGE (with timeout)
router.post("/:session/send", async (req, res) => {
    const session = req.params.session;
    const sock = sockets[session];

    if (!sock) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });

    const number = req.body.number;
    const message = req.body.message;

    if (!number || !message) {
        return res.status(400).json({ error: "number and message required" });
    }

    // Format JID: support both "628xxx" and "628xxx@s.whatsapp.net"
    const jid = normalizeJid(number);

    try {
        const sendPromise = sock.sendMessage(jid, { text: message });
        const timeoutPromise = new Promise((_, reject) =>
            setTimeout(
                () => reject(new Error(`Send timeout after ${SEND_TIMEOUT_MS}ms`)),
                SEND_TIMEOUT_MS
            )
        );

        const sent = await Promise.race([sendPromise, timeoutPromise]);
        upsertMessageToCache(session, jid, {
            key: {
                remoteJid: jid,
                id: sent?.key?.id || `local-${Date.now()}`,
                fromMe: true,
            },
            message: { conversation: message },
            messageTimestamp: Math.floor(Date.now() / 1000),
            __synthetic: true,
        });
        res.json({
            status: "sent",
            id: sent.key.id,
        });
    } catch (err) {
        log("error", `SEND ERROR [${session}] => ${err.message}`);

        // Detect connection dead
        const isDead =
            err.message.includes("Connection Closed") ||
            err.message.includes("Send timeout") ||
            err.message.includes("Timed Out");

        if (isDead) {
            log("warn", `Connection dead on [${session}], reconnecting...`);
            statuses[session] = "reconnecting";
            delete sockets[session];
            reconnectTimers[session] = setTimeout(
                () => createClient(session),
                2000
            );
        }

        res.status(500).json({ error: err.message });
    }
});

// SEND MEDIA / FILE
router.post("/:session/send-media", upload.single("file"), async (req, res) => {
    const session = req.params.session;
    const sock = sockets[session];

    if (!sock) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated") {
        return res.status(400).json({ error: "not authenticated" });
    }

    const number = req.body.number;
    const caption = req.body.caption || "";
    const file = req.file;

    if (!number || !file) {
        return res.status(400).json({ error: "number and file required" });
    }

    try {
        const jid = normalizeJid(number);
        const mediaPayload = getMediaPayload(file, caption);
        const sent = await sock.sendMessage(jid, mediaPayload);

        const syntheticMediaMessage = {};
        const category = (file.mimetype || "").split("/")[0];
        if (category === "image") {
            syntheticMediaMessage.imageMessage = { caption: caption || "", mimetype: file.mimetype };
        } else if (category === "video") {
            syntheticMediaMessage.videoMessage = { caption: caption || "", mimetype: file.mimetype };
        } else if (category === "audio") {
            syntheticMediaMessage.audioMessage = { mimetype: file.mimetype };
        } else {
            syntheticMediaMessage.documentMessage = {
                caption: caption || "",
                mimetype: file.mimetype,
                fileName: file.originalname,
            };
        }

        upsertMessageToCache(session, jid, {
            key: {
                remoteJid: jid,
                id: sent?.key?.id || `local-${Date.now()}`,
                fromMe: true,
            },
            message: syntheticMediaMessage,
            messageTimestamp: Math.floor(Date.now() / 1000),
            __synthetic: true,
        });

        res.json({
            status: "sent",
            id: sent.key.id,
            type: file.mimetype,
            fileName: file.originalname,
        });
    } catch (err) {
        log("error", `SEND MEDIA ERROR [${session}] => ${err.message}`);
        res.status(500).json({ error: err.message });
    }
});

// MARK CHAT AS READ
router.post("/:session/read", async (req, res) => {
    const session = req.params.session;
    const sock = sockets[session];
    const chatId = req.body.chatId;

    if (!sock) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated") {
        return res.status(400).json({ error: "not authenticated" });
    }
    if (!chatId) return res.status(400).json({ error: "chatId required" });

    try {
        const jid = normalizeJid(chatId);
        const unreadMessages = (messageMap[session]?.[jid] || [])
            .filter((msg) => msg?.key?.id && !msg.key.fromMe)
            .slice(-20)
            .map((msg) => ({
                remoteJid: msg.key.remoteJid,
                id: msg.key.id,
                fromMe: false,
                participant: msg.key.participant,
            }));

        if (unreadMessages.length > 0) {
            await sock.readMessages(unreadMessages);
        }

        if (chatMap[session]?.[jid]) {
            chatMap[session][jid].unreadCount = 0;
        }

        res.json({ status: "read", chatId: jid, marked: unreadMessages.length });
    } catch (err) {
        log("error", `MARK READ ERROR [${session}] => ${err.message}`);
        res.status(500).json({ error: err.message });
    }
});

// GET MEDIA CONTENT FROM MESSAGE
router.get("/:session/media", async (req, res) => {
    const session = req.params.session;
    const sock = sockets[session];
    const chatId = req.query.chatId;
    const messageId = req.query.messageId;

    if (!sock) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated") {
        return res.status(400).json({ error: "not authenticated" });
    }
    if (!chatId || !messageId) {
        return res.status(400).json({ error: "chatId and messageId required" });
    }

    try {
        const msg = findMessageById(session, chatId, messageId);
        if (!msg) return res.status(404).json({ error: "message not found" });

        const mimeType =
            msg.message?.imageMessage?.mimetype ||
            msg.message?.videoMessage?.mimetype ||
            msg.message?.audioMessage?.mimetype ||
            msg.message?.documentMessage?.mimetype ||
            "application/octet-stream";

        const fileName =
            msg.message?.documentMessage?.fileName ||
            `wa-media-${messageId}`;

        const buffer = await downloadMediaMessage(
            msg,
            "buffer",
            {},
            { logger, reuploadRequest: sock.updateMediaMessage }
        );

        res.setHeader("Content-Type", mimeType);
        res.setHeader("Cache-Control", "private, max-age=60");
        res.setHeader("Content-Disposition", `inline; filename=\"${fileName}\"`);
        return res.send(buffer);
    } catch (err) {
        log("error", `MEDIA FETCH ERROR [${session}] => ${err.message}`);
        return res.status(500).json({ error: err.message });
    }
});

// ==================================================
//              CHATS LIST
// ==================================================
router.get("/:session/chats", async (req, res) => {
    const session = req.params.session;
    const sock = sockets[session];

    if (!sock) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });

    try {
        const chats = chatMap[session] || {};

        // Fallback: synthesize chat list from messageMap keys when chatMap is empty.
        if (Object.keys(chats).length === 0) {
            const msgKeys = Object.keys(messageMap[session] || {});
            for (const jid of msgKeys) {
                if (!chats[jid]) {
                    const msgs = messageMap[session][jid] || [];
                    const last = msgs[msgs.length - 1];
                    chats[jid] = {
                        id: jid,
                        name: jid.split("@")[0],
                        conversationTimestamp: normalizeTimestamp(last?.messageTimestamp),
                        unreadCount: 0,
                    };
                }
            }
        }
        const result = Object.values(chats).map((chat) => ({
            id: chat.id,
            name: chat.name || chat.id.split("@")[0],
            isGroup: chat.id.endsWith("@g.us"),
            unreadCount: chat.unreadCount || 0,
            timestamp: normalizeTimestamp(chat.conversationTimestamp),
            lastMessage: null,
        }));

        // Attach last message from messageMap
        const msgs = messageMap[session] || {};
        for (const item of result) {
            const chatMsgs = msgs[item.id];
            if (chatMsgs && chatMsgs.length > 0) {
                const last = chatMsgs[chatMsgs.length - 1];
                item.lastMessage = {
                    body:
                        extractMessageBody(last.message) ||
                        (detectMediaType(last.message) ? `[${detectMediaType(last.message)}]` : "[system]"),
                    timestamp: normalizeTimestamp(last.messageTimestamp),
                    fromMe: last.key?.fromMe || false,
                };
            }
        }

        // Sort by timestamp descending
        result.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

        res.json(result);
    } catch (err) {
        log("error", `CHATS ERROR [${session}] => ${err.message}`);
        res.status(500).json({ error: err.message });
    }
});

// ==================================================
//              CHAT HISTORY
// ==================================================
router.get("/:session/history", async (req, res) => {
    const session = req.params.session;
    const sock = sockets[session];
    const chatId = req.query.chatId;

    if (!sock) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });
    if (!chatId) return res.status(400).json({ error: "chatId required" });

    try {
        const normalizedChatId = normalizeJid(chatId);
        const msgs = messageMap[session] || {};
        let chatMsgs = msgs[normalizedChatId] || [];

        // Try on-demand fetch when cache is empty.
        if (chatMsgs.length === 0) {
            await requestHistorySync(session, normalizedChatId, 40);
            chatMsgs = (messageMap[session] || {})[normalizedChatId] || [];
        }

        const deduped = [];
        const seen = new Map();
        for (const msg of chatMsgs) {
            const id = msg?.key?.id;
            if (!id) {
                deduped.push(msg);
                continue;
            }

            const prevIndex = seen.get(id);
            if (prevIndex === undefined) {
                seen.set(id, deduped.length);
                deduped.push(msg);
                continue;
            }

            const prev = deduped[prevIndex];
            const prevScore = (extractMessageBody(prev?.message) ? 1 : 0) + (detectMediaType(prev?.message) ? 2 : 0);
            const curScore = (extractMessageBody(msg?.message) ? 1 : 0) + (detectMediaType(msg?.message) ? 2 : 0);
            if (curScore >= prevScore) {
                deduped[prevIndex] = msg;
            }
        }

        const result = deduped.slice(-50).map((msg) => ({
            id: msg.key?.id || "",
            body: extractMessageBody(msg.message),
            fromMe: msg.key?.fromMe || false,
            timestamp: normalizeTimestamp(msg.messageTimestamp),
            from: msg.key?.remoteJid || "",
            to: msg.key?.fromMe ? msg.key?.remoteJid : "",
            type: msg.message
                ? Object.keys(msg.message)[0] || "unknown"
                : "unknown",
            hasMedia: !!(
                msg.message?.imageMessage ||
                msg.message?.videoMessage ||
                msg.message?.audioMessage ||
                msg.message?.documentMessage
            ),
            mediaType: detectMediaType(msg.message),
            mimeType:
                msg.message?.imageMessage?.mimetype ||
                msg.message?.videoMessage?.mimetype ||
                msg.message?.audioMessage?.mimetype ||
                msg.message?.documentMessage?.mimetype ||
                null,
            fileName: msg.message?.documentMessage?.fileName || null,
            canDownloadMedia: !!(
                msg.message?.imageMessage ||
                msg.message?.videoMessage ||
                msg.message?.audioMessage ||
                msg.message?.documentMessage
            ),
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
    const sock = sockets[session];

    if (!sock) return res.status(404).json({ error: "session not found" });
    if (statuses[session] !== "authenticated")
        return res.status(400).json({ error: "not authenticated" });

    try {
        const groups = await sock.groupFetchAllParticipating();
        const result = Object.values(groups).map((g) => ({
            id: g.id,
            name: g.subject || g.id,
            participantCount: g.participants ? g.participants.length : 0,
        }));

        res.json(result);
    } catch (err) {
        log("error", `GROUPS ERROR [${session}] => ${err.message}`);
        res.status(500).json({ error: err.message });
    }
});

// ==================================================
//              LOGOUT (QR MUNCUL)
// ==================================================
router.post("/:session/logout", async (req, res) => {
    const session = req.params.session;
    log("info", `LOGOUT REQUEST for ${session}`);

    const sock = sockets[session];

    delete sockets[session];
    statuses[session] = "not_authenticated";
    qrCodes[session] = null;
    qrRaw[session] = null;
    delete meta[session];

    if (sock) {
        try {
            await sock.logout();
        } catch (_) {}
        try {
            sock.end();
        } catch (_) {}
    }

    cleanupSessionFolders(session);
    log("info", `LOGOUT SUCCESS for ${session}`);

    reconnectTimers[session] = setTimeout(() => createClient(session), 1500);
    return res.json({ status: "logged_out", session });
});

// ==================================================
//              RESTART SESSION
// ==================================================
router.post("/:session/restart", async (req, res) => {
    const session = req.params.session;
    log("info", `RESTART REQUEST for ${session}`);

    if (sockets[session]) {
        try {
            sockets[session].end();
        } catch (_) {}
        delete sockets[session];
    }

    await createClient(session);
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

    if (sockets[session]) {
        try {
            await sockets[session].logout();
        } catch (_) {}
        try {
            sockets[session].end();
        } catch (_) {}
        delete sockets[session];
    }

    delete statuses[session];
    delete qrCodes[session];
    delete qrRaw[session];
    delete meta[session];
    delete chatMap[session];
    delete messageMap[session];
    delete authFailCounts[session];

    sessionList = sessionList.filter((s) => s !== session);
    saveSessionList();

    cleanupSessionFolders(session);
    res.json({ status: "deleted", session });
});

// ==================================================
//           GRACEFUL SHUTDOWN
// ==================================================
async function gracefulShutdown(signal) {
    log("info", `Received ${signal}, shutting down gracefully...`);
    saveRuntimeCacheNow();

    Object.keys(reconnectTimers).forEach((k) => {
        clearTimeout(reconnectTimers[k]);
        delete reconnectTimers[k];
    });

    const closePromises = Object.keys(sockets).map(async (s) => {
        try {
            log("info", `Closing socket: ${s}`);
            sockets[s].end();
        } catch (_) {}
    });

    await Promise.allSettled(closePromises);
    log("info", "All sockets closed. Exiting.");
    process.exit(0);
}

process.on("SIGTERM", () => gracefulShutdown("SIGTERM"));
process.on("SIGINT", () => gracefulShutdown("SIGINT"));

process.on("uncaughtException", (err) => {
    log("error", `UNCAUGHT EXCEPTION: ${err.message}`);
    log("error", err.stack);
});

process.on("unhandledRejection", (reason) => {
    log("error", `UNHANDLED REJECTION: ${reason}`);
});

// ==================================================
app.listen(PORT, "0.0.0.0", () =>
    log("info", `WA Gateway (Baileys) running on port ${PORT}`)
);
