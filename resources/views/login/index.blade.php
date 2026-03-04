@extends('layouts.main')

@section('container')
<!-- Start Hero -->
<div class="hero hero-login">
    @if(session()->has('loginError'))
    <div class="alert alert-danger alert-dismissible fade show" style="margin-top: 50px" role="alert">
        {{session('loginError')}}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    <div class="hero__inner container d-flex flex-wrap justify-content-center">
        <div class="left-login">
            <img src="img/logokk.png" alt="logo">

        </div>
        <div class="right-login">
            <div class="form">
                <h3 class="title-form">Login</h3>
                <form action="/login" method="post" class="form-input">
                    @csrf
                    <input type="email" placeholder="email @error('email') is-invalid @enderror" autofocus required
                        value="{{old('email')}}" class="input-form" name="email" id="floatingInput" />
                    @error('email')
                    <div class="invalid-feedback" style="display: block">
                        {{$message}}
                    </div>
                    @enderror
                    <input type="password" placeholder="password @error('password') is-invalid @enderror" autofocus
                        required value="{{old('password')}}" class="input-form" name="password" id="floatingInput" />

                    <div class="button-container">
                        <button type="submit" class="button-submit">Masuk</button>
                    </div>
                </form>
                {{-- <div class="register-link">
                    <p>Belum punya akun? <a href="/register" class="text-decoration-none">Buat di sini</a></p>
                </div> --}}
            </div>
        </div>
    </div>
</div>
<!-- End Hero -->
@endsection
