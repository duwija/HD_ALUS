@extends('layout.main')

@section('content')
<div class="row">
  <div class="col-md-4">
    <div class="card card-warning card-outline">
      <div class="card-header">
        <h3 class="card-title">Chats</h3>
      </div>
      <div class="card-body p-0" style="height: calc(100vh - 56px); overflow-y: auto;">
        <div class="card-body p-0" style="height: calc(100vh - 56px); overflow-y: auto;">
          <input type="text" class="form-control mb-2" id="chatSearchInput" placeholder="Cari kontak/group...">
          <ul id="chatList" class="list-group list-group-flush">
            <!-- Chat items go here -->
          </ul>
        </div>
        <ul id="chatList" class="list-group list-group-flush">
          <!-- Chat items go here -->
        </ul>
      </div>
    </div>
  </div>

  <div class="col-md-8 d-flex flex-column">
    <div class="card direct-chat direct-chat-primary flex-grow-1">
      <div class="card-header">
        <h3 class="card-title" id="chatTitle">Select a chat</h3>
      </div>
      <div class="card-body p-0 d-flex flex-column">
        <div id="messagesContainer" class="direct-chat-messages flex-grow-1 p-3" style="overflow-y:auto;">
          <!-- Messages go here -->
        </div>
      </div>
      <div class="card-footer" id="chatInput">
        <form id="sendForm" class="input-group">
         <textarea id="messageInput" class="form-control me-2" rows="2" placeholder="Type your message... (Shift+Enter untuk newline, Enter untuk kirim)" autocomplete="off"></textarea>

         <span class="input-group-append">
          <button type="submit" class="btn btn-primary">Send</button>
        </span>
      </form>
    </div>
  </div>
</div>
</div>
@endsection

@section('footer-scripts')
<script>
  $(function() {
    const session = "{{ request()->query('session','') }}";
    if (!session) {
      alert('Session parameter is required');
      return;
    }

    let currentChat = null;

    // function loadChatList() {
    //   fetch(`{{ route("wa.chatTable") }}?session=${session}`)
    //   .then(res => res.json())
    //   .then(data => {
    //     const list = $('#chatList').empty();
    //     data.data.forEach(chat => {
    //       const item = $('<li>', { class: 'list-group-item list-group-item-action', text: chat.name });
    //       const badge = $('<span>', { class: 'badge badge-primary float-right', text: chat.unreadCount });
    //       item.append(badge)
    //       .on('click', () => selectChat(chat.chatId, chat.name))
    //       .appendTo(list);
    //     });
    //   });
    // }


    $('#messageInput').on('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        $('#sendForm').submit();
      }
    });

    let chatListData = [];

    function loadChatList() {
      const ul = $('#chatList');
      ul.html('<li class="list-group-item text-center text-muted">Loading…</li>');

      fetch(`/wa/${session}/chats`)
      .then(r => r.json())
      .then(list => {
      chatListData = list; // <-- simpan data aslinya
      renderChatList(list);
    })
      .catch(err => {
        console.error('Load chats error', err);
        ul.html('<li class="list-group-item text-center text-danger">Failed to load chats</li>');
      });
    }



// Render chat list (bisa dipanggil berulang)
    function renderChatList(list) {
      const ul = $('#chatList');
      ul.empty();
      if (!list.length) {
        return ul.html('<li class="list-group-item text-center text-muted">No chats</li>');
      }
      list.forEach(c => {
        const item = $(`
          <li class="list-group-item d-flex justify-content-between align-items-center list-group-item-action" 
          data-chatid="${c.id}">
          ${c.name}
          <span class="badge badge-primary badge-pill">${c.unreadCount}</span>
          </li>
          `);
        item.on('click', () => selectChat(c.id, c.name));
        ul.append(item);
      });
    }

// Handler search input
    $('#chatSearchInput').on('input', function() {
      const term = $(this).val().toLowerCase();
  // filter: name/nama group/kontak mengandung term
      const filtered = chatListData.filter(c => 
        c.name.toLowerCase().includes(term) ||
        c.id.toLowerCase().includes(term)
        );
      renderChatList(filtered);
    });



    function formatTime(ts) {
      const d = new Date(ts * 1000);
      return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' }) +
      ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }



    

    function selectChat(chatId, name) {
      currentChat = String(chatId);
      const plainNumber = chatId.replace('@c.us', '');
      $('#chatTitle').text(`${name} (${plainNumber})`);
      $('#chatInput').show();
      const container = $('#messagesContainer').empty();

      fetch(`/api/wa/${session}/history?chatId=${encodeURIComponent(chatId)}`)
      .then(res => res.json())
      .then(groups => {
        const messages = groups[0]?.messages || [];
        messages.forEach(msg => {
  // Buat elemen waktu
          const timeLabel = $('<span>', {
            class: 'direct-chat-timestamp d-block mb-1',
            text: formatTime(msg.timestamp)
          });

  // Buat bubble pesan
          const body = $('<div>', { class: 'direct-chat-text position-relative' });

  // Tambahkan waktu di ATAS isi pesan
  body.append(timeLabel);              // waktu dulu
  body.append(document.createTextNode(msg.body)); // lalu isi pesan

  const msgDiv = $('<div>', {
    class: 'direct-chat-msg ' + (msg.fromMe ? '' : 'right')
  }).append(body);

  container.append(msgDiv);
});

        container.scrollTop(container.prop('scrollHeight'));
      });
    }


  // // Send message
  //   $('#sendForm').on('submit', function(e) {
  //     e.preventDefault();
  //     const message = $('#messageInput').val().trim();
  //     if (!message || !currentChat) return;
  //     const sendUrl = `//${window.location.hostname}:3001/api/${session}/send`;
  //     fetch(sendUrl, {
  //       method: 'POST',
  //       headers: { 'Content-Type': 'application/json' },
  //       body: JSON.stringify({ number: currentChat, message })
  //     })
  //     .then(res => {
  //       if (!res.ok) return res.text().then(err => console.error('Send failed:', err));
  //       $('#messageInput').val('');
  //       selectChat(currentChat, $('#chatTitle').text());
  //     });
  //   });




  // Send message
    $('#sendForm').on('submit', function(e) {
      e.preventDefault();
      const message = $('#messageInput').val().trim();
      if (!message || !currentChat) return;
      fetch(`/wa/${session}/send`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ number: currentChat, message })
      }).then(() => {
        $('#messageInput').val('');
        selectChat(currentChat, $('#chatTitle').text());
      });
    });




  // Initial load
    loadChatList();
  });
</script>
@endsection
