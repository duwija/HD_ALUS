@extends('layout.main')
@section('title','Customer log')
@section('content')


<section class="content-header">


  <div class="card  card-outline">
    <div class="card-header bg-primary  ">
        <h3 class="card-title font-weight-bold "> Show Logs </h3>
    </div>

    <div class="card-body">



        <h2>Customer Changed Logs</h2>
        <a href="{{ url('customer/'.$id) }}" class="btn btn-primary mb-3">Kembali</a>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Updated By</th>
                    <th>Topic</th>
                    <th>Logs</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logEntries as $log)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $log->date }}</td>
                    <td>{{ $log->updated_by }}</td>
                    <td>{{ $log->topic }}</td>
                    <td>
                        <ul>
                            @php
                            $changes = json_decode($log->updates, true);
                            @endphp
                            @foreach ($changes as $key => $change)
                            <li><strong>{{ ucfirst($key) }}</strong>: 
                                @if(is_array($change))
                                <span class="text-danger">{{ $change['old'] ?? 'N/A' }}</span> → 
                                <span class="text-success">{{ $change['new'] ?? 'N/A' }}</span>
                                @else
                                {{ $change }}
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">log not found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</section>
@endsection