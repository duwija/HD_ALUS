@extends('layout.main')
@section('title','Add Sub-Ticket')

@section('content')
<section class="content-header">

  <div class="alert alert-info shadow-sm">
    <h5 class="mb-2"><i class="fas fa-info-circle"></i> Creating Sub-Ticket for Parent:</h5>
    <strong>#{{$parent->id}} - {{$parent->tittle}}</strong>
    <span class="badge badge-{{$parent->status == 'Open' ? 'danger' : 'primary'}} ml-2">{{$parent->status}}</span>
    <a href="/ticket/{{$parent->id}}" class="btn btn-sm btn-outline-primary float-right">
      <i class="fas fa-arrow-left"></i> Back to Parent
    </a>
  </div>

  <div class="card shadow">
    <div class="card-header bg-gradient-info text-white">
      <h3 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Add New Sub-Ticket</h3>
    </div>
    <form method="post" action="/ticket/{{$parent->id}}/store-child">
      @csrf
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label><i class="fas fa-heading"></i> Title *</label>
              <input type="text" class="form-control @error('tittle') is-invalid @enderror" 
                     name="tittle" placeholder="Survey, Installasi, Aktivasi" required>
              @error('tittle')<span class="text-danger">{{$message}}</span>@enderror
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><i class="fas fa-folder"></i> Category *</label>
              <select name="id_categori" class="form-control @error('id_categori') is-invalid @enderror" required>
                <option value="">-- Select --</option>
                @foreach($ticketcategorie as $id => $name)
                <option value="{{$id}}">{{$name}}</option>
                @endforeach
              </select>
              @error('id_categori')<span class="text-danger">{{$message}}</span>@enderror
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><i class="fas fa-flag"></i> Status *</label>
              <select name="status" class="form-control" required>
                <option value="Open" selected>Open</option>
                <option value="Inprogress">Inprogress</option>
                <option value="Pending">Pending</option>
                <option value="Solve">Solve</option>
                <option value="Close">Close</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-user-cog"></i> Assign To *</label>
              <select name="assign_to" class="form-control select2" required>
                <option value="">-- Select User --</option>
                @foreach($user as $id => $name)
                <option value="{{$id}}">{{$name}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="fas fa-users"></i> Team Members</label>
              <select name="member[]" class="form-control select2" multiple data-placeholder="Select members">
                @foreach($user as $id => $name)
                <option value="{{$name}}">{{$name}}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="far fa-calendar"></i> Date *</label>
              <input type="date" name="date" class="form-control" value="{{date('Y-m-d')}}" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="far fa-clock"></i> Time *</label>
              <input type="time" name="time" class="form-control" value="{{date('H:i')}}" required>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label><i class="fas fa-file-alt"></i> Description *</label>
          <textarea name="description" class="textarea" required></textarea>
        </div>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Create Sub-Ticket</button>
        <a href="/ticket/{{$parent->id}}" class="btn btn-default"><i class="fas fa-times"></i> Cancel</a>
      </div>
    </form>
  </div>
</section>
@endsection

@section('footer-scripts')
<script src="/adminlte/plugins/summernote/summernote-bs4.min.js"></script>
<script src="/adminlte/plugins/select2/js/select2.full.min.js"></script>
<script>
  $(function () {
    $('.textarea').summernote({ height: 200 });
    $('.select2').select2({ theme: 'bootstrap4' });
  });
</script>
@endsection
