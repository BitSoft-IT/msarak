<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') | @endifمسارك</title>

    {{-- الخط العربي المعتمد: IBM Plex Sans Arabic ذو الرصانة التقنية والوضوح العالي في الواجهات البرمجية --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-900 antialiased font-sans">
    <a href="#main" class="skip-link">تخطي إلى المحتوى الرئيسي</a>

    <header class="border-b border-slate-200 bg-white">
        <div class="container-page flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-3">
            <a href="{{ url('/') }}" class="text-xl font-bold text-brand-700">مسارك</a>

            <nav aria-label="التنقل الرئيسي">
                <ul class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <li>
                        <a href="{{ url('/') }}"
                           class="inline-flex min-h-11 items-center px-1 font-medium hover:text-brand-700"
                           @if (request()->is('/')) aria-current="page" @endif>الرئيسية</a>
                    </li>
                    <li>
                        <a href="{{ route('specializations.index') }}"
                           class="inline-flex min-h-11 items-center px-1 font-medium hover:text-brand-700"
                           @if (request()->routeIs('specializations.*')) aria-current="page" @endif>التخصصات</a>
                    </li>
                    @auth
                        @if (auth()->user()->role === 'student')
                            <li>
                                <a href="{{ route('assessment.intro') }}"
                                   class="inline-flex min-h-11 items-center px-1 font-medium hover:text-brand-700"
                                   @if (request()->routeIs('assessment.*')) aria-current="page" @endif>استكشاف ميولك</a>
                            </li>
                        @endif
                    @endauth
                    {{-- روابط الصفحات اللاحقة تضاف هنا --}}
                </ul>
            </nav>
        </div>
    </header>

    <main id="main" tabindex="-1" class="container-page flex-1 break-words py-8 sm:py-12">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="container-page py-4 text-sm text-slate-600">مسارك</div>
    </footer>
</body>
</html>