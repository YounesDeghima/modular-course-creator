<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Modular-Course-Creator</title>
    <link rel="stylesheet" href="{{asset('css/admin-layout.css')}}">
    <link rel="stylesheet" href="{{ asset('vendors/katex/katex.min.css') }}">

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ asset('vendors/katex/katex.min.js') }}"></script>
    <script src="{{ asset('vendors/katex/contrib/auto-render.min.js') }}"></script>

    @yield('css')


</head>

<body>
<header>
    @yield("progress-bar")
    <nav style="justify-content: space-between">
        <div class="nav-left">
            <img src="{{asset('images/logo/logo.png')}}" class="logo" alt="logo">
            <a href='{{route('admin.main')}}' data-item='Home'>Home</a>
            <a href='{{route('admin.dashboard')}}' data-item='About'>Users</a>
            <a href='{{route('admin.courses.index')}}' data-item='Projects'>Modular site</a>
            <a href="{{route('admin.preview.courses')}}" data-item='preview'>Preview</a>
            <a href="{{route('admin.ai.panel')}}" data-item='AI Panel' style="color: #4f46e5; font-weight: bold;">
                ✨ AI Generator
            </a>

        </div>

        <div class="nav-right">


            @yield('right-side')
            <div class="user-badge" id="right-side">
                <div class="user-avatar">{{substr($name, 0, 1)}}</div>
                <span class="user-id">{{ $name }}</span>

                <div class="popup" id="popup">
                    <p><span>Name</span> {{ $name }}</p>
                    <p><span>Email</span> {{ $email }}</p>
                    <p><span>ID</span> {{ $id }}</p>
                    <button class="theme-toggle" id="themeToggle" title="Toggle theme">
                        <span class="icon-moon">🌙</span>
                        <span class="icon-sun">☀️</span>
                    </button>
                    <form action="" method="post">
                        <input type="submit" value="Delete user" name="delete-user" id="delete-user">
                    </form>
                    <a href="{{route('login_page')}}" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </nav>
</header>
<div class="middle">
    <div class="side-bar">

        <button class="sidebar-toggle" id="sidebarToggle">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>

        <div class="sidebar-content">
            @yield('sidebar-elements')
        </div>

    </div>
    <main>
        @yield('navigation')
        @yield('main')
    </main>
    @hasSection('right-sidebar')
        <div class="right-side-bar">
            @yield('right-sidebar')
        </div>
    @endif
</div>




@yield('js')
<script src="{{ asset('vendors/chart.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="{{ asset('js/function.js') }}"></script>
<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarContent = document.querySelector('.sidebar-content');
    const sidebar = document.querySelector('.side-bar');

    // Set correct initial state on load


    sidebarToggle.addEventListener('click', () => {
        const isCollapsed = sidebar.classList.toggle('collapsed');

        if (isCollapsed) {
            sidebarContent.style.display = 'none';
        } else {
            sidebarContent.style.display = 'flex';
        }
    });

    const userBadge = document.getElementById('right-side');
    const popup = document.getElementById('popup');

    userBadge.addEventListener('click', (e) => {
        e.stopPropagation();
        popup.classList.toggle('open');
    });

    document.addEventListener('click', () => {
        popup.classList.remove('open');
    });


    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    // Restore saved preference
    if (localStorage.getItem('theme') === 'dark') {
        html.setAttribute('data-theme', 'dark');
    }

    themeToggle.addEventListener('click', () => {
        const isDark = html.getAttribute('data-theme') === 'dark';
        if (isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        }
    });
</script>



</body>
</html>
