@extends('layouts.app')

@section('title', 'إحصائيات المنصة')

@section('content')
@php
    // F-05: كل الأرقام من Backend كما هي — لا حساب ولا ترتيب في الواجهة.
    $hasData = $stats['started_assessments'] > 0 || $stats['completed_assessments'] > 0;
    $fmt = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
    $topMax = empty($stats['top_recommendations']) ? 1 : max(1, $stats['top_recommendations'][0]['count']);
    // عرض التاريخ بالعربية (20 سبتمبر 2026) — حقول type=date تبقى ISO بطبيعتها.
    $arDate = static function (?string $d): string {
        return $d ? \Illuminate\Support\Carbon::parse($d)->locale('ar')->translatedFormat('j F Y') : '';
    };
@endphp

<div class="mx-auto max-w-3xl">

    {{-- ترويسة اللوحة --}}
    <header class="card-surface relative overflow-hidden">
        <div class="pointer-events-none absolute -end-12 -top-12 h-40 w-40 rounded-full bg-brand-400/10 blur-2xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -start-8 bottom-0 h-28 w-28 rounded-full bg-accent-500/10 blur-2xl" aria-hidden="true"></div>
        <div class="auth-head relative z-10">
            <span class="auth-head-icon">
                <x-ui.icon name="activity" class="h-5 w-5" />
            </span>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">إحصائيات المنصة</h1>
                <p class="mt-1 text-sm text-slate-500">مؤشرات التقييم كما يحتسبها النظام.</p>
            </div>
        </div>
    </header>

    {{-- التصفية الزمنية — شرائح فترات جاهزة بدل <input type=date>:
         العنصر الأصلي يعرض التاريخ بصيغة المتصفح (ISO مقلوبة في العربية) ولا يقبل تنسيقا بالـCSS.
         الشرائح روابط GET تستخدم نفس عقد from/to في Backend — بلا منطق أو حساب في الواجهة. --}}
    @php
        $today = now()->toDateString();
        $presets = [
            ['label' => 'كل الفترة', 'from' => null, 'to' => null],
            ['label' => 'هذا الشهر', 'from' => now()->startOfMonth()->toDateString(), 'to' => $today],
            ['label' => 'آخر 3 أشهر', 'from' => now()->subMonths(3)->startOfDay()->toDateString(), 'to' => $today],
            ['label' => 'هذه السنة', 'from' => now()->startOfYear()->toDateString(), 'to' => $today],
        ];
        $isActive = static fn (?string $f, ?string $t): bool => (string) $from === (string) $f && (string) $to === (string) $t;
    @endphp

    <nav aria-label="التصفية الزمنية" class="card-surface mt-6 p-5">
        <ul class="flex flex-wrap items-center gap-2">
            @foreach ($presets as $preset)
                @php $active = $isActive($preset['from'], $preset['to']); @endphp
                <li>
                    <a href="{{ $preset['from'] ? route('admin.statistics.index', ['from' => $preset['from'], 'to' => $preset['to']]) : route('admin.statistics.index') }}"
                       @if ($active) aria-current="true" @endif
                       class="inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold transition-colors {{ $active ? 'border-transparent bg-brand-600 text-white shadow-sm shadow-brand-600/25' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700' }}">{{ $preset['label'] }}</a>
                </li>
            @endforeach
        </ul>
        @if ($from || $to)
            <p class="mt-3 text-xs text-slate-500">
                النتائج معروضة للفترة:
                <span class="font-semibold text-slate-700">{{ $arDate($from) ?: 'البداية' }}</span>
                —
                <span class="font-semibold text-slate-700">{{ $arDate($to) ?: 'اليوم' }}</span>
            </p>
        @endif
    </nav>

    @if ($errors->any())
        <div class="mt-6">
            <x-ui.alert variant="danger">{{ $errors->first() }}</x-ui.alert>
        </div>
    @endif

    @if (! $hasData)
        <x-ui.empty-state class="mt-6" icon="clock"
            title="لا توجد بيانات بعد"
            description="لم يبدأ أي تقييم في هذه الفترة. ستظهر المؤشرات هنا فور تسجيل أول نشاط." />
    @else
        {{-- بطاقات المؤشرات --}}
        <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="card-surface card-lift p-5">
                <dt class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700" aria-hidden="true">
                        <x-ui.icon name="activity" class="h-4 w-4" />
                    </span>
                    تقييمات بدأت
                </dt>
                <dd class="mt-3 text-3xl font-extrabold text-slate-900 tabular-nums">{{ number_format($stats['started_assessments']) }}</dd>
            </div>
            <div class="card-surface card-lift p-5">
                <dt class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-700" aria-hidden="true">
                        <x-ui.icon name="check" class="h-4 w-4" />
                    </span>
                    تقييمات أُكملت
                </dt>
                <dd class="mt-3 text-3xl font-extrabold text-slate-900 tabular-nums">{{ number_format($stats['completed_assessments']) }}</dd>
            </div>
            <div class="card-surface card-lift p-5">
                <dt class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-accent-100 text-accent-600" aria-hidden="true">
                        <x-ui.icon name="target" class="h-4 w-4" />
                    </span>
                    نسبة الإكمال
                </dt>
                <dd class="mt-3 text-3xl font-extrabold text-brand-700 tabular-nums">{{ $fmt($stats['completion_rate']) }}%</dd>
            </div>
        </dl>

        {{-- أكثر التخصصات ترشيحًا --}}
        <section aria-labelledby="top-recs-title" class="card-surface mt-6">
            <h2 id="top-recs-title" class="text-lg font-bold text-slate-900">أكثر التخصصات ترشيحًا</h2>
            <p class="mt-1 text-sm text-slate-500">عدد مرات ظهور كل تخصص ضمن توصيات النتائج — بترتيب النظام.</p>

            @if (empty($stats['top_recommendations']))
                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    لا توجد ترشيحات مسجلة في هذه الفترة بعد.
                </p>
            @else
                <ol class="mt-5 space-y-4">
                    @foreach ($stats['top_recommendations'] as $i => $row)
                        <li class="flex items-center gap-4">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $i === 0 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600' }} text-xs font-extrabold"
                                  aria-hidden="true">{{ $i + 1 }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-3">
                                    <span class="truncate text-sm font-bold text-slate-900">{{ $row['name'] }}</span>
                                    <span class="shrink-0 text-sm font-bold text-slate-600 tabular-nums">{{ number_format($row['count']) }}</span>
                                </span>
                                <span class="mt-1.5 block h-2 w-full overflow-hidden rounded-full bg-slate-100"
                                      role="img" aria-label="{{ $row['name'] }}: {{ $row['count'] }} ترشيحًا">
                                    <span class="block h-full rounded-full {{ $i === 0 ? 'bg-brand-600' : 'bg-brand-300' }}"
                                          style="width: {{ round($row['count'] / $topMax * 100) }}%"></span>
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @endif

</div>
@endsection
