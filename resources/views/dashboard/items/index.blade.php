@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Daftar {{$title}}</h2>
<div class="card-body text-end">
  @if(session()->has('itemSuccess'))
    <div class="col-md-16 mx-auto alert alert-success text-center alert-success alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('itemSuccess')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session()->has('deleteItem'))
    <div class="col-md-16 mx-auto alert alert-success text-center alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('deleteItem')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session()->has('itemError'))
    <div class="col-md-16 mx-auto alert alert-danger text-center alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('itemError')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  
     <!-- Statistics Cards -->
   <div class="row mb-3" id="stats-container">
     <div class="col-md-2">
       <div class="card text-center">
         <div class="card-body">
           <h5 class="card-title" id="total-items">-</h5>
           <p class="card-text">Total Item</p>
         </div>
       </div>
     </div>
     <div class="col-md-2">
       <div class="card text-center">
         <div class="card-body">
           <h5 class="card-title" id="total-stock">-</h5>
           <p class="card-text">Stok (Tersedia/Total)</p>
         </div>
       </div>
     </div>
     <div class="col-md-2">
       <div class="card text-center">
         <div class="card-body">
           <h5 class="card-title" id="total-rented">-</h5>
           <p class="card-text">Total Dipinjam</p>
         </div>
       </div>
     </div>
     <div class="col-md-2">
       <div class="card text-center">
         <div class="card-body">
           <h5 class="card-title" id="out-of-stock">-</h5>
           <p class="card-text">Stok Habis</p>
         </div>
       </div>
     </div>
     <div class="col-md-2">
       <div class="card text-center">
         <div class="card-body">
           <h5 class="card-title" id="low-stock">-</h5>
           <p class="card-text">Stok Menipis</p>
         </div>
       </div>
     </div>
   </div>
  
  @if (auth()->user()->role_id <= 2)    
  <a href="/dashboard/items/create" class="mb-3 btn button btn-primary">
    Tambah Item
  </a>
  @endif
  
  <div class="table-responsive">
    <table class="table table-hover table-stripped table-bordered text-center" id="items-datatable">
             <thead class="table-info">
         <tr>
           <th>No.</th>
           <th>Nama Item</th>
           <th>Jumlah Tersedia</th>
           <th>Jumlah Dipinjam</th>
           <th>Total</th>
           <th>Yang Meminjam</th>
           @if (auth()->user()->role_id <= 2)
             <th>Action</th>
           @endif
         </tr>
       </thead>
      <tbody>
        <!-- Data will be loaded via AJAX -->
      </tbody>
    </table>
  </div>
</div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable for items
    var itemsTable = $('#items-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/dashboard/items',
            type: 'GET'
        },
        columns: [
            {
                data: null,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                },
                orderable: false,
                searchable: false
            },
            { data: 'name' },
            { 
                data: 'available_quantity',
                render: function(data, type, row) {
                    var badge = '';
                    if (data <= 0) {
                        badge = '<span class="badge bg-danger">Habis</span>';
                    } else if (data < 5) {
                        badge = '<span class="badge bg-warning">Menipis</span>';
                    }
                    return data + ' ' + badge;
                }
            },
            { 
                data: 'rented_quantity',
                render: function(data, type, row) {
                    return data > 0 ? data : '-';
                }
            },
            { 
                data: 'total_quantity',
                render: function(data, type, row) {
                    return data;
                }
            },
            { 
                data: 'current_renters',
                render: function(data, type, row) {
                    return data;
                }
            }@if (auth()->user()->role_id <= 2),
            {
                data: 'actions',
                orderable: false,
                searchable: false
            }
            @endif
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/id.json'
        },
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
        columnDefs: [
            {
                targets: '_all',
                className: 'dt-head-center'
            }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        responsive: true
    });

    // Update statistics when data is loaded
    itemsTable.on('xhr', function() {
        var data = itemsTable.ajax.json();
        if (data && data.data) {
            updateStatistics(data.data);
        }
    });

    // Function to update statistics
    function updateStatistics(items) {
        var totalItems = items.length;
        var totalAvailableStock = 0;
        var totalRentedStock = 0;
        var outOfStock = 0;
        var lowStock = 0;

        items.forEach(function(item) {
            totalAvailableStock += item.available_quantity;
            totalRentedStock += item.rented_quantity;
            if (item.available_quantity <= 0) outOfStock++;
            if (item.available_quantity < 5 && item.available_quantity > 0) lowStock++;
        });

        $('#total-items').text(totalItems);
        $('#total-stock').text(totalAvailableStock + ' / ' + (totalAvailableStock + totalRentedStock));
        $('#total-rented').text(totalRentedStock);
        $('#out-of-stock').text(outOfStock);
        $('#low-stock').text(lowStock);
    }

    // Function to refresh table and statistics
    function refreshTable() {
        itemsTable.ajax.reload(function() {
            // Update statistics after reload
            var data = itemsTable.ajax.json();
            if (data && data.data) {
                updateStatistics(data.data);
            }
        }, false);
    }

    // Refresh table when returning from create/edit pages
    if (window.location.search.includes('refresh=true')) {
        refreshTable();
        // Remove the refresh parameter from URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Initial statistics load
    $.ajax({
        url: '/dashboard/items',
        type: 'GET',
        success: function(response) {
            if (response.data) {
                updateStatistics(response.data);
            }
        }
    });

    // Custom styling for items datatable
    $('#items-datatable_length').css('text-align', 'left');
    $('#items-datatable_info').css('text-align', 'left');
});
</script>
@endsection 