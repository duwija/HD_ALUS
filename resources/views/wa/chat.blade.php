@extends('layout.main')
@section('title', 'WhatsApp Chat')

@section('content')
<style>
  body { background: #ece5dd; }
  .wa-wrapper {
    display: flex;
    height: calc(100vh - 80px);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 12px rgba(0,0,0,0.15);
  }
  .chat-sidebar {
    width: 30%;
    background: #fff;
    border-right: 1px solid #ccc;
    display: flex;
    flex-direction: column;
  }
  .chat-search { padding: 10px; border-bottom: 1px solid #ddd; }
  .chat-list { flex: 1; overflow-y: auto; }
  .chat-item {
    padding: 10px 15px;
    border-bottom: 1px solid #f2f2f2;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .chat-item:hover { background: #f5f5f5; }
  .chat-item.active { background: #d9fdd3; }
  .chat-item .name { font-weight: 600; flex: 1; }
  .chat-item .unread {
    background: #25d366;
    color: #fff;
    font-size: 12px;
    border-radius: 10px;
    padding: 2px 6px;
    margin-left: 6px;
  }
  .chat-main { flex: 1; display: flex; flex-direction: column; background: var(--chat-bg); }
  .chat-header {
    background: #ededed;
    padding: 10px 15px;
    border-bottom: 1px solid #ccc;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .chat-messages { flex: 1; padding: 15px; overflow-y: auto; background: var(--chat-bg); }
  .chat-input {
    padding: 10px;
    border-top: 1px solid #ccc;
    background: #f0f0f0;
    display: flex;
    align-items: center;
  }
  .chat-input textarea { flex: 1; resize: none; }
  .message { margin-bottom: 10px; display: flex; }
  .message.outgoing { justify-content: flex-end; }
  .message.incoming { justify-content: flex-start; }
  .message-bubble {
    max-width: 70%;
    padding: 8px 12px;
    border-radius: 8px;
    word-wrap: break-word;
    white-space: pre-wrap;
  }
  .message.outgoing .message-bubble { background: #dcf8c6; }
  .message.incoming .message-bubble { background: #fff; }
  .message-time { font-size: 0.75rem; color: #777; margin-top: 4px; text-align: right; }
  .spinner { text-align: center; color: #999; padding: 20px; }
  :root { --chat-bg: #e5ddd5; }
  mark { background: #fff176; padding: 0 2px; border-radius: 3px; }
</style>

<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h4 class="m-0">💬 WhatsApp Chat Gateway</h4>
    <select id="sessionSelect" class="form-control form-control-sm" style="width:180px;">
      <option value="">Pilih Session</option>
    </select>
  </div>
</section>

<section class="content">
  <div class="wa-wrapper">
    <!-- SIDEBAR -->
    <div class="chat-sidebar">
      <div class="chat-search">
        <input id="searchBox" type="text" class="form-control form-control-sm" placeholder="Cari nama, nomor, atau grup...">
      </div>
      <div id="chatList" class="chat-list">
        <div class="spinner"><i class="fas fa-spinner fa-spin"></i> Memuat chat...</div>
      </div>
    </div>

    <!-- MAIN CHAT -->
    <div class="chat-main">
      <div class="chat-header">
        <div>
          <strong id="chatName">Pilih chat</strong><br>
          <small id="chatId" class="text-muted"></small>
        </div>
        <button class="btn btn-sm btn-outline-secondary" onclick="toggleTheme()">
          <i class="fas fa-adjust"></i>
        </button>
      </div>

      <div id="messagesArea" class="chat-messages">
        <div class="spinner"><i class="fas fa-comments"></i> Pilih chat dari kiri...</div>
      </div>

      <div class="chat-input">
        <form id="sendMessageForm" class="w-100 d-flex align-items-center" onsubmit="return false;">
          <textarea id="messageInput" class="form-control mr-2" rows="1" placeholder="Ketik pesan... (Enter = Kirim, Shift+Enter = Baris baru)"></textarea>
          <button type="button" id="sendButton" class="btn btn-success">
            <i class="fas fa-paper-plane"></i>
          </button>
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

// === INIT ===
  document.addEventListener('DOMContentLoaded', () => {
    loadSessions();
    document.getElementById('sendButton').addEventListener('click', () => sendMessage());

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
      const sessions = data.sessions || [];
      const sel = document.getElementById('sessionSelect');
      sel.innerHTML = '<option value="">Pilih Session</option>';
      for (const s of sessions)
        sel.innerHTML += `<option value="${s}" ${s===currentSession?'selected':''}>${s}</option>`;
      sel.addEventListener('change', e => {
        currentSession = e.target.value;
        localStorage.setItem('wa_active_session', currentSession);
        loadChats(currentSession);
      });
    } catch(e){ console.error(e); }
  }

// === CHAT LIST ===
  async function loadChats(session, silent=false) {
    const list = document.getElementById('chatList');
    if (!silent) list.innerHTML = `<div class="spinner"><i class="fas fa-spinner fa-spin"></i> Memuat chat...</div>`;
    try {
      const res = await fetch(`/wa/${session}/chats`);
      if (!res.ok) throw new Error(res.status);
      const chats = await res.json();
      chatCache = chats;
      renderChatList(chats);
    } catch(e){
      list.innerHTML = `<div class="text-danger p-3">Gagal memuat chat (${e.message})</div>`;
    }
  }

  function renderChatList(chats) {
    const list = document.getElementById('chatList');
    if (!chats.length) { list.innerHTML = '<div class="text-muted p-3">Tidak ada chat</div>'; return; }
    chats.sort((a,b)=>(b.unreadCount||0)-(a.unreadCount||0));
    list.innerHTML = chats.map(c=>`
      <div class="chat-item ${c.id===currentChatId?'active':''}" 
      onclick="selectChat('${c.id}', '${escapeHtml(c.name || 'Tanpa Nama')}')">
      <div class="name">${escapeHtml(c.name || c.id)}</div>
      ${c.unreadCount>0?`<span class="unread">${c.unreadCount}</span>`:''}
      </div>`).join('');
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
      <div class="chat-item" onclick="selectChat('${c.id}', '${escapeHtml(c.name || 'Tanpa Nama')}')">
      <div class="name">${highlight(escapeHtml(c.name || c.id))}</div>
      ${c.unreadCount>0?`<span class="unread">${c.unreadCount}</span>`:''}
      </div>`).join('');
  }

// === CHAT OPEN ===
  function selectChat(id, name){
    currentChatId = id;
    localStorage.setItem('wa_active_chat', id);
    document.getElementById('chatName').textContent = name;
    document.getElementById('chatId').textContent = id;
    document.querySelectorAll('.chat-item').forEach(e=>e.classList.remove('active'));
    const active = [...document.querySelectorAll('.chat-item')].find(e=>e.textContent.includes(name));
    if(active) active.classList.add('active');
    loadMessages(currentSession, id);
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
      messages.sort((a,b)=>(a.timestamp||0)-(b.timestamp||0));
      if (!messages.length){
        area.innerHTML = '<div class="text-center text-muted p-3">Tidak ada pesan</div>';
        return;
      }
      area.innerHTML = messages.map(m=>`
        <div class="message ${m.fromMe?'outgoing':'incoming'}">
        <div class="message-bubble">
        ${escapeHtml(m.body||'')}
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
    if (!msg || !currentSession || !currentChatId) return;
    addMessage(msg,true);
    msgInput.value = '';
    try {
      await fetch(`/wa/${currentSession}/send`,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
        body:JSON.stringify({number:currentChatId,message:msg})
      });
    } catch(e){ toast('Gagal kirim pesan','error'); }
  }

// === HELPERS ===
  function addMessage(msg,fromMe){
    const area=document.getElementById('messagesArea');
    area.innerHTML += `
    <div class="message ${fromMe?'outgoing':'incoming'}">
    <div class="message-bubble">
    <div>${escapeHtml(msg)}</div>
    <div class="message-time">${formatTime(Date.now()/1000)}</div>
    </div>
    </div>`;
    area.scrollTop=area.scrollHeight;
  }
  function escapeHtml(t){const d=document.createElement('div');d.textContent=t;return d.innerHTML;}
  function formatTime(ts){const d=new Date(ts*1000);return d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});}
  function toggleTheme(){document.body.classList.toggle('dark-mode');}
  function toast(msg,icon){Swal.fire({toast:true,position:'top-end',icon,title:msg,showConfirmButton:false,timer:2000});}
</script>
@endsection
