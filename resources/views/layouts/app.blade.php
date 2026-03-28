<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'My Laravel App' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-container">
    @include('partials._header')

    <div class="main-wrapper">
        @include('partials._sidebar')

        <main>
            {{ $slot }}
        </main>
    </div>

    @include('partials._footer')
</div>
</body>
</html>
