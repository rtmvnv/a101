@include('internal/header')

<style>
    html,
    body {
        height: 100%;
    }

    body {
        display: flex;
        align-items: center;
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
    }

    .form-signin {
        width: 100%;
        max-width: 330px;
        padding: 15px;
        margin: auto;
    }

    .form-signin .checkbox {
        font-weight: 400;
    }

    .form-signin .form-floating:focus-within {
        z-index: 2;
    }

    .form-signin input[type="email"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }

    .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }

    .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
    }

    @media (min-width: 768px) {
        .bd-placeholder-img-lg {
            font-size: 3.5rem;
        }
    }
</style>

<main class="form-signin">
    <form method="POST" action="/internal/login">
        @csrf
        <div class="form-floating">
            <input type="username" class="form-control" id="username" name="username" @unless(empty(old('username'))) value="{{ old('username') }}" @endunless>
            <label for="username">Имя пользователя</label>
            @error('username')
            <p>{{ $message }}</p>
            @enderror
        </div>
        <div class="form-floating">
            <input type="password" class="form-control" id="password" name="password">
            <label for="password">Пароль</label>
            @error('password')
            <p>{{ $message }}</p>
            @enderror
        </div>

        <div class="checkbox mb-3">
            <label>
                <input type="checkbox" value="remember-me"> Запомнить меня
            </label>
        </div>
        <button class="w-100 btn btn-lg btn-primary" type="submit">Войти</button>
    </form>
</main>

@include('internal/footer')
