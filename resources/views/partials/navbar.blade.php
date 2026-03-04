<!-- Start Navbar -->
<style>
.header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 0 50px !important;
    width: 100% !important;
}

.header .header_content {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    width: 100% !important;
}

.header .logo_container {
    display: flex !important;
    align-items: center !important;
}

.header .main_nav_container {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
    margin-left: auto !important;
}

.header ul.main_nav_list {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 30px !important;
    justify-content: flex-end !important;
}

.header .main_nav_item {
    display: inline-block !important;
    margin: 0 !important;
}

.header .main_nav_item a {
    text-decoration: none !important;
    color: inherit !important;
}

@media screen and (max-width: 1280px) {
    .header {
        padding: 0 30px !important;
    }
    .header ul.main_nav_list {
        gap: 25px !important;
    }
}

@media only screen and (max-width: 480px) {
    .header {
        padding: 0 15px !important;
    }
    .header ul.main_nav_list {
        gap: 15px !important;
    }
}
</style>
<header class="header">
    <div class="header_content">
      <a href="dashboard/rooms">
        <div class="logo_container">
          <div class="logo">
            <img src="img/logokkrillv23.png" alt="LOGO" />
          </div>
        </div>
      </a>
      <div class="main_nav_container">
        <div class="main_nav">
          <ul class="main_nav_list">
            <li class="main_nav_item {{Request::is('') ? '' : 'active'}}">
              <a href="/">Beranda</a>
            </li>
            <li class="main_nav_item {{Request::is('about') ? 'active' : ''}}">
              <a href="/about">Tentang</a>
            </li>
            <li class="main_nav_item {{Request::is('help') ? 'active' : ''}}">
              <a href="/help">Bantuan</a>
            </li>
            @auth
            <li class="main_nav_item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Welcome back, {{auth()->user()->name}}
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                @if(auth()->user()->role_id == 1)
                  <li><a class="dropdown-item" href="/dashboard/admin"><i class="bi bi-shield-check"></i> Admin Dashboard</a></li>
                @elseif(auth()->user()->role_id == 2)
                  <li><a class="dropdown-item" href="/dashboard/security"><i class="bi bi-shield-lock"></i> Security Dashboard</a></li>
                @elseif(auth()->user()->role_id == 3)
                  <li><a class="dropdown-item" href="/dashboard/loaner"><i class="bi bi-person-check"></i> Loaner Dashboard</a></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="/logout" method="post">
                      @csrf
                      <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Logout</button>
                  </form>  
              </ul>
            </li>
            @else
            <li class="main_nav_item {{Request::is('login') ? 'active' : ''}}">
              <a href="{{ route('login') }}">Login</a>
            {{-- </li>
            <li class="main_nav_item {{Request::is('register') ? 'active' : ''}}">
              <a href="{{ route('register') }}">Register</a>
            </li> --}}
            @endauth
          </ul>
        </div>
      </div>
  </div>
  </header>
  <!-- End Navbar -->