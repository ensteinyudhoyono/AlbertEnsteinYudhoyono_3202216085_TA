  @extends('layouts.main')

  @section('container')

  {{-- Tambahkan blok style ini untuk memperbesar font deskripsi --}}
  <style>
      .section-description {
          font-size: 1.6rem; /* Ubah nilai ini jika ingin lebih besar/kecil */
          line-height: 1.6;  /* Menambah jarak antar baris agar lebih mudah dibaca */
      }
  </style>

  <div class="hero hero-about">
      <div class="hero__inner container">
          <div class="hero-description text-center">
              <h2>TENTANG SISTEM PEMINJAMAN RUANGAN GEREJA KELUARGA KUDUS</h2>
              <p>
                  Aplikasi <span><b>Sistem Peminjaman Ruangan Gereja Keluarga Kudus</b></span> merupakan aplikasi real-time berbasis website. 
                  Aplikasi ini menampilkan ketersediaan ruangan serta informasi mendetail lainnya mengenai ruangan yang sedang dan akan dipinjam. 
                  Aplikasi ini ditujukan bagi instansi, organisasi,   dan kelompok masyarakat yang ingin menggunakan ruangan yang ada di lingkungan Gereja Keluarga Kudus Pontianak.
              </p>
          </div>
      </div>
  </div>
  <div class="description-about container-fluid"> {{-- ubah ke container-fluid agar full width --}}
    <div class="upper-content d-flex flex-wrap justify-content-center my-4">
        <div class="section section-1 mx-3 flex-fill" style="min-width: 45%;">
            <h1 class="title-section text-center">LATAR BELAKANG</h1>
            <div class="section-description">
                Salah satu sarana dan prasarana di lingkungan gereja adalah ruangan serbaguna. 
                Akan tetapi, kurangnya pembukuan dalam penggunaan ruangan membuat pihak pengelola gereja sulit untuk mengatur penggunaan ruangan. 
                Untuk mengefektifkan penjadwalan ruangan dan pembukuan, 
                maka dibuatlah aplikasi web berupa peminjaman ruangan di lingkungan gereja khususnya di lingkungan gereja Keluarga Kudus Pontianak.
            </div>
        </div>
        <div class="section section-1 mx-3 flex-fill" style="min-width: 45%;">
            <h1 class="title-section text-center">TUJUAN</h1>
            <div class="section-description">
                Aplikasi sistem peminjaman ruangan ini bertujuan untuk meningkatkan efisiensi proses peminjaman ruangan yang akan menghemat waktu dan tenaga, baik bagi pengurus gereja maupun umat.
                Selain itu, mengurangi kesalahan yang sering terjadi pada pencatatan manual, karena sistem digital akan meminimalkan resiko kesalahan manusia dalam proses pencatatan dan verifikasi peminjaman.
                Umat dapat melihat ketersediaan ruangan secara real-time dan melakukan peminjaman dengan mudah melalui perangkat mereka.
                Tentu hal ini mempermudah peminjam untuk meminjam ruangan tanpa perlu mengecek ruangan satu persatu.
            </div>
        </div>
    </div>

      <div class="lower-content">
          <h1 class="text-center">LOKASI</h1>
          <div class="mapouter m-auto my-4 mb-5">
              <div class="gmap_canvas">
                  <iframe width="600" height="500" id="gmap_canvas" src="https://maps.google.com/maps?q=Gereja%20Katolik%20Keluarga%20Kudus%20Pontianak&t=&z=16&ie=UTF8&iwloc=&output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>
                  <style>.mapouter{position:relative;text-align:right;height:500px;width:600px;}</style>
                  <style>.gmap_canvas {overflow:hidden;background:none!important;height:500px;width:600px;}</style>
              </div>
          </div>
      </div>
  </div>
  @endsection