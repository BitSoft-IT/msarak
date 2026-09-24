<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <title>@hasSection('title')@yield('title') | @endifمسارك</title>

    {{-- الخط العربي المعتمد: IBM Plex Sans Arabic ذو الرصانة التقنية والوضوح العالي في الواجهات البرمجية --}}
    <link rel="preload" as="font" type="font/woff2" href="/fonts/ibm-plex-sans-arabic-arabic-400-normal.woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-900 antialiased font-sans">
    <a href="#main" class="skip-link">تخطي إلى المحتوى الرئيسي</a>

    <header class="sticky top-0 z-40 bg-white/85 backdrop-blur-md">
        <div class="container-page flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-3">
            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center gap-2 text-xl font-bold text-brand-700 transition-colors hover:text-brand-800">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-extrabold text-white shadow-sm shadow-brand-600/30" aria-hidden="true">م</span>
                مسارك
            </a>

            <nav aria-label="التنقل الرئيسي">
                <ul class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <li>
                        <a href="{{ url('/') }}"
                           class="nav-link inline-flex min-h-11 items-center rounded-lg px-2.5 font-medium text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                           @if (request()->is('/')) aria-current="page" @endif>الرئيسية</a>
                    </li>
                    <li>
                        <a href="{{ route('specializations.index') }}"
                           class="nav-link inline-flex min-h-11 items-center rounded-lg px-2.5 font-medium text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                           @if (request()->routeIs('specializations.*')) aria-current="page" @endif>التخصصات</a>
                    </li>
                    @auth
                        @if (auth()->user()->role === 'student')
                            <li>
                                <a href="{{ route('assessment.intro') }}"
                                   class="nav-link inline-flex min-h-11 items-center rounded-lg px-2.5 font-medium text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                   @if (request()->routeIs('assessment.*')) aria-current="page" @endif>استكشاف ميولك</a>
                            </li>
                        @endif
                    @endauth
                    {{-- روابط الصفحات اللاحقة تضاف هنا --}}
                </ul>
            </nav>
        </div>
        {{-- خط الهوية السفلي — نفس شريط البطاقات (brand→accent) بدل الحد المسطح --}}
        <div class="h-0.5 bg-gradient-to-l from-brand-500 via-brand-600 to-accent-500/70 opacity-70" aria-hidden="true"></div>
    </header>

    <main id="main" tabindex="-1" class="container-page flex-1 break-words py-12 lg:py-16">
        <x-ui.flash class="mb-6" />
        @yield('content')
    </main>

    <footer class="mt-auto">
        {{-- شريط الهوية العلوي — مرآة شريط الترويسة السفلي --}}
        <div class="h-1 bg-gradient-to-l from-brand-500 via-brand-600 to-accent-500" aria-hidden="true"></div>
        <div class="bg-gradient-to-br from-brand-800 to-brand-900 text-white">
            <div class="container-page flex flex-wrap items-center justify-between gap-x-8 gap-y-6 py-10">
                <div class="max-w-md">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-sm font-extrabold text-white ring-1 ring-inset ring-white/25" aria-hidden="true">م</span>
                        <span class="text-xl font-bold">مسارك</span>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-brand-100/90">
                        منصة للتوجيه الأكاديمي والمهني لطلاب الثانوية في اليمن
                    </p>
                </div>

                <nav aria-label="روابط التذييل">
                    <ul class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-medium">
                        <li>
                            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-brand-100/90 transition-colors hover:bg-white/10 hover:text-white">الرئيسية</a>
                        </li>
                        <li>
                            <a href="{{ route('specializations.index') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-brand-100/90 transition-colors hover:bg-white/10 hover:text-white">التخصصات</a>
                        </li>
                        @auth
                            @if (auth()->user()->role === 'student')
                                <li>
                                    <a href="{{ route('assessment.intro') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-brand-100/90 transition-colors hover:bg-white/10 hover:text-white">استكشاف ميولك</a>
                                </li>
                            @endif
                            @if (auth()->user()->role === 'admin')
                                <li>
                                    <a href="{{ route('admin.statistics.index') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-brand-100/90 transition-colors hover:bg-white/10 hover:text-white">الإحصائيات</a>
                                </li>
                            @endif
                        @endauth
                        @guest
                            <li>
                                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-brand-100/90 transition-colors hover:bg-white/10 hover:text-white">تسجيل الدخول</a>
                            </li>
                            <li>
                                <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-brand-100/90 transition-colors hover:bg-white/10 hover:text-white">إنشاء حساب</a>
                            </li>
                        @endguest
                    </ul>
                </nav>
            </div>
        </div>
    </footer>
</body>
</html>
