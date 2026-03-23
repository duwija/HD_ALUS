@extends('layout.main')
@section('title', 'Logging')

@section('content')
<iframe class="col-md-12" style="width: 100vw; height: 100vh; position: relative;" 
src="{!! env('APP_URL') . '/log-viewer' !!}" 
title="Logging Viewer"></iframe>
@endsection
