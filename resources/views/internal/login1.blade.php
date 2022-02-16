@include('internal/header')

<main role="main" class="container">
    <div>
        <form method="POST" action="/internal/login">
            @csrf
            <div>
                <label for="username">Имя пользователя</label>
                <input type="string" name="username" id="username" value="{{ old('username') }}" required>
                @error('username')
                <p>{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password">Пароль</label>
                <input type="password" name="password" id="password" required>
                @error('password')
                <p>{{ $message }}</p>
                @enderror
            </div>
            <div>
                <button type="submit">Войти</button>
            </div>
        </form>
    </div>
</main>

@include('internal/footer')
