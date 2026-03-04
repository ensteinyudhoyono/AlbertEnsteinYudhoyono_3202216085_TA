<div class=" col-md-2 col-6 p-0 sidebar">
    <ul class="nav flex-column ">
      @if(auth()->user()->role_id == 1)
        {{-- Admin Menu --}}
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/admin">Daftar Security</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/users">Daftar User</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/rooms">Daftar Ruangan</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/rents">Daftar Peminjaman</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/temporaryRents">Daftar Peminjaman Sementara</a>
        </li>
      @elseif(auth()->user()->role_id == 2)
        {{-- Security Menu --}}
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/security">Daftar Loaner</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/rooms">Daftar Ruangan</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/rents">Daftar Peminjaman</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/temporaryRents">Daftar Peminjaman Sementara</a>
        </li>
      @elseif(auth()->user()->role_id == 3)
        {{-- Loaner Menu --}}
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/loaner">Peminjaman Saya</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dashboard/rooms">Daftar Ruangan</a>
        </li>
      @endif
    </ul>
</div>