<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in</title>
</head>
<body>
    @include('partials.navigation')

    <main>
        <h1>Log in</h1>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">

            <button type="submit">Log in</button>
        </form>

        <p>Don't have an account? <a href="{{ route('register') }}">Register</a></p>
    </main>
</body>
</html>
