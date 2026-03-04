@extends('dashboard.layouts.main')

@section('container')
<!-- Main Content -->
{{-- @dd(request()->segment(count(request()->segments()))) --}}
{{-- @dd(count(request()->segments())) --}}
<div class="col-md-10 p-0">
    <h2 class="content-title text-center mb-3">{{$room->name}}</h2>
    <article class='explore-detail d-flex flex-wrap' style="margin-left: 20px;" tabindex='0'>
        <div class='img-container'>
          @php 
            // Resolve image URL with robust fallback (uses media proxy to avoid Windows symlink issues)
            $cacheBust = optional($room->updated_at)->timestamp ?? time();
            $imagePath = $room->img ?: 'room-image/roomdefault.jpg';
            $publicDisk = Illuminate\Support\Facades\Storage::disk('public');
            if ($publicDisk->exists($imagePath)) {
              // Use route proxy that streams from the public disk irrespective of symlink state
              $imageUrl = route('media.public', ['path' => $imagePath]);
            } else {
              // Fallback to public/img default if file not found in storage
              $imageUrl = asset('img/roomdefault.jpg');
            }
          @endphp
          <img
            class='explore-item__thumbnail'
            src='{{ $imageUrl }}?v={{ $cacheBust }}'
            alt='{{ $room->name . '.jpg' }}'
            tabindex='0'
            style="width: 18rem;"
          />
        </div>
        
        <ul class='detail-explore__info'>
            <table class="table table-borderless table-sm">
                <thead>
                </thead>
                <tbody>
                    <tr>
                        <th scope="col">Nama</th>
                        <td>: {{$room->name}}</td>
                    </tr>
                    <tr>
                        <th scope="col">Kode Ruangan</th>
                        <td>: {{$room->code}}</td>
                    </tr>

                    <tr>
                        <th scope="col">Lantai</th>
                        <td>: {{$room->floor}}</td>
                    </tr>
                    <tr>
                        <th scope="col">Kapasitas</th>
                        <td>: {{$room->capacity}}</td>
                    </tr>

                    <tr>
                        <th scope="col">Deskripsi</th>
                        <td>: {{$room->description}}</td>
                    </tr>
                </tbody>
            </table>
        </ul>
    </article>
    <h2 class="content-title text-center" style="margin-top: 20px;">Daftar Peminjaman </h2>
    <!-- tambahkan content disini! -->
    <div class="card-body text-end me-3">
        @if (auth()->user()->role_id <= 4)
            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#pinjamRuangan">Pinjam</button>
        @endif
        @if (auth()->user()->role_id <= 2)
            <button type="button"
                    class="btn btn-warning mb-3 editroom"
                    id="editroom"
                    data-id="{{ $room->id }}"
                    data-code="{{ $room->code }}"
                    data-bs-toggle="modal"
                    data-bs-target="#editRoom">
                Edit Ruangan
            </button>
        @endif
        <div class="table-responsive">
            <table class="table table-hover table-stripped table-bordered text-center dt-head-center" id="datatable">
                <thead class="table-info">
                  <tr>
                    <th scope="row">No.</th>
                    <th scope="row">Nama Peminjam</th>
                    <th scope="row">Mulai Pinjam</th>
                    <th scope="row">Selesai Pinjam</th>
                    <th scope="row">Tujuan</th>
                    <th scope="row">Waktu Transaksi</th>
                    <th scope="row">Status Pinjam</th>
                  </tr>
                </thead>
                <tbody class="rent-details">
                    @foreach ($rents as $rent)
                    <tr class="rent-detail">
                      <th scope="row">{{ $loop->iteration }}</th scope="row">
                      <td>{{ $rent->user->name }}</td>
                      <td class="detail-rent-room_start-time">{{ $rent->time_start_use }}</td>
                      <td>{{ $rent->time_end_use }}</td>
                      <td>{{ $rent->purpose }}</td>
                      <td>{{ $rent->transaction_start }}</td>
                      <td>{{ $rent->status }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
        </div>
    </div>
</div>
<!-- Main Content -->
</div>
@extends('dashboard.partials.rentModal')
@extends('dashboard.partials.editRoomModal')
@endsection