@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-book"></i> {{ $title }}</h3>
                    <a href="{{ route('admin.documentation') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Documentation
                    </a>
                </div>
                <div class="card-body">
                    <div id="markdown-content" class="markdown-body"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Markdown CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.1.0/github-markdown.min.css">

<!-- Marked.js for Markdown parsing -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<style>
    .markdown-body {
        box-sizing: border-box;
        min-width: 200px;
        max-width: 100%;
        padding: 20px;
    }
    
    .markdown-body pre {
        background-color: #f6f8fa;
        border-radius: 6px;
        padding: 16px;
        overflow: auto;
    }
    
    .markdown-body code {
        background-color: rgba(175, 184, 193, 0.2);
        border-radius: 6px;
        padding: 0.2em 0.4em;
        font-family: 'Courier New', monospace;
    }
    
    .markdown-body table {
        display: block;
        width: max-content;
        max-width: 100%;
        overflow: auto;
    }
    
    .markdown-body h1, 
    .markdown-body h2 {
        border-bottom: 1px solid #eaecef;
        padding-bottom: 0.3em;
    }
    
    .markdown-body img {
        max-width: 100%;
        height: auto;
    }
</style>

<script>
    // Markdown content from backend
    const markdownContent = {!! json_encode($content) !!};
    
    // Parse and render markdown
    document.addEventListener('DOMContentLoaded', function() {
        const contentElement = document.getElementById('markdown-content');
        contentElement.innerHTML = marked.parse(markdownContent);
        
        // Add table classes for Bootstrap styling
        const tables = contentElement.querySelectorAll('table');
        tables.forEach(table => {
            table.classList.add('table', 'table-bordered', 'table-striped');
        });
        
        // Add alert classes for blockquotes
        const blockquotes = contentElement.querySelectorAll('blockquote');
        blockquotes.forEach(blockquote => {
            blockquote.classList.add('alert', 'alert-info');
        });
    });
</script>
@endsection
