@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Detail {{$title}}</h2>
    <div class="card-body">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Nama Item:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $item->name }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Jumlah:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $item->quantity }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Deskripsi:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $item->description ?? '-' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Tanggal Dibuat:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $item->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Terakhir Diupdate:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $item->updated_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        
                        @if($item->rents->count() > 0)
                        <hr>
                        <h5>Riwayat Peminjaman</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ruangan</th>
                                        <th>Peminjam</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->rents as $rent)
                                    <tr>
                                        <td>{{ $rent->room->code }}</td>
                                        <td>{{ $rent->user->name }}</td>
                                        <td>{{ $rent->pivot->quantity }}</td>
                                        <td>
                                            @if($rent->status === 'dipinjam')
                                                <span class="badge bg-primary">Dipinjam</span>
                                            @elseif($rent->status === 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($rent->status === 'selesai')
                                                <span class="badge bg-success">Selesai</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $rent->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $rent->time_start_use->format('d/m/Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        
                        <div class="d-flex justify-content-between">
                            <a href="/dashboard/items" class="btn btn-secondary">Kembali</a>
                            @if (auth()->user()->role_id <= 2)
                            <a href="/dashboard/items/{{ $item->id }}/edit" class="btn btn-warning">Edit</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 