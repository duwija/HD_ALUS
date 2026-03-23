@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-code-branch"></i> GitHub Sync</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">Manage repository synchronization with GitHub</p>
                </div>
            </div>

            <!-- Status Card -->
            @if($status['success'])
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Current Branch</h6>
                            <h4 class="text-primary"><i class="fas fa-code-branch"></i> {{ $status['branch'] }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Changed Files</h6>
                            <h4 class="text-warning"><i class="fas fa-file"></i> {{ $status['changedCount'] }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Remote</h6>
                            <small class="text-success">{{ basename($status['remote']) }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-secondary">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Last Commit</h6>
                            <small class="text-secondary">{{ substr($status['lastCommit'], 0, 20) }}...</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success btn-lg" onclick="pullFromGitHub()" id="pullBtn">
                            <i class="fas fa-download"></i> Pull from GitHub
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" onclick="showPushModal()" id="pushBtn">
                            <i class="fas fa-upload"></i> Push to GitHub
                        </button>
                        <button type="button" class="btn btn-info btn-lg" onclick="refreshStatus()" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> Refresh Status
                        </button>
                    </div>
                </div>
            </div>

            <!-- Changed Files Table -->
            @if($status['hasChanges'])
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Local Changes ({{ $status['changedCount'] }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="changesTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="80">Status</th>
                                <th>File</th>
                                <th width="100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($status['changedFiles'] as $file)
                            <tr>
                                <td>
                                    @php
                                        $status_char = substr($file, 0, 1);
                                        $file_name = substr($file, 3);
                                    @endphp
                                    @if($status_char == 'M')
                                        <span class="badge badge-warning">Modified</span>
                                    @elseif($status_char == 'A')
                                        <span class="badge badge-success">Added</span>
                                    @elseif($status_char == 'D')
                                        <span class="badge badge-danger">Deleted</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $status_char }}</span>
                                    @endif
                                </td>
                                <td>
                                    <code>{{ $file_name }}</code>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $file }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <strong>No changes!</strong> Your repository is up to date.
            </div>
            @endif

            <!-- Last Commit Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Last Commit</h5>
                </div>
                <div class="card-body">
                    <code class="text-monospace">{{ $status['lastCommit'] }}</code>
                </div>
            </div>
            @else
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> {{ $status['error'] }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Push Modal -->
<div class="modal fade" id="pushModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-upload"></i> Push to GitHub</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" id="pushForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Commit Message <span class="text-danger">*</span></strong></label>
                        <textarea name="message" id="pushMessage" class="form-control" rows="4" 
                                  placeholder="Describe your changes..." required minlength="5" maxlength="200"></textarea>
                        <small class="form-text text-muted">5-200 characters</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> All local changes will be:
                        <ol class="mb-0 pl-3">
                            <li>Added to staging</li>
                            <li>Committed with your message</li>
                            <li>Pushed to origin/main</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="pushSubmitBtn">
                        <i class="fas fa-upload"></i> Push
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="responseHeader">
                <h5 class="modal-title" id="responseTitle">Operation Result</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="responseMessage" class="mb-3"></div>
                <div id="responseOutput" style="display:none;">
                    <h6>Output:</h6>
                    <pre id="responseOutputContent" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="location.reload()">Refresh Page</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function showPushModal() {
    $('#pushModal').modal('show');
}

function pullFromGitHub() {
    if (!confirm('Are you sure you want to pull from GitHub? Any local uncommitted changes may be affected.')) {
        return;
    }

    const pullBtn = document.getElementById('pullBtn');
    pullBtn.disabled = true;
    pullBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Pulling...';

    fetch('{{ route("admin.github-sync.pull") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        showResponse(data.success, data.message, data.output);
        pullBtn.disabled = false;
        pullBtn.innerHTML = '<i class="fas fa-download"></i> Pull from GitHub';
    })
    .catch(error => {
        showResponse(false, 'Error: ' + error.message);
        pullBtn.disabled = false;
        pullBtn.innerHTML = '<i class="fas fa-download"></i> Pull from GitHub';
    });
}

document.getElementById('pushForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const message = document.getElementById('pushMessage').value;
    const pushSubmitBtn = document.getElementById('pushSubmitBtn');
    
    pushSubmitBtn.disabled = true;
    pushSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Pushing...';

    fetch('{{ route("admin.github-sync.push") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        $('#pushModal').modal('hide');
        showResponse(data.success, data.message, data.push_output);
        pushSubmitBtn.disabled = false;
        pushSubmitBtn.innerHTML = '<i class="fas fa-upload"></i> Push';
    })
    .catch(error => {
        showResponse(false, 'Error: ' + error.message);
        pushSubmitBtn.disabled = false;
        pushSubmitBtn.innerHTML = '<i class="fas fa-upload"></i> Push';
    });
});

function refreshStatus() {
    const refreshBtn = document.getElementById('refreshBtn');
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';

    fetch('{{ route("admin.github-sync.refresh") }}')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status refreshed! Please reload the page to see updates.');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status';
    })
    .catch(error => {
        alert('Error: ' + error.message);
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status';
    });
}

function showResponse(success, message, output) {
    const modal = document.getElementById('responseModal');
    const header = document.getElementById('responseHeader');
    const title = document.getElementById('responseTitle');
    const msg = document.getElementById('responseMessage');
    const outputDiv = document.getElementById('responseOutput');
    const outputContent = document.getElementById('responseOutputContent');

    if (success) {
        header.className = 'modal-header bg-success text-white';
        title.innerHTML = '<i class="fas fa-check-circle"></i> Success';
        msg.innerHTML = '<div class="alert alert-success"><i class="fas fa-check"></i> ' + message + '</div>';
    } else {
        header.className = 'modal-header bg-danger text-white';
        title.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
        msg.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times"></i> ' + message + '</div>';
    }

    if (output) {
        outputDiv.style.display = 'block';
        outputContent.textContent = output;
    } else {
        outputDiv.style.display = 'none';
    }

    $('#responseModal').modal('show');
}
</script>
@endsection
