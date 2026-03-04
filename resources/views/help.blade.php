@extends('layouts.main')

@section('container')
    {{-- Blok style ini sudah benar, tidak perlu diubah --}}
    <style>
        .list-tahapan ul li {
            font-size: 1.6rem; /* Ukuran font yang lebih besar */
            line-height: 1.6;  /* Jarak antar baris agar mudah dibaca */
        }
    </style>

    <div class="hero hero-bantuan">
        <div class="hero__inner container">
            <div class="hero-description m-auto p-5">
                <h2>PETUNJUK PENGGUNAAN SISTEM PEMINJAMAN RUANGAN GEREJA KELUARGA KUDUS</h2>
            </div>
        </div>
    </div>
    <div class="my-4 container">
        <h2>TAHAPAN PEMINJAMAN RUANGAN</h2>

        {{-- Gunakan div dengan class yang sesuai dengan target CSS Anda --}}
        <div class="list-tahapan">
            <ul>
                <li>Untuk melakukan peminjaman, Anda diharuskan melakukan login terlebih dahulu sesuai dengan username dan password yang telah dibuat oleh admin.</li>
                <li>Jika ingin mengetahui ruangan yang tersedia, silakan menuju ke menu Daftar Ruangan. Secara default, tampilan akan langsung ke menu Daftar Ruangan.</li>
                <li>Jika ingin meminjam ruangan, silakan menuju ke menu Daftar Ruangan. Cek ketersediaan ruangan dan daftar peminjam pada sub-menu Daftar Ruangan dan Daftar Peminjam. Jika ruangan tersedia, silakan klik tombol Pinjam dan isi form peminjaman. </li>
                <li>Pastikan data yang Anda masukkan sudah sesuai. Jika sudah sesuai, klik Kirim.</li>
                <li>Proses pengajuan peminjaman sedang diproses, silakan tunggu pemberitahuan lebih lanjut.</li>
                <li>Untuk mengecek status peminjaman apakah diterima atau ditolak, silakan menuju sub-menu Daftar Peminjaman.</li>
            </ul>
        </div>
    </div>
    @endsection