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
    /* Properti ini menempatkan item pertama di awal dan item terakhir di akhir */
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
      <a href="#">
        <div class="logo_container">
          <div class="logo">
            @if (substr_count(URL::current(), '/') == 5)
            <img src='{{Request::is('dashboard') ? '' : '../../'}}img/logokkrillv23.png' alt='LOGO' />
            @else
            <img src='{{Request::is('dashboard') ? '' : '../'}}img/logokkrillv23.png' alt='LOGO' />    
            @endif  
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
                <span class="text-dark">{{auth()->user()->name}} &#9660;</span> 
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="/dashboard/rooms"><i class="bi bi-layout-text-sidebar-reverse"></i> My Dashboard</a></li>
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
              <a href="/login">Login</a>
            </li>
            @endauth
          </ul>
        </div>
      </div>
  </div>
  </header>
  <!-- End Navbar -->