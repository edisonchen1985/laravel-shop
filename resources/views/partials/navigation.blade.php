<nav aria-label="Main navigation">
    <a href="{{ url('/') }}">Home</a>
    <a href="{{ route('products.index') }}">Products</a>

    @auth
        <a href="{{ route('cart.index') }}">Cart</a>
        <a href="{{ route('orders.index') }}">My Orders</a>
        @if (auth()->user()->is_admin)
            <a href="{{ route('admin.products.index') }}">Admin</a>
        @endif
        <form method="POST" action="{{ route('logout') }}" style="display: inline">
            @csrf
            <button type="submit">Log out</button>
        </form>
    @else
        <a href="{{ route('login') }}">Log in</a>
        <a href="{{ route('register') }}">Register</a>
    @endauth
</nav>
