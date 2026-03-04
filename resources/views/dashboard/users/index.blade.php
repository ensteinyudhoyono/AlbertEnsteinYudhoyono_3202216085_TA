@extends('dashboard.layouts.main')

@section('container')
<div class="col-md-10 p-0">
    <h2 class="content-title text-center">Daftar {{$title}}</h2>
<div class="card-body text-end">
  @if(session()->has('userSuccess'))
    <div class="col-md-16 mx-auto alert alert-success text-center  alert-success alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('userSuccess')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session()->has('deleteUser'))
    <div class="col-md-16 mx-auto alert alert-success text-center  alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('deleteUser')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if (auth()->user()->role_id == 1)
  <button type="button" class="mb-3 btn button btn-primary" data-bs-toggle="modal" data-bs-target="#addUser">
    Tambah User
  </button>
  @endif
  <div class="table-responsive">
    <table class="table table-hover table-stripped table-bordered text-center" id="users-datatable">
      <thead class="table-info">
        <tr>
          <th scope="row">No.</th>
          <th scope="row">Username</th>
          <th scope="row">Email</th>
          <th scope="row">Organisasi/Instansi</th>
          <th scope="row">Phone</th>
          <th scope="row">Address</th>
          <th scope="row">Role</th>
          <th scope="row">Status</th>
          @if (auth()->user()->role_id == 1)
            <th scope="row">Action</th>
          @endif
        </tr>
      </thead>
      <tbody>
        <!-- Data loaded via AJAX -->
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-end"></div>
</div>
</div>
@extends('dashboard.partials.addUserModal')
@extends('dashboard.partials.editUserModal')
<script>
// Function to handle edit user button click
function editUser(userId) {
    console.log("Edit user clicked for ID:", userId);
    
    // Set form action
    const formAction = "/dashboard/users/" + userId;
    $("#editformuser").attr("action", formAction);
    
    // Set the hidden ID field
    $("#id").val(userId);
    
    // Clear previous values and errors
    $("#editformuser")[0].reset();
    $(".invalid-feedback").hide();
    $(".is-invalid").removeClass("is-invalid");
    
    // Make AJAX request to get user data
    $.ajax({
        url: "/dashboard/users/" + userId + "/edit",
        type: "GET",
        dataType: "json",
        success: function(data) {
            console.log("User data loaded:", data);
            
            // Populate form fields
            $("#id").val(data.id);
            $("#name").val(data.name);
            $("#email").val(data.email);
            $("#organization").val(data.organization || '');
            $("#phone").val(data.phone || '');
            $("#address").val(data.address || '');
            $("#role_id").val(data.role_id);
            $("#status").val(data.status);
            
            // Clear password fields
            $("#password").val('');
            $("#password_confirmation").val('');
            
            // Show the modal
            $("#edituser").modal('show');
        },
        error: function(xhr, status, error) {
            console.error("Error loading user data:", error);
            alert("Gagal memuat data user. Silakan coba lagi.");
        }
    });
}

$(document).ready(function() {
  var table = $('#users-datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '/dashboard/users',
      type: 'GET'
    },
    dom: '<"row"<"col-sm-12 col-md-6 d-flex align-items-center"l><"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>>' +
         '<"row"<"col-sm-12"tr>>' +
         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-end"p>>',
    columns: [
      { data: null, orderable: false, searchable: false, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
      { data: 'name' },
      { data: 'email' },
      { data: 'organization' },
      { data: 'phone' },
      { data: 'address' },
      { data: 'role' },
      { data: 'status_badge', orderable: false, searchable: false, render: function(data){ return data; } },
      @if (auth()->user()->role_id == 1)
      { data: 'actions', orderable: false, searchable: false, render: function(data){ return data; } }
      @endif
    ],
    language: { url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/id.json' },
    order: [[1, 'asc']],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
    columnDefs: [{ targets: '_all', className: 'dt-head-center' }],
    responsive: true
  });
  // ensure left alignment for Show entries
  $('#users-datatable_length').css('text-align', 'left');
});
</script>
@endsection
