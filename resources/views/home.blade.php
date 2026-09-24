@extends('layouts.app')

@section('title', 'الرئيسية')

@section('content')
    {{-- Hero --}}
    <section aria-labelledby="home-title" class="relative mt-4 overflow-hidden rounded-card border border-brand-100/70 bg-gradient-to-br from-white via-brand-50/50 to-brand-100/40 shadow-card sm:mt-8">
        <div class="pointer-events-none absolute -end-24 -top-24 h-72 w-72 rounded-full bg-brand-400/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -start-16 bottom-0 h-56 w-56 rounded-full bg-accent-500/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative z-10 mx-auto max-w-2xl px-6 py-12 text-center sm:px-10 sm:py-16">
            <span class="inline-flex items-center gap-2 rounded-full border border-brand-200/80 bg-white/90 px-4 py-1.5 text-xs font-bold text-brand-700 shadow-xs backdrop-blur-xs">
                <span class="h-2 w-2 rounded-full bg-accent-500" aria-hidden="true"></span>
                مسارك
            </span>

            <h1 id="home-title" class="mt-6 text-4xl font-extrabold text-slate-900 sm:text-5xl">
                مسارك
            </h1>

            <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-slate-700">
                منصة للتوجيه الأكاديمي والمهني لطلاب الثانوية في اليمن.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary min-w-40 text-base">إنشاء حساب</a>
                    <a href="{{ route('login') }}" class="btn btn-secondary min-w-40 text-base">تسجيل الدخول</a>
                @else
                    @if (auth()->user()->role === 'student')
                        <a href="{{ route('assessment.intro') }}" class="btn btn-primary min-w-40 text-base">استكشاف ميولك</a>
                    @endif
                    <a href="{{ route('specializations.index') }}" class="btn btn-secondary min-w-40 text-base">التخصصات</a>
                @endguest
            </div>
        </div>
    </section>

    {{-- بطاقات: نصوص معتمدة من صفحات الواجهات --}}
    <div class="mt-8 flex flex-wrap justify-center gap-5">
        <a href="{{ route('specializations.index') }}" class="card-surface card-lift group w-full max-w-sm flex-1 basis-72">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white">
                <x-ui.icon name="info" class="h-5 w-5" />
            </span>
            <h2 class="mt-4 text-lg font-bold text-slate-900">دليل التخصصات</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                تصفح التخصصات الأكاديمية المتاحة، واطّلع على تفاصيل كل تخصص، أو قارن بين تخصصين.
            </p>
        </a>

        @auth
            @if (auth()->user()->role === 'student')
                <a href="{{ route('assessment.intro') }}" class="card-surface card-lift group w-full max-w-sm flex-1 basis-72">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white">
                        <x-ui.icon name="clock" class="h-5 w-5" />
                    </span>
                    <h2 class="mt-4 text-lg font-bold text-slate-900">استكشاف ميولك</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        يساعدك هذا التقييم على استكشاف الأنشطة والمجالات التي قد تستمتع بها أو ترغب في التعرف إليها أكثر.
                    </p>
                </a>
            @endif
        @else
            <a href="{{ route('register') }}" class="card-surface card-lift group w-full max-w-sm flex-1 basis-72">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white">
                    <x-ui.icon name="check" class="h-5 w-5" />
                </span>
                <h2 class="mt-4 text-lg font-bold text-slate-900">استكشاف ميولك</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    يساعدك هذا التقييم على استكشاف الأنشطة والمجالات التي قد تستمتع بها أو ترغب في التعرف إليها أكثر.
                </p>
            </a>

            <a href="{{ route('login') }}" class="card-surface card-lift group w-full max-w-sm flex-1 basis-72">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white">
                    <x-ui.icon name="arrow-end" class="h-5 w-5" />
                </span>
                <h2 class="mt-4 text-lg font-bold text-slate-900">تسجيل الدخول</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    سجّل دخولك لمتابعة رحلتك في اكتشاف التخصص الأنسب لك.
                </p>
            </a>
        @endauth
    </div>

    {{-- تنويه إرشادي --}}
    <p class="mx-auto mt-10 max-w-2xl text-center text-sm leading-relaxed text-slate-500">
        التقييم أداة استكشافية وإرشادية، وليس اختبارًا للقدرات أو تشخيصًا للشخصية، ولا يحدد تخصصًا أو مهنة واحدة مناسبة لك بشكل نهائي.
    </p>
@endsection
