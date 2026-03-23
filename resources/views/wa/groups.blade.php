@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Daftar Grup WhatsApp</h2>
    <div class="form-group row">
        <label for="session" class="col-sm-2 col-form-label">Pilih Session</label>
        <div class="col-sm-4">
            <input type="text" id="session" class="form-control" placeholder="Masukkan nama session...">
        </div>
        <div class="col-sm-2">
            <button class="btn btn-primary" id="loadGroups">Ambil Grup</button>
        </div>
    </div>

    <table class="table table-bordered mt-4" id="groupsTable" style="display: none">
        <thead>
            <tr>
                <th>ID Grup</th>
                <th>Nama Grup</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="groupList"></tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('loadGroups').addEventListener('click', function () {
        const session = document.getElementById('session').value;
        if (!session) return alert('Isi nama session terlebih dahulu!');

        fetch(`/wa/${session}/groups`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('groupList');
            tbody.innerHTML = '';

            if (!Array.isArray(data)) {
                alert('Terjadi kesalahan mengambil grup');
                return;
            }

            data.forEach(group => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${group.id}</td>
                <td>${group.name}</td>
                <td>
                <button class="btn btn-success btn-sm" onclick="sendToGroup('${session}', '${group.id}')">Kirim Pesan</button>
                </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('groupsTable').style.display = 'table';
        });
    });

    function sendToGroup(session, groupId) {
        const pesan = prompt('Masukkan pesan yang ingin dikirim ke grup:');
        if (!pesan) return;

        fetch(`/wa/${session}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                number: groupId,
                message: pesan
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'sent') alert('Pesan berhasil dikirim!');
            else alert('Gagal kirim: ' + (data.message || 'Tidak diketahui'));
        })
        .catch(err => alert('Terjadi kesalahan saat mengirim: ' + err.message));
    }
</script>
@endsection
