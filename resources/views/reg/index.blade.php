@extends('layouts.main')

@section('container')
<!-- Start Hero -->
<div class="hero hero-login">
    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('success')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    <div class="hero__inner container d-flex flex-wrap justify-content-center">
        <div class="left-login">
            <img src="img/logokk.png" alt="logo">
        </div>
        <div class="right-login">
            <div class="form">
                <h3 class="title-form">Register</h3>
                <form action="/register" method="post" class="form-input">
                    @csrf
                    <input type="text" placeholder="Nama Lengkap @error('name') is-invalid @enderror" autofocus required
                        value="{{old('name')}}" class="input-form" name="name" id="name" />
                    @error('name')
                    <div class="invalid-feedback" style="display: block">
                        {{$message}}
                    </div>
                    @enderror



                    <input type="email" placeholder="Email @error('email') is-invalid @enderror" required
                        value="{{old('email')}}" class="input-form" name="email" id="email" />
                    @error('email')
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i> {{$message}}
                    </div>
                    @enderror

                    <input type="text" placeholder="Organisasi/Instansi (Opsional) @error('organization') is-invalid @enderror"
                        value="{{old('organization')}}" class="input-form" name="organization" id="organization" />
                    @error('organization')
                    <div class="invalid-feedback" style="display: block">
                        {{$message}}
                    </div>
                    @enderror

                    <input type="text" placeholder="Nomor Telepon @error('phone') is-invalid @enderror" required
                        value="{{old('phone')}}" class="input-form" name="phone" id="phone" />
                    @error('phone')
                    <div class="invalid-feedback" style="display: block">
                        {{$message}}
                    </div>
                    @enderror

                    <textarea placeholder="Alamat @error('address') is-invalid @enderror" required
                        class="input-form" name="address" id="address" rows="3">{{old('address')}}</textarea>
                    @error('address')
                    <div class="invalid-feedback" style="display: block">
                        {{$message}}
                    </div>
                    @enderror

                    <input type="password" placeholder="Password @error('password') is-invalid @enderror" required
                        class="input-form" name="password" id="password" />
                    @error('password')
                    <div class="invalid-feedback" style="display: block">
                        {{$message}}
                    </div>
                    @enderror

                    <input type="password" placeholder="Konfirmasi Password @error('password_confirmation') is-invalid @enderror" required
                        class="input-form" name="password_confirmation" id="password_confirmation" />
                    @error('password_confirmation')
                    <div class="invalid-feedback" style="display: block">
                        {{$message}}
                    </div>
                    @enderror

                    <button type="submit" class="button-submit">Register</button>
                </form>
                <div class="text-center mt-3">
                    <p>Sudah punya akun? <a href="/login" class="text-decoration-none">Login di sini</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Hero -->
@endsection 