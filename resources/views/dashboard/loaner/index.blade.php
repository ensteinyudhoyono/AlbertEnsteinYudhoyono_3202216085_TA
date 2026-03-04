@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Peminjaman Saya</h2>
<div class="card-body text-end">
  <a href="/dashboard/rooms" type="button" class="mb-3 btn button btn-primary">
    Pinjam Ruangan
  </a>
  <div class="table-responsive">
    <table class="table table-hover table-stripped table-bordered text-center dt-head-center" id="datatable">
      <thead class="table-info">
        <tr>
          <th scope="row">No.</th>
          <th scope="row">Ruangan</th>
          <th scope="row">Tanggal</th>
          <th scope="row">Waktu Mulai</th>
          <th scope="row">Waktu Selesai</th>
          <th scope="row">Status</th>
          <th scope="row">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rents as $rent) 
        <tr>
          <th scope="row">{{$loop->iteration}} </th>
          <td>{{$rent->room->name}} ({{$rent->room->code}})</td>
          <td>{{$rent->date}}</td>
          <td>{{$rent->start_time}}</td>
          <td>{{$rent->end_time}}</td>
          <td>
            @if($rent->status == 1)
              <span class="badge bg-success">Disetujui</span>
            @elseif($rent->status == 0)
              <span class="badge bg-warning">Menunggu</span>
            @else
              <span class="badge bg-danger">Ditolak</span>
            @endif
          </td>
          <td style="font-size: 22px;">
            @if($rent->status == 1)
              <a href="/dashboard/rents/{{ $rent->id }}/endTransaction" class="bi bi-check-circle-fill text-success border-0" onclick="return confirm('Selesaikan peminjaman?')"></a>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
</div>
@endsection 