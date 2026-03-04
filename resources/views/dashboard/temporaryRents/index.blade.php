@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Daftar {{$title}}</h2>
<div class="card-body text-end">
  @if(session()->has('rentSuccess'))
    <div class="col-md-16 mx-auto alert alert-success text-center alert-success alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('rentSuccess')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session()->has('deleteRent'))
    <div class="col-md-16 mx-auto alert alert-success text-center alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('deleteRent')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session()->has('rentError'))
    <div class="col-md-16 mx-auto alert alert-danger text-center alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('rentError')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  <div class="table-responsive">
    <table class="table table-hover table-stripped table-bordered text-center dt-head-center" id="temporary-rents-datatable">
      <thead class="table-info">
        <tr>
          <th scope="row">No.</th>
          <th scope="row">Nama Ruangan</th>
          <th scope="row">Nama Peminjam</th>
          <th scope="row">Mulai Pinjam</th>
          <th scope="row">Selesai Pinjam</th>
          <th scope="row">Tujuan</th>
          <th scope="row">Item yang Dipinjam</th>
          <th scope="row">Mulai Transaksi</th>
          <th scope="row">Status Pinjam</th>
          <th scope="row">Catatan</th>
          <th scope="row">Action</th>
        </tr>
      </thead>
      <tbody>
        <!-- Data via AJAX -->
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- Modal untuk Accept dengan Catatan & Jumlah Item Disetujui -->
@foreach ($rents as $rent)
<div class="modal fade" id="acceptModal{{ $rent->id }}" tabindex="-1" aria-labelledby="acceptModalLabel{{ $rent->id }}" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="acceptModalLabel{{ $rent->id }}">Setujui Peminjaman</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/dashboard/temporaryRents/{{ $rent->id }}/acceptRents" method="POST">
        @csrf
        <div class="modal-body">
          <p><strong>Detail Peminjaman:</strong></p>
          <p>Ruangan: {{ $rent->room->code }}</p>
          <p>Peminjam: {{ $rent->user->name }}</p>
          <p>Waktu: {{ $rent->time_start_use }} - {{ $rent->time_end_use }}</p>
          <p>Tujuan: {{ $rent->purpose }}</p>
          
          @if($rent->items->count())
          <div class="mb-3">
            <label class="form-label">Sesuaikan Jumlah Item yang Disetujui</label>
            <div class="border rounded p-2">
              @foreach($rent->items as $item)
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div>
                    {{ $item->name }}
                    <small class="text-muted">(Diminta: {{ $item->pivot->quantity }}, Stok: {{ $item->quantity }})</small>
                  </div>
                  <div style="width:120px">
                    <input type="number" name="approved_quantities[{{ $item->id }}]" class="form-control form-control-sm" min="0" max="{{ $item->quantity }}" value="{{ $item->pivot->quantity }}">
                  </div>
                </div>
              @endforeach
            </div>
            <small class="text-muted">Nilai 0 berarti item tidak diberikan.</small>
          </div>
          @endif

          <div class="mb-3">
            <label for="notes{{ $rent->id }}" class="form-label">Catatan (Opsional)</label>
            <textarea class="form-control" id="notes{{ $rent->id }}" name="notes" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Setujui Peminjaman</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal untuk Decline dengan Catatan -->
<div class="modal fade" id="declineModal{{ $rent->id }}" tabindex="-1" aria-labelledby="declineModalLabel{{ $rent->id }}" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="declineModalLabel{{ $rent->id }}">Tolak Peminjaman</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/dashboard/temporaryRents/{{ $rent->id }}/declineRents" method="POST">
        @csrf
        <div class="modal-body">
          <p><strong>Detail Peminjaman:</strong></p>
          <p>Ruangan: {{ $rent->room->code }}</p>
          <p>Peminjam: {{ $rent->user->name }}</p>
          <p>Waktu: {{ $rent->time_start_use }} - {{ $rent->time_end_use }}</p>
          <p>Tujuan: {{ $rent->purpose }}</p>
          
          <div class="mb-3">
            <label for="notes{{ $rent->id }}" class="form-label">Alasan Penolakan (Opsional)</label>
            <textarea class="form-control" id="notes{{ $rent->id }}" name="notes" rows="3" placeholder="Berikan alasan penolakan..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Tolak Peminjaman</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<script>
$(document).ready(function(){
  var table = $('#temporary-rents-datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '/dashboard/temporaryRents', type: 'GET' },
    dom: '<"row"<"col-sm-12 col-md-6 d-flex align-items-center"l><"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>>' +
         '<"row"<"col-sm-12"tr>>' +
         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-end"p>>',
    columns: [
      { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
      { data: 'room_code', render: function(d){ return d; } },
      { data: 'user_name' },
      { data: 'start' },
      { data: 'end' },
      { data: 'purpose' },
      { data: 'items', render: function(d){ return d; } },
      { data: 'transaction_start' },
      { data: 'status' },
      { data: 'notes', orderable:false, searchable:false, render: function(d){ return d; } },
      { data: 'actions', orderable:false, searchable:false, render: function(d, type, row){ return d; } },
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/id.json' },
    order: [[1, 'asc']],
    pageLength: 10,
    lengthMenu: [[10,25,50,-1],[10,25,50,'Semua']],
    columnDefs: [{ targets: '_all', className: 'dt-head-center' }],
    responsive: true
  });
  $('#temporary-rents-datatable_length').css('text-align', 'left');

  // Accept/Decline handlers via AJAX prompt for notes
  $(document).on('click', '.btn-accept', function(){
    var id = $(this).data('rent-id');
    var notes = prompt('Catatan (opsional):');
    if (notes === null) return; // cancelled
    $.post('/dashboard/temporaryRents/' + id + '/acceptRents', { _token: $('meta[name="csrf-token"]').attr('content'), notes: notes }, function(){ table.ajax.reload(null, false); });
  });
  $(document).on('click', '.btn-decline', function(){
    var id = $(this).data('rent-id');
    var notes = prompt('Alasan penolakan (opsional):');
    if (notes === null) return; // cancelled
    $.post('/dashboard/temporaryRents/' + id + '/declineRents', { _token: $('meta[name="csrf-token"]').attr('content'), notes: notes }, function(){ table.ajax.reload(null, false); });
  });
});
</script>
@endsection
