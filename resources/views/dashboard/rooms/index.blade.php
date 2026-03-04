@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Daftar {{$title}}</h2>
<div class="card-body text-end">
  @if(session()->has('roomSuccess'))
    <div class="col-md-16 mx-auto alert alert-success text-center  alert-success alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('roomSuccess')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session()->has('deleteRoom'))
    <div class="col-md-16 mx-auto alert alert-success text-center  alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('deleteRoom')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if (auth()->user()->role_id <= 4)    
  <button type="button" class="mb-3 btn button btn-primary" data-bs-toggle="modal" data-bs-target="#pinjamRuangan">
    Pinjam
  </button>
    @endif
  
  @if (auth()->user()->role_id <= 2)
  <button type="button" class="mb-3 btn button btn-primary" data-bs-toggle="modal" data-bs-target="#addRoom">
    Tambah Ruangan
  </button>
  @endif
  <div class="table-responsive">
    <table class="table table-hover table-stripped table-bordered text-center dt-head-center" id="rooms-datatable">
      <thead class="table-info">
        <tr>
          <th class="text-center" scope="row">No.</th>
          <th class="text-center" scope="row">Nama Ruangan</th>
          <th class="text-center" scope="row">Kode Ruangan</th>
          @if(auth()->user()->role_id <= 2)
          <th class="text-center" scope="row">Action</th>
          @endif
        </tr>
      </thead>
      <tbody>
        <!-- Data via AJAX -->
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-end"></div>
</div>
</div>
@extends('dashboard.partials.rentModal')
@extends('dashboard.partials.addRoomModal')
@extends('dashboard.partials.editRoomModal')
<script>
$(document).ready(function(){
  $('#rooms-datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '/dashboard/rooms', type: 'GET' },
    dom: '<"row"<"col-sm-12 col-md-6 d-flex align-items-center"l><"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>>' +
         '<"row"<"col-sm-12"tr>>' +
         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-end"p>>',
    columns: [
      { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
      { data: 'name', render: function(data){ return data; } },
      { data: 'code' },
      @if(auth()->user()->role_id <= 2)
      { data: 'actions', orderable: false, searchable: false }
      @endif
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/id.json' },
    order: [[1, 'asc']],
    pageLength: 10,
    lengthMenu: [[10,25,50,-1],[10,25,50,'Semua']],
    columnDefs: [{ targets: '_all', className: 'dt-head-center' }],
    responsive: true
  });
  $('#rooms-datatable_length').css('text-align', 'left');
});
</script>
@endsection
