{{-- PWA: manifest, ikonkalar, mavzu rangi. Barcha layoutlarning <head> qismida. --}}
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#2F4B33">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Chorvadorim">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
{{-- Joriy foydalanuvchi: o'zgarsa service worker sahifa keshini tozalaydi (public/sw.js) --}}
<meta name="chv-user" content="{{ auth()->id() ?? '' }}">
