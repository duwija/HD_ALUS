@extends('layout.main')
@section('title', 'WhatsApp Chat')

@section('content')
<style>
  :root {
    --wa-bg: linear-gradient(180deg, #d7e7de 0%, #e9f0eb 28%, #f3efe8 100%);
    --wa-panel: rgba(255, 255, 255, 0.92);
    --wa-panel-strong: #ffffff;
    --wa-border: rgba(17, 27, 33, 0.08);
    --wa-shadow: 0 24px 60px rgba(17, 27, 33, 0.12);
    --wa-green: #25d366;
    --wa-green-dark: #128c7e;
    --wa-text: #1f2c34;
    --wa-soft: #667781;
    --wa-chat-bg: #efeae2;
    --wa-message-out: #d9fdd3;
    --wa-message-in: #ffffff;
    --wa-active: #e7fce8;
    --wa-muted: #8696a0;
  }
  body {
    background: var(--wa-bg);
    color: var(--wa-text);
  }
  .wa-wrapper {
    display: flex;
    height: calc(100vh - 120px);
    min-height: 640px;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: var(--wa-shadow);
    border: 1px solid rgba(255,255,255,0.55);
    backdrop-filter: blur(10px);
  }
  .chat-sidebar {
    width: 32%;
    min-width: 320px;
    max-width: 420px;
    background: var(--wa-panel);
    border-right: 1px solid var(--wa-border);
    display: flex;
    flex-direction: column;
  }
  .sidebar-top {
    padding: 18px 18px 12px;
    border-bottom: 1px solid var(--wa-border);
    background: rgba(255,255,255,0.7);
  }
  .sidebar-top h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
  }
  .sidebar-top p {
    margin: 4px 0 0;
    color: var(--wa-soft);
    font-size: 0.85rem;
  }
  .chat-search {
    padding: 14px 16px;
    border-bottom: 1px solid var(--wa-border);
    background: rgba(255,255,255,0.72);
  }
  .chat-search .input-group-text,
  .chat-search .form-control {
    border: 0;
    background: #f5f7f8;
  }
  .chat-search .form-control:focus {
    box-shadow: none;
    background: #eef3f4;
  }
  .chat-list { flex: 1; overflow-y: auto; }
  .chat-item {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(17, 27, 33, 0.05);
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }
  .chat-item:hover { background: rgba(17, 27, 33, 0.03); }
  .chat-item.active { background: var(--wa-active); }
  .chat-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d7efe1, #bee7cb);
    color: var(--wa-green-dark);
    display: grid;
    place-items: center;
    font-size: 1rem;
    flex: 0 0 44px;
    font-weight: 700;
  }
  .chat-meta {
    flex: 1;
    min-width: 0;
  }
  .chat-topline,
  .chat-subline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .chat-item .name {
    font-weight: 600;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .chat-preview,
  .chat-jid,
  .chat-time-label {
    color: var(--wa-soft);
    font-size: 0.78rem;
  }
  .chat-preview {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 240px;
  }
  .chat-item .unread {
    background: #25d366;
    color: #fff;
    font-size: 12px;
    border-radius: 999px;
    padding: 2px 7px;
    min-width: 22px;
    text-align: center;
  }
  .chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: var(--wa-chat-bg);
    position: relative;
  }
  .chat-header {
    background: rgba(255,255,255,0.72);
    padding: 14px 18px;
    border-bottom: 1px solid var(--wa-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    backdrop-filter: blur(8px);
  }
  .chat-header-main {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }
  .chat-header-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #ecfaf0, #d7efe1);
    color: var(--wa-green-dark);
    font-weight: 700;
  }
  .chat-header-title {
    min-width: 0;
  }
  .chat-header-title strong,
  .chat-header-title small {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .chat-session-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(37, 211, 102, 0.12);
    color: var(--wa-green-dark);
    font-size: 0.78rem;
    font-weight: 600;
  }
  .chat-messages {
    flex: 1;
    padding: 18px;
    overflow-y: auto;
    background:
      radial-gradient(circle at top left, rgba(255,255,255,0.5), transparent 30%),
      radial-gradient(circle at bottom right, rgba(37,211,102,0.08), transparent 22%),
      var(--wa-chat-bg);
  }
  .chat-input {
    margin: 0 12px 10px;
    padding: 8px;
    border: 1px solid var(--wa-border);
    border-radius: 14px;
    background: rgba(255,255,255,0.78);
    backdrop-filter: blur(8px);
  }
  .chat-composer {
    width: 100%;
    display: flex;
    gap: 12px;
    align-items: flex-end;
  }
  .chat-composer textarea {
    flex: 1;
    resize: none;
    border: 0;
    border-radius: 14px;
    padding: 10px 12px;
    background: #fff;
    box-shadow: inset 0 0 0 1px rgba(17, 27, 33, 0.06);
  }
  .chat-composer textarea:focus {
    box-shadow: inset 0 0 0 2px rgba(18, 140, 126, 0.12);
  }
  .composer-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
  }
  .composer-hint {
    color: var(--wa-soft);
    font-size: 0.72rem;
  }
  .message { margin-bottom: 3px; display: flex; }
  .message.outgoing { justify-content: flex-end; }
  .message.incoming { justify-content: flex-start; }
  .message-bubble {
    max-width: min(74%, 560px);
    padding: 4px 7px;
    border-radius: 12px;
    word-wrap: break-word;
    box-shadow: 0 4px 12px rgba(17, 27, 33, 0.05);
  }
  .message.outgoing .message-bubble {
    background: var(--wa-message-out);
    border-bottom-right-radius: 4px;
  }
  .message.incoming .message-bubble {
    background: var(--wa-message-in);
    border-bottom-left-radius: 4px;
  }
  .message-text {
    color: var(--wa-text);
    line-height: 1.16;
    font-size: 0.89rem;
    white-space: pre-wrap;
    word-break: break-word;
  }
  .media-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 7px;
    border-radius: 10px;
    background: rgba(18, 140, 126, 0.10);
    color: #0f5f56;
    font-size: 0.78rem;
    font-weight: 600;
    margin-bottom: 4px;
  }
  .media-caption {
    margin-top: 2px;
  }
  .media-preview {
    margin-bottom: 5px;
  }
  .media-preview img,
  .media-preview video {
    max-width: 190px;
    max-height: 190px;
    border-radius: 10px;
    display: block;
    background: rgba(0,0,0,0.04);
  }
  .media-preview audio {
    width: 240px;
    max-width: 100%;
  }
  .media-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #0f5f56;
    font-size: 0.82rem;
    text-decoration: none;
    border-bottom: 1px dashed rgba(15,95,86,0.35);
  }
  .message-time { font-size: 0.62rem; color: #6a7b83; margin-top: 1px; text-align: right; }
  .spinner, .empty-state {
    text-align: center;
    color: #6d7c84;
    padding: 28px 20px;
  }
  .empty-state i,
  .spinner i {
    font-size: 1.4rem;
    margin-bottom: 8px;
  }
  .toolbar-btn {
    border-radius: 999px;
    padding-inline: 12px;
  }
  mark { background: #fff176; padding: 0 2px; border-radius: 3px; }
  @media (max-width: 992px) {
    .wa-wrapper {
      flex-direction: column;
      height: auto;
      min-height: calc(100vh - 120px);
    }
    .chat-sidebar {
      width: 100%;
      max-width: none;
      min-width: 0;
      max-height: 42vh;
    }
    .chat-main {
      min-height: 58vh;
    }
    .message-bubble {
      max-width: 92%;
    }
  }
</style>

<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div>
      <h4 class="m-0">WhatsApp Chat Gateway</h4>
    </div>
    <select id="sessionSelect" class="form-control form-control-sm" style="width:180px;">
      <option value="">Pilih Session</option>
    </select>
  </div>
</section>

<section class="content">
  <div class="wa-wrapper">
    <!-- SIDEBAR -->
    <div class="chat-sidebar">
      <div class="sidebar-top">
        <h5>Daftar Chat</h5>
        <p id="chatSummary">Pilih session untuk memuat percakapan terbaru.</p>
      </div>
      <div class="chat-search">
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
          </div>
          <input id="searchBox" type="text" class="form-control" placeholder="Cari nama, nomor, atau grup...">
        </div>
      </div>
      <div id="chatList" class="chat-list">
        <div class="spinner"><i class="fas fa-spinner fa-spin"></i> Memuat chat...</div>
      </div>
    </div>

    <!-- MAIN CHAT -->
    <div class="chat-main">
      <div class="chat-header">
        <div class="chat-header-main">
          <div class="chat-header-avatar" id="chatAvatar">WA</div>
          <div class="chat-header-title">
            <strong id="chatName">Pilih chat</strong>
            <small id="chatId" class="text-muted">Belum ada percakapan yang dipilih.</small>
          </div>
        </div>
        <div class="d-flex align-items-center" style="gap:8px;">
          <span id="sessionBadge" class="chat-session-badge"><i class="fas fa-plug"></i> Session belum dipilih</span>
          <button class="btn btn-sm btn-outline-secondary toolbar-btn" onclick="loadChats(currentSession)">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>

      <div id="messagesArea" class="chat-messages">
        <div class="empty-state"><i class="fas fa-comments"></i><div>Pilih chat dari panel kiri untuk melihat percakapan.</div></div>
      </div>

      <div class="chat-input">
        <form id="sendMessageForm" class="chat-composer" onsubmit="return false;">
          <input type="file" id="mediaInput" hidden>
          <textarea id="messageInput" rows="1" placeholder="Ketik pesan... Enter untuk kirim, Shift+Enter untuk baris baru"></textarea>
          <div class="composer-actions">
            <div class="d-flex" style="gap:8px;">
              <button type="button" id="attachButton" class="btn btn-outline-secondary toolbar-btn">
                <i class="fas fa-paperclip"></i>
              </button>
            <button type="button" id="sendButton" class="btn btn-success toolbar-btn">
              <i class="fas fa-paper-plane"></i> Kirim
            </button>
            </div>
            <div class="composer-hint">Dikirim lewat session aktif di gateway.</div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection

@section('footer-scripts')
<script>
  const csrfToken = '{{ csrf_token() }}';
  let currentSession = localStorage.getItem('wa_active_session') || null;
  let currentChatId = localStorage.getItem('wa_active_chat') || null;
  let chatCache = [];
  let isSending = false;

// === INIT ===
  document.addEventListener('DOMContentLoaded', () => {
    loadSessions();
    document.getElementById('sendButton').addEventListener('click', () => sendMessage());
    document.getElementById('attachButton').addEventListener('click', () => document.getElementById('mediaInput').click());
    document.getElementById('mediaInput').addEventListener('change', handleMediaSelected);
    document.getElementById('chatList').addEventListener('click', handleChatListClick);

  // Enter = kirim, Shift+Enter = newline
    const msgInput = document.getElementById('messageInput');
    msgInput.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

  // Search contact / chat realtime
    document.getElementById('searchBox').addEventListener('input', handleSearch);

    if (currentSession) document.getElementById('sessionSelect').value = currentSession;
    if (currentSession) loadChats(currentSession);
    refreshSessionBadge();

  // Auto refresh 10 detik
    setInterval(() => {
      if (currentSession) loadChats(currentSession, true);
      if (currentSession && currentChatId) loadMessages(currentSession, currentChatId, true);
    }, 10000);
  });

// === SESSION ===
  async function loadSessions() {
    try {
      const res = await fetch('/wa/status');
      const data = await res.json();
      let sessions = data.sessions || [];
      // Handle object format from new gateway
      if (!Array.isArray(sessions)) sessions = Object.keys(sessions);
      const sel = document.getElementById('sessionSelect');
      sel.innerHTML = '<option value="">Pilih Session</option>';
      for (const s of sessions)
        sel.innerHTML += `<option value="${s}" ${s===currentSession?'selected':''}>${s}</option>`;
      sel.addEventListener('change', e => {
        currentSession = e.target.value;
        localStorage.setItem('wa_active_session', currentSession);
        currentChatId = null;
        localStorage.removeItem('wa_active_chat');
        refreshSessionBadge();
        resetChatPanel();
        if (currentSession) loadChats(currentSession);
      });

      // Auto-select from URL param if available
      const urlSession = '{{ $session ?? '' }}';
      if (urlSession && !currentSession) {
        currentSession = urlSession;
        localStorage.setItem('wa_active_session', currentSession);
        sel.value = currentSession;
        refreshSessionBadge();
      }
    } catch(e){ console.error('loadSessions error:', e); }
  }

// === CHAT LIST ===
  async function loadChats(session, silent=false) {
    const list = document.getElementById('chatList');
    if (!session) {
      chatCache = [];
      updateChatSummary(0);
      list.innerHTML = '<div class="empty-state"><i class="fas fa-plug"></i><div>Pilih session terlebih dahulu.</div></div>';
      return;
    }
    if (!silent) list.innerHTML = `<div class="spinner"><i class="fas fa-spinner fa-spin"></i> Memuat chat...</div>`;
    try {
      const res = await fetch(`/wa/${session}/chats`);
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        if (res.status === 503 || res.status === 500) {
          if (!silent) list.innerHTML = `<div class="text-warning p-3"><i class="fas fa-hourglass-half"></i> WhatsApp sedang loading, coba lagi dalam beberapa detik...</div>`;
          return;
        }
        throw new Error(err.error || res.status);
      }
      const chats = await res.json();
      if (chats.error) {
        if (!silent) list.innerHTML = `<div class="text-warning p-3"><i class="fas fa-hourglass-half"></i> ${chats.error}</div>`;
        return;
      }
      chatCache = chats;
      updateChatSummary(chats.length);
      renderChatList(chats);
    } catch(e){
      if (!silent) list.innerHTML = `<div class="text-danger p-3"><i class="fas fa-exclamation-triangle"></i> Gagal memuat chat (${e.message})</div>`;
    }
  }

  function renderChatList(chats) {
    const list = document.getElementById('chatList');
    if (!chats.length) {
      list.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><div>Tidak ada chat yang bisa ditampilkan.</div></div>';
      return;
    }
    chats.sort((a,b)=>((b.unreadCount||0)-(a.unreadCount||0)) || ((b.timestamp||0)-(a.timestamp||0)));
    list.innerHTML = chats.map(c=>`
      <div class="chat-item ${c.id===currentChatId?'active':''}" data-chat-id="${escapeHtml(c.id)}">
        <div class="chat-avatar">${escapeHtml(getAvatarText(c.name || c.id))}</div>
        <div class="chat-meta">
          <div class="chat-topline">
            <div class="name">${escapeHtml(c.name || c.id)}</div>
            <div class="chat-time-label">${formatListTime(c.timestamp)}</div>
          </div>
          <div class="chat-subline">
            <div class="chat-preview">${escapeHtml(getPreviewText(c))}</div>
            ${c.unreadCount>0?`<span class="unread">${c.unreadCount}</span>`:''}
          </div>
          <div class="chat-jid">${escapeHtml(formatChatId(c.id))}</div>
        </div>
      </div>`).join('');

    if (currentChatId && chats.some(c => c.id === currentChatId)) {
      const current = chats.find(c => c.id === currentChatId);
      syncSelectedChatMeta(current);
    }
  }

// === SEARCH CONTACT ===
  function handleSearch(e){
    const keyword = e.target.value.trim().toLowerCase();
    if (!keyword) return renderChatList(chatCache);
    
    const filtered = chatCache.filter(c=>{
      const name=(c.name||'').toLowerCase();
      const id=(c.id||'').toLowerCase();
      const shortId=id.replace('@c.us','').replace('@g.us','');
      return name.includes(keyword)||id.includes(keyword)||shortId.includes(keyword);
    });

    if (!filtered.length){
      document.getElementById('chatList').innerHTML = '<div class="text-muted p-3">Tidak ditemukan hasil</div>';
      return;
    }

    const highlight = (text) => {
      const pattern = new RegExp(`(${keyword})`, 'gi');
      return text.replace(pattern, '<mark>$1</mark>');
    };

    document.getElementById('chatList').innerHTML = filtered.map(c=>`
      <div class="chat-item ${c.id===currentChatId?'active':''}" data-chat-id="${escapeHtml(c.id)}">
        <div class="chat-avatar">${escapeHtml(getAvatarText(c.name || c.id))}</div>
        <div class="chat-meta">
          <div class="chat-topline">
            <div class="name">${highlight(escapeHtml(c.name || c.id))}</div>
            <div class="chat-time-label">${formatListTime(c.timestamp)}</div>
          </div>
          <div class="chat-subline">
            <div class="chat-preview">${highlight(escapeHtml(getPreviewText(c)))}</div>
            ${c.unreadCount>0?`<span class="unread">${c.unreadCount}</span>`:''}
          </div>
          <div class="chat-jid">${escapeHtml(formatChatId(c.id))}</div>
        </div>
      </div>`).join('');
  }

// === CHAT OPEN ===
  function handleChatListClick(event) {
    const item = event.target.closest('.chat-item');
    if (!item) return;
    const chat = chatCache.find(entry => entry.id === item.dataset.chatId);
    if (!chat) return;
    selectChat(chat);
  }

  function selectChat(chat){
    const id = chat.id;
    currentChatId = id;
    localStorage.setItem('wa_active_chat', id);
    syncSelectedChatMeta(chat);
    document.querySelectorAll('.chat-item').forEach(e=>e.classList.toggle('active', e.dataset.chatId === id));
    loadMessages(currentSession, id);
    markChatRead(currentSession, id);
  }

// === MESSAGES ===
  async function loadMessages(session, chatId, silent=false){
    const area = document.getElementById('messagesArea');
    if (!silent)
      area.innerHTML = `<div class="spinner"><i class="fas fa-spinner fa-spin"></i> Memuat pesan...</div>`;
    try {
      const res = await fetch(`/wa/${session}/history?chatId=${encodeURIComponent(chatId)}`);
      if(!res.ok) throw new Error(res.status);
      const data = await res.json();
      let messages = Array.isArray(data[0]?.messages) ? data[0].messages : data;
      const map = new Map();
      for (const msg of (Array.isArray(messages) ? messages : [])) {
        const key = msg.id || `${msg.fromMe ? 'out' : 'in'}-${parseTimestamp(msg.timestamp)}-${(msg.body || '').trim()}-${msg.type || ''}`;
        map.set(key, msg);
      }
      messages = Array.from(map.values());
      messages.sort((a,b)=>(a.timestamp||0)-(b.timestamp||0));
      if (!messages.length){
        area.innerHTML = '<div class="empty-state"><i class="fas fa-comment-slash"></i><div>Tidak ada pesan pada percakapan ini.</div></div>';
        return;
      }
      area.innerHTML = messages.map(m=>`
        <div class="message ${m.fromMe?'outgoing':'incoming'}">
        <div class="message-bubble">
        <div class="message-text">${renderMessageContent(m)}</div>
        <div class="message-time">${formatTime(m.timestamp)}</div>
        </div>
        </div>`).join('');
      area.scrollTop = area.scrollHeight;
    }catch(e){
      if (!silent) area.innerHTML = `<div class="text-danger p-3">Gagal memuat pesan (${e.message})</div>`;
    }
  }

// === SEND ===
  async function sendMessage(){
    const msgInput = document.getElementById('messageInput');
    const msg = msgInput.value.trim();
    if (!msg || !currentSession || !currentChatId || isSending) return;
    isSending = true;
    toggleComposerState(true);
    msgInput.value = '';
    try {
      const response = await fetch(`/wa/${currentSession}/send`,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
        body:JSON.stringify({number:currentChatId,message:msg})
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.error) {
        throw new Error(payload.message || payload.error || 'Gagal mengirim pesan');
      }
      loadChats(currentSession, true);
      setTimeout(() => loadMessages(currentSession, currentChatId, true), 1200);
    } catch(e){
      toast(e.message || 'Gagal kirim pesan','error');
      loadMessages(currentSession, currentChatId, true);
    } finally {
      isSending = false;
      toggleComposerState(false);
    }
  }

  async function handleMediaSelected(event) {
    const file = event.target.files?.[0];
    if (!file || !currentSession || !currentChatId || isSending) {
      event.target.value = '';
      return;
    }

    isSending = true;
    toggleComposerState(true);

    try {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('number', currentChatId);
      formData.append('caption', document.getElementById('messageInput').value.trim());

      const response = await fetch(`/wa/${currentSession}/send-media`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData,
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.error) {
        throw new Error(payload.message || payload.error || 'Gagal mengirim file');
      }

      document.getElementById('messageInput').value = '';
      toast('File berhasil dikirim', 'success');
      loadChats(currentSession, true);
      setTimeout(() => loadMessages(currentSession, currentChatId, true), 1200);
    } catch (error) {
      toast(error.message || 'Gagal mengirim file', 'error');
    } finally {
      event.target.value = '';
      isSending = false;
      toggleComposerState(false);
    }
  }

  async function markChatRead(session, chatId) {
    if (!session || !chatId) return;
    try {
      await fetch(`/wa/${session}/read`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ chatId }),
      });
    } catch (error) {
      console.debug('markChatRead error:', error);
    }
  }

// === HELPERS ===
  function addMessage(msg,fromMe){
    const area=document.getElementById('messagesArea');
    if (area.querySelector('.empty-state, .spinner')) area.innerHTML = '';
    area.innerHTML += `
    <div class="message ${fromMe?'outgoing':'incoming'}">
    <div class="message-bubble">
    <div class="message-text">${escapeHtml(msg)}</div>
    <div class="message-time">${formatTime(Date.now()/1000)}</div>
    </div>
    </div>`;
    area.scrollTop=area.scrollHeight;
  }
  function syncSelectedChatMeta(chat) {
    document.getElementById('chatName').textContent = chat.name || 'Tanpa Nama';
    document.getElementById('chatId').textContent = `${formatChatId(chat.id)}${chat.isGroup ? ' • Grup' : ' • Personal'}`;
    document.getElementById('chatAvatar').textContent = getAvatarText(chat.name || chat.id);
  }
  function refreshSessionBadge() {
    const badge = document.getElementById('sessionBadge');
    badge.innerHTML = currentSession
      ? `<i class="fas fa-circle"></i> Session ${escapeHtml(currentSession)} aktif`
      : '<i class="fas fa-plug"></i> Session belum dipilih';
  }
  function resetChatPanel() {
    document.getElementById('chatName').textContent = 'Pilih chat';
    document.getElementById('chatId').textContent = 'Belum ada percakapan yang dipilih.';
    document.getElementById('chatAvatar').textContent = 'WA';
    document.getElementById('messagesArea').innerHTML = '<div class="empty-state"><i class="fas fa-comments"></i><div>Pilih chat dari panel kiri untuk melihat percakapan.</div></div>';
  }
  function updateChatSummary(total) {
    const summary = document.getElementById('chatSummary');
    summary.textContent = currentSession
      ? `${total} chat termuat untuk session ${currentSession}.`
      : 'Pilih session untuk memuat percakapan terbaru.';
  }
  function toggleComposerState(disabled) {
    document.getElementById('sendButton').disabled = disabled;
    document.getElementById('attachButton').disabled = disabled;
    document.getElementById('messageInput').disabled = disabled;
  }
  function escapeHtml(t){const d=document.createElement('div');d.textContent=t;return d.innerHTML;}
  function getAvatarText(text){return (text || 'WA').replace(/[^A-Za-z0-9]/g,'').slice(0,2).toUpperCase() || 'WA';}
  function formatChatId(chatId){return (chatId || '').replace('@c.us','').replace('@s.whatsapp.net','').replace('@g.us','');}
  function getPreviewText(chat){return chat.lastMessage?.body || (chat.isGroup ? 'Grup WhatsApp' : 'Percakapan WhatsApp');}
  function parseTimestamp(ts){
    if (!ts) return 0;
    if (typeof ts === 'number') return ts;
    if (typeof ts === 'string') {
      const n = Number(ts);
      return Number.isFinite(n) ? n : 0;
    }
    if (typeof ts === 'object') {
      if (typeof ts.low === 'number') return ts.low;
      if (typeof ts.seconds === 'number') return ts.seconds;
      if (typeof ts._seconds === 'number') return ts._seconds;
    }
    return 0;
  }
  function formatTime(ts){
    const unix = parseTimestamp(ts);
    if (!unix) return '-';
    const d=new Date(unix*1000);
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
  }
  function formatListTime(ts){
    const unix = parseTimestamp(ts);
    if (!unix) return '';
    const date = new Date(unix*1000);
    if (Number.isNaN(date.getTime())) return '';
    const now = new Date();
    const sameDay = date.toDateString() === now.toDateString();
    return sameDay
      ? date.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})
      : date.toLocaleDateString('id-ID',{day:'2-digit',month:'short'});
  }
  function mediaLabel(type){
    switch(type){
      case 'image': return 'Gambar';
      case 'video': return 'Video';
      case 'audio': return 'Audio';
      case 'document': return 'Dokumen';
      case 'sticker': return 'Sticker';
      default: return 'Media';
    }
  }
  function mediaIcon(type){
    switch(type){
      case 'image': return 'fa-image';
      case 'video': return 'fa-video';
      case 'audio': return 'fa-music';
      case 'document': return 'fa-file-alt';
      case 'sticker': return 'fa-smile';
      default: return 'fa-paperclip';
    }
  }
  function renderMessageContent(message){
    const normalizedBody = String(message.body || '')
      .replace(/\r\n/g, '\n')
      .replace(/\n{3,}/g, '\n\n')
      .trim();
    const text = escapeHtml(normalizedBody);
    if (!message.hasMedia) {
      return text || '[Pesan sistem]';
    }

    const type = message.mediaType || 'media';
    const label = mediaLabel(type);
    const icon = mediaIcon(type);
    const fileName = message.fileName ? `<div class="media-caption"><small>${escapeHtml(message.fileName)}</small></div>` : '';
    const caption = text ? `<div class="media-caption">${text}</div>` : '';
    const mediaUrl = (message.canDownloadMedia && message.id)
      ? `/wa/${encodeURIComponent(currentSession)}/media?chatId=${encodeURIComponent(currentChatId)}&messageId=${encodeURIComponent(message.id)}`
      : null;

    let preview = '';
    if (mediaUrl) {
      if (type === 'image') {
        preview = `<div class="media-preview"><img src="${mediaUrl}" alt="image" loading="lazy" onerror="handleMediaError(this,'Gambar belum tersedia','fa-image')"></div>`;
      } else if (type === 'video') {
        preview = `<div class="media-preview"><video controls preload="metadata" src="${mediaUrl}" onerror="handleMediaError(this,'Video belum tersedia','fa-video')"></video></div>`;
      } else if (type === 'audio') {
        preview = `<div class="media-preview"><audio controls preload="none" src="${mediaUrl}" onerror="handleMediaError(this,'Audio belum tersedia','fa-music')"></audio></div>`;
      } else {
        const docName = escapeHtml(message.fileName || 'Unduh dokumen');
        preview = `<div class="media-preview"><a class="media-link" href="${mediaUrl}" target="_blank" rel="noopener"><i class="fas fa-download"></i> ${docName}</a></div>`;
      }
    }

    return `${preview}<div class="media-chip"><i class="fas ${icon}"></i> ${label}</div>${fileName}${caption}`;
  }
  function handleMediaError(el, text, icon){
    const parent = el?.parentElement;
    if (!parent) return;
    parent.innerHTML = `<div class="media-chip"><i class="fas ${icon}"></i> ${escapeHtml(text)}</div>`;
  }
  function toast(msg,icon){Swal.fire({toast:true,position:'top-end',icon,title:msg,showConfirmButton:false,timer:2000});}
</script>
@endsection
