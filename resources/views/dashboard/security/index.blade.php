@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Daftar {{$title}}</h2>
<div class="card-body text-end">
  <a href="/dashboard/users" type="button" class="mb-3 btn button btn-primary">
    Pilih dari User
  </a>
  <button type="button" class="mb-3 btn button btn-primary" data-bs-toggle="modal" data-bs-target="#addLoaner">
    Tambah Data Baru
  </button>
  <div class="table-responsive">
    <table class="table table-hover table-stripped table-bordered text-center dt-head-center" id="datatable">
      <thead class="table-info">
        <tr>
          <th scope="row">No.</th>
          <th scope="row">Username</th>
          <th scope="row">Nomor Induk</th>
          <th scope="row">Email</th>
          <th scope="row">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($loaners as $loaner) 
        <tr>
          <th scope="row">{{$loop->iteration}} </th>
          <td>{{$loaner->name}} </td>
          <td>{{$loaner->nomor_induk}} </td>
          <td>{{$loaner->email}} </td>
          <td style="font-size: 22px;">
            <a href="/dashboard/security/{{ $loaner->id }}/edit" class="edituser" data-id="{{ $loaner->id }}" data-bs-toggle="modal" data-bs-target="#edituser"><i class="bi bi-pencil-square text-warning"></i></a>&nbsp;
            <a href="/dashboard/security/{{ $loaner->id }}" class="bi bi-trash-fill text-danger border-0" onclick="return confirm('Hapus data loaner?')"></a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
</div>
@extends('dashboard.partials.addLoanerModal')
@extends('dashboard.partials.editUserModal')
@endsection 