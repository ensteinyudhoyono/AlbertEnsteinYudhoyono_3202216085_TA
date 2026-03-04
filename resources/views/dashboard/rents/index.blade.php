@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Daftar {{$title}}</h2>
<div class="card-body text-end">
  @if(session()->has('rentSuccess'))
    <div class="col-md-16 mx-auto alert alert-success text-center  alert-success alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('rentSuccess')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session()->has('deleteRent'))
    <div class="col-md-16 mx-auto alert alert-success text-center  alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('deleteRent')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  <div class="d-flex gap-2 justify-content-end mb-3">
    @if (auth()->user()->role_id <= 4)
    <button type="button" class="btn button btn-primary" data-bs-toggle="modal" data-bs-target="#pinjamRuangan">Pinjam</button>
    @endif
    <a href="{{ route('rents.export') }}" class="btn btn-success">
      <i class="bi bi-file-earmark-excel"></i> Export Excel
    </a>
  </div>
  
  <div class="table-responsive">
    <table class="table table-hover table-stripped table-bordered text-center dt-head-center" id="rents-datatable">
      <thead class="table-info">
        <tr>
          <th scope="row">No.</th>
          <th scope="row">Kode Ruangan</th>
          @if (auth()->user()->role_id <= 2)
            <th scope="row">Nama Peminjam</th>              
          @endif
          <th scope="row">Mulai Pinjam</th>
          <th scope="row">Selesai Pinjam</th>
          <th scope="row">Tujuan</th>
          <th scope="row">Item yang Dipinjam</th>
          <th scope="row">Waktu Pengembalian</th>
          <th scope="row">Denda</th>
          @if (auth()->user()->role_id <= 2)
            <th scope="row">Kembalikan</th>
          @endif
          <th scope="row">Status Pinjam</th>
          <th scope="row">Catatan</th>
          @if (auth()->user()->role_id == 1)
            <th scope="row">Action</th>
          @endif
        </tr>
      </thead>
      <tbody>
        <!-- Data via AJAX -->
      </tbody>
    </table>
  </div>
</div>
</div>
@extends('dashboard.partials.rentModal')
<script>
$(document).ready(function(){
  var isAdminOrSecurity = {{ auth()->user()->role_id <= 2 ? 'true' : 'false' }};
  var isAdmin = {{ auth()->user()->role_id == 1 ? 'true' : 'false' }};

  var table = $('#rents-datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '/dashboard/rents', type: 'GET' },
    autoWidth: false,
    scrollX: true,
    dom: '<"row"<"col-sm-12 col-md-6 d-flex align-items-center"l><"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>>' +
         '<"row"<"col-sm-12"tr>>' +
         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-end"p>>',
    columns: [
      { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
      { data: 'code', render: function(d){ return d; } },
      @if (auth()->user()->role_id <= 2)
      { data: 'borrower' },
      @endif
      { data: 'start' },
      { data: 'end' },
      { data: 'purpose' },
      { data: 'items', render: function(d){ return d; } },
      { data: 'return_time' },
      { data: 'penalty', orderable:false, searchable:false, render: function(d){ return d; } },
      @if (auth()->user()->role_id <= 2)
      { data: 'return_action', orderable:false, searchable:false, render: function(d){ return d; } },
      @endif
      { data: 'status_badge', orderable:false, searchable:false, render: function(d){ return d; } },
      { data: 'notes', orderable:false, searchable:false, render: function(d){ return d; } },
      @if (auth()->user()->role_id == 1)
      { data: 'actions', orderable:false, searchable:false, render: function(d){ return d; } }
      @endif
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/id.json' },
    order: [[1, 'asc']],
    pageLength: 10,
    lengthMenu: [[10,25,50,-1],[10,25,50,'Semua']],
    columnDefs: (function(){
      var defs = [{ targets: '_all', className: 'dt-head-center' }];
      var col = 0;
      var idxNo = col++;
      var idxCode = col++;
      var idxBorrower = null;
      if (isAdminOrSecurity) idxBorrower = col++;
      var idxStart = col++;
      var idxEnd = col++;
      var idxPurpose = col++;
      var idxItems = col++;
      var idxReturnTime = col++;
      var idxReturn = null;
      if (isAdminOrSecurity) idxReturn = col++;
      var idxStatus = col++;
      var idxNotes = col++;
      var idxActions = null;
      if (isAdmin) idxActions = col++;

      // Center some columns
      defs.push({ targets: [idxNo, idxCode, idxStart, idxEnd, idxReturnTime, idxStatus].filter(i => i !== null), className: 'dt-center dt-nowrap' });
      if (idxReturn !== null) defs.push({ targets: [idxReturn], className: 'dt-center' });

      // Left-align text-heavy columns
      var lefts = [idxPurpose, idxItems, idxNotes];
      if (idxBorrower !== null) lefts.push(idxBorrower);
      defs.push({ targets: lefts, className: 'dt-left' });

      // Ellipsis for long text
      defs.push({ targets: [idxPurpose, idxItems, idxNotes], className: 'dt-ellipsis' });

      // Optional fixed widths
      defs.push({ targets: [idxCode], width: 80 });
      defs.push({ targets: [idxStart, idxEnd], width: 130 });
      defs.push({ targets: [idxReturnTime], width: 150 });
      return defs;
    })(),
    responsive: true
  });
  $('#rents-datatable_length').css('text-align', 'left');

  // Enable bootstrap tooltip for truncated notes on initial draw and every redraw
  function enableNotesTooltip(){
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('.dt-notes'))
    tooltipTriggerList.forEach(function (el) {
      if ($(el).data('bs.tooltip')) {
        $(el).tooltip('dispose');
      }
      $(el).tooltip({ html: false, container: 'body' });
    });
  }
  table.on('draw', enableNotesTooltip);
  enableNotesTooltip();
});
</script>
<!-- Modal: Catatan Lengkap (untuk perangkat sentuh/HP) -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Catatan</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="noteModalBody" class="text-muted"></div>
      </div>
    </div>
  </div>
  </div>
<script>
  // Perangkat sentuh: tap pada catatan membuka modal berisi teks lengkap
  (function(){
    var isTouch = ('ontouchstart' in window) || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
    if (!isTouch) return; // Tooltip sudah cukup untuk desktop

    function bindNoteModal(){
      $(document).off('click.dtNotes');
      $(document).on('click.dtNotes', '.dt-notes', function(e){
        e.preventDefault();
        var full = $(this).attr('title') || $(this).text();
        $('#noteModalBody').text(full);
        var modal = new bootstrap.Modal(document.getElementById('noteModal'));
        modal.show();
      });
    }
    bindNoteModal();
  })();
</script>
@endsection
