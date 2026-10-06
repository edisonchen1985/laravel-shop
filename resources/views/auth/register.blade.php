<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
</head>
<body>
    @include('partials.navigation')

    <main>
        <h1>Create an account</h1>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name">

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">

            <label for="phone">Phone (optional)</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel">

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </main>
</body>
</html>
