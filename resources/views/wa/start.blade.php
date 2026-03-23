@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Mulai Session WhatsApp Baru</h3>

    <form id="startSessionForm">
        <div class="form-group">
            <label for="session">Nama Session</label>
            <input type="text" id="session" name="session" class="form-control" placeholder="contoh: aluswa1" required>
        </div>
        <button type="submit" class="btn btn-primary">Mulai Session</button>
    </form>

    <div id="result" class="mt-4"></div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('startSessionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const session = document.getElementById('session').value.trim();
        if (!session) return alert('Nama session tidak boleh kosong');

        fetch('http://127.0.01:3001/api/wa/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ session })
        })
        .then(res => res.json())
        .then(data => {
            const result = document.getElementById('result');
            if (data.status === 'started' || data.status === 'already_running') {
                result.innerHTML = `<div class="alert alert-success">Session <strong>${session}</strong> ${data.status.replace('_', ' ')}</div>`;
                setTimeout(() => window.location.href = `/wa/${session}/qr`, 1500);
            } else {
                result.innerHTML = `<div class="alert alert-danger">Gagal: ${data.message}</div>`;
            }
        })
        .catch(err => {
            document.getElementById('result').innerHTML = `<div class="alert alert-danger">Terjadi error: ${err.message}</div>`;
        });
    });
</script>
@endsection
