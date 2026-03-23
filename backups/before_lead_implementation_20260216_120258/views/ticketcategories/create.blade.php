@extends('layout.main')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah Kategori Tiket</h3>
                </div>

                <form action="{{ route('ticketcategories.store') }}" method="POST" id="createCategoryForm">
                    @csrf
                    <div class="card-body">
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
                            <label for="name">Nama Kategori <span class="text-danger">*</span></label>
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
                            <label for="workflow">Workflow Steps</label>
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
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <a href="{{ route('ticketcategories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<!-- jQuery UI untuk drag & drop -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
    .ui-state-highlight {
        height: 60px;
        background-color: #f0f0f0;
        border: 2px dashed #ccc;
        margin-bottom: 8px;
        border-radius: 4px;
    }
    .workflow-item:hover {
        background-color: #f8f9fa !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .workflow-item {
        transition: all 0.2s ease;
        cursor: move !important;
    }
</style>

<script>
$(document).ready(function() {
    // Debug check
    console.log('jQuery version:', $.fn.jquery);
    console.log('jQuery UI available:', typeof $.ui !== 'undefined');
    console.log('Sortable available:', typeof $.fn.sortable !== 'undefined');
    
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
                <div class="alert alert-secondary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong>Workflow Steps Preview:</strong>
                        <small class="text-muted"><i class="fas fa-arrows-alt"></i> Drag & drop untuk mengubah urutan</small>
                    </div>
                    <div id="sortable-workflow">
            `;

            workflowSteps.forEach((step, index) => {
                html += `
                    <div class="workflow-item d-flex align-items-center mb-2 p-3 border rounded bg-white" data-index="${index}">
                        <div class="mr-3">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="mr-3" style="min-width: 50px;">
                            <span class="badge badge-primary step-number" style="font-size: 14px;">${index + 1}</span>
                        </div>
                        <div class="flex-grow-1" style="font-size: 15px;">
                            ${step}
                        </div>
                        <div class="ml-2">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger remove-workflow-step" 
                                    data-index="${index}"
                                    title="Hapus step">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            $('#workflow-preview').html(html);

            // Initialize sortable setelah HTML dirender
            initializeSortable();
        }
    }

    function initializeSortable() {
        // Destroy existing sortable jika ada
        if ($('#sortable-workflow').hasClass('ui-sortable')) {
            $('#sortable-workflow').sortable('destroy');
        }

        // Check jika jQuery UI Sortable tersedia
        if (typeof $.fn.sortable !== 'function') {
            console.error('jQuery UI Sortable not loaded!');
            return;
        }

        // Initialize sortable
        $('#sortable-workflow').sortable({
            items: '.workflow-item',
            placeholder: 'ui-state-highlight',
            tolerance: 'pointer',
            cursor: 'move',
            opacity: 0.8,
            forcePlaceholderSize: true,
            update: function(event, ui) {
                console.log('Sortable update triggered');
                
                // Update array berdasarkan urutan baru
                let newOrder = [];
                $('#sortable-workflow .workflow-item').each(function() {
                    let oldIndex = $(this).data('index');
                    newOrder.push(workflowSteps[oldIndex]);
                });
                
                console.log('Old order:', workflowSteps);
                console.log('New order:', newOrder);
                
                workflowSteps = newOrder;
                
                // Update hidden field dan re-render untuk update nomor
                updateWorkflowHidden();
                updateWorkflowPreview();
            }
        });

        console.log('Sortable initialized on #sortable-workflow');
    }
});
</script>
@endsection
