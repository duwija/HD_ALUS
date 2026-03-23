@extends('layout.main')

@section('content')
<style>
  .tc-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
  }
  .tc-card-header {
    background: var(--bg-surface-2);
    border-bottom: 1px solid var(--border);
    padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .tc-card-header h5 { margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary); }
  .tc-card-header h5 i { color: var(--brand); margin-right: 6px; }
  .tc-card-body { padding: 20px; }
  .tc-card-footer {
    background: var(--bg-surface-2);
    border-top: 1px solid var(--border);
    padding: 12px 20px;
    display: flex; gap: 8px;
  }
  .form-label {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.4px; color: var(--text-secondary); margin-bottom: 6px;
  }
  .wf-box {
    background: var(--bg-surface-2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 10px;
  }
  .wf-box-title {
    font-size: 12px; font-weight: 700; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.4px;
    margin-bottom: 10px;
    display: flex; align-items: center; justify-content: space-between;
  }
  .wf-item {
    display: flex; align-items: center; gap: 10px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 8px;
    cursor: move;
    transition: box-shadow .15s;
  }
  .wf-item:hover { box-shadow: var(--shadow-sm); }
  .wf-num {
    min-width: 26px; height: 26px; border-radius: 50%;
    background: var(--brand); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
  }
  .wf-label { flex: 1; font-size: 14px; color: var(--text-primary); }
  .wf-grip { color: var(--text-muted); font-size: 13px; flex-shrink: 0; }
  .ui-state-highlight {
    height: 50px;
    background: var(--brand-light) !important;
    border: 2px dashed var(--brand) !important;
    border-radius: 8px;
    margin-bottom: 8px;
  }
</style>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-7 col-12">
            <div class="tc-card">
                <div class="tc-card-header">
                  <h5><i class="fas fa-plus-circle"></i>Tambah Kategori Tiket</h5>
                  <a href="{{ route('ticketcategories.index') }}" class="btn btn-sm btn-secondary" style="border-radius:8px;font-size:12px">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali
                  </a>
                </div>

                <form action="{{ route('ticketcategories.store') }}" method="POST" id="createCategoryForm">
                    @csrf
                    <div class="tc-card-body">
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="form-label" for="name">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="Contoh: Masalah Internet, Gangguan Jaringan"
                                   required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="workflow">Workflow Steps</label>
                            <small class="form-text text-muted mb-2">
                                Tambahkan langkah-langkah workflow untuk kategori ini. Tekan Enter setelah mengetik setiap step.
                            </small>
                            <div id="workflow-container">
                                <div class="input-group mb-2">
                                    <input type="text" 
                                           class="form-control workflow-step" 
                                           placeholder="Contoh: Verifikasi Masalah"
                                           id="workflow-input">
                                    <div class="input-group-append">
                                        <button class="btn btn-success add-workflow-btn" type="button">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="workflow" id="workflow-hidden" value="[]">
                        </div>

                        <div id="workflow-preview" class="mb-3"></div>
                    </div><!-- /.tc-card-body -->

                    <div class="tc-card-footer">
                        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px">
                            <i class="fas fa-save mr-1"></i>Simpan
                        </button>
                        <a href="{{ route('ticketcategories.index') }}" class="btn btn-sm btn-secondary" style="border-radius:8px">
                            <i class="fas fa-times mr-1"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
    let workflowSteps = [];

    // Add workflow step
    $('.add-workflow-btn').on('click', function() {
        const input = $('#workflow-input');
        const stepValue = input.val().trim();
        
        if (stepValue) {
            workflowSteps.push(stepValue);
            updateWorkflowHidden();
            updateWorkflowPreview();
            input.val('');
            input.focus();
        } else {
            alert('Mohon isi step terlebih dahulu!');
        }
    });

    // Enter key to add step
    $('#workflow-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('.add-workflow-btn').click();
        }
    });

    // Remove workflow step
    $(document).on('click', '.remove-workflow-step', function() {
        const index = $(this).data('index');
        const stepName = workflowSteps[index];
        if (confirm('Hapus step "' + stepName + '"?')) {
            workflowSteps.splice(index, 1);
            updateWorkflowHidden();
            updateWorkflowPreview();
        }
    });

    function updateWorkflowHidden() {
        $('#workflow-hidden').val(JSON.stringify(workflowSteps));
        console.log('Updated hidden field:', $('#workflow-hidden').val());
    }

    function updateWorkflowPreview() {
        let html = '';
        
        if (workflowSteps.length === 0) {
            $('#workflow-preview').html('');
        } else {
            html = `
                <div class="wf-box">
                    <div class="wf-box-title">
                        <span><i class="fas fa-list-ol mr-1"></i>Workflow Steps Preview</span>
                        <small style="color:var(--text-muted);font-weight:400"><i class="fas fa-arrows-alt mr-1"></i>Drag untuk ubah urutan</small>
                    </div>
                    <div id="sortable-workflow">
            `;

            workflowSteps.forEach((step, index) => {
                html += `
                    <div class="wf-item" data-index="${index}">
                        <i class="fas fa-grip-vertical wf-grip"></i>
                        <span class="wf-num">${index + 1}</span>
                        <span class="wf-label">${step}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-workflow-step" data-index="${index}" style="border-radius:6px;padding:3px 8px">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            $('#workflow-preview').html(html);
            initializeSortable();
        }
    }

    function initializeSortable() {
        if ($('#sortable-workflow').hasClass('ui-sortable')) {
            $('#sortable-workflow').sortable('destroy');
        }
        if (typeof $.fn.sortable !== 'function') return;

        // Initialize sortable
        $('#sortable-workflow').sortable({
            items: '.workflow-item',
            placeholder: 'ui-state-highlight',
            tolerance: 'pointer',
            cursor: 'move',
            opacity: 0.8,
            forcePlaceholderSize: true,
            update: function(event, ui) {
                let newOrder = [];
                $('#sortable-workflow .wf-item').each(function() {
                    let oldIndex = $(this).data('index');
                    newOrder.push(workflowSteps[oldIndex]);
                });
                workflowSteps = newOrder;
                updateWorkflowHidden();
                updateWorkflowPreview();
            }
        });
    }
});
</script>
@endsection
