@extends('layouts.main')

@section('container')
    <!-- Start Hero Section -->
    <div class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Sistem Peminjaman Ruangan Gereja Keluarga Kudus</h1>
                    <p class="hero-subtitle">Untuk memenuhi kebutuhan kegiatan dan pelayanan</p>
                    @guest
                    <div class="hero-buttons">
                        <a href="{{ route('login') }}" class="btn btn-login">Masuk</a>
                        {{-- <a href="{{ route('register') }}" class="btn btn-register">Daftar</a> --}}
                    </div>
                    @endguest
                    @auth
                    <div class="hero-buttons">
                        <a href="{{ url('dashboard/rooms') }}" class="btn btn-login">Mulai Peminjaman Ruangan</a>
                    </div>
                    @endauth
                </div>
                <div class="hero-logo">
                    <div class="logo-circle">
                        <div class="logo-content">
                            <div class="logo-image">
                                @auth
                                    <form action="/logout" method="post" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="logo-button" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                            <img src="/img/logokk.png" alt="Logo Keluarga Kudus Pontianak" class="logo-img">
                                        </button>
                                    </form>
                                @else
                                    <img src="/img/logokk.png" alt="Logo Keluarga Kudus Pontianak" class="logo-img">
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Hero Section -->

    <!-- Start Footer -->
    <footer class="footer-section">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3 class="footer-title">Ada Pertanyaan ? <br> Hubungi Kami</h3>
                    <ul class="footer-links">
                        <li><a href="mailto:keluargakuduspnk@n   " class="text-white me-3">Email</a></li>
                        <li><a href="https://wa.me/6283141732818" class="text-white me-3">Whatsapp</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">Pintasan</h3>
                    <ul class="footer-links">
                        <li><a href="help" class="text-white me-3">Bantuan</a></li>
                        <li><a href="about" class="text-white me-3">Tentang Kami</a></li>
                        <li><a href="https://keluargakuduskap.or.id/" class="text-white me-3">Website Paroki</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">Media Sosial</h3>
                    <div class="social-links">
                        <a href="https://www.facebook.com/parokikeluargakudus.pontianak/" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.instagram.com/keluargakudus.pnk/" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com/@parokikeluargakuduskotabar4604" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- End Footer -->
@endsection