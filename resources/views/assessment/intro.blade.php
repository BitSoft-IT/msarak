@extends('layouts.app')

@section('title', 'استكشاف ميولك')

@section('content')
<div class="mx-auto max-w-4xl space-y-8">

    {{-- 1. Hero Card: الهوية البصرية الرئيسية للتقييم --}}
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-brand-50/20 to-brand-100/30 p-6 sm:p-10 lg:p-12 shadow-sm transition-all hover:shadow-md">
        {{-- تأثيرات ضوئية خلفية ناعمة --}}
        <div class="pointer-events-none absolute -end-24 -top-24 h-80 w-80 rounded-full bg-brand-500/10 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -start-20 bottom-0 h-60 w-60 rounded-full bg-brand-400/10 blur-2xl" aria-hidden="true"></div>

        <div class="relative z-10 text-center max-w-2xl mx-auto">
            {{-- شارة المقدمة مع نقطة حية نابضة --}}
            <div class="inline-flex items-center gap-2 rounded-full border border-brand-200/80 bg-white/90 px-4 py-1.5 text-xs font-bold text-brand-700 shadow-xs backdrop-blur-xs">
                <span class="flex h-2 w-2 rounded-full bg-brand-600 animate-pulse" aria-hidden="true"></span>
                <span>رحلة التوجيه الأكاديمي والمهني</span>
            </div>

            <h1 class="mt-5 text-3xl font-extrabold text-slate-900 sm:text-4xl lg:text-5xl">
                استكشاف ميولك
            </h1>

            <p class="mt-4 text-base font-medium leading-relaxed text-slate-700 sm:text-lg">
                يساعدك هذا التقييم على استكشاف الأنشطة والمجالات التي قد تستمتع بها أو ترغب في التعرف إليها أكثر.
            </p>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                التقييم أداة استكشافية وإرشادية، وليس اختبارًا للقدرات أو تشخيصًا للشخصية، ولا يحدد تخصصًا أو مهنة واحدة مناسبة لك بشكل نهائي.
            </p>

            {{-- شريط ميزات التقييم السريع --}}
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4 text-start">
                <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3.5 backdrop-blur-xs shadow-2xs">
                    <div class="flex items-center gap-2 text-brand-600 mb-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-xs font-bold text-slate-700">المدة</span>
                    </div>
                    <span class="text-xs text-slate-500">10 – 15 دقيقة</span>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3.5 backdrop-blur-xs shadow-2xs">
                    <div class="flex items-center gap-2 text-brand-600 mb-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                        <span class="text-xs font-bold text-slate-700">المواقف</span>
                    </div>
                    <span class="text-xs text-slate-500">18 موقفًا واقعيًا</span>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3.5 backdrop-blur-xs shadow-2xs">
                    <div class="flex items-center gap-2 text-brand-600 mb-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span class="text-xs font-bold text-slate-700">الحفظ</span>
                    </div>
                    <span class="text-xs text-slate-500">فوري وتلقائي</span>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3.5 backdrop-blur-xs shadow-2xs">
                    <div class="flex items-center gap-2 text-brand-600 mb-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-xs font-bold text-slate-700">النتيجة</span>
                    </div>
                    <span class="text-xs text-slate-500">توصيات موجهة</span>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. تنبيه استئناف الجلسة إن وُجدت بتصميم بارز جذاب --}}
    @if ($activeSession)
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-600 to-brand-700 p-6 sm:p-8 text-white shadow-lg shadow-brand-600/15" role="status">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-xs">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">لديك تقييم غير مكتمل</h2>
                        <p class="mt-1 text-sm text-brand-100 leading-relaxed">
                            لديك تقييم غير مكتمل. تم حفظ تقدمك، ويمكنك المتابعة من حيث توقفت.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('assessment.sessions.store') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-brand-700 shadow-md hover:bg-brand-50 transition-all">
                        <span>متابعة التقييم</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- 3. دليل رحلة الإجابة ونموذج الاستجابة المعتمد --}}
    <section aria-labelledby="guide-heading" class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-10 shadow-sm space-y-8">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-brand-600">إرشادات المشاركة</span>
            <h2 id="guide-heading" class="mt-1 text-2xl font-bold text-slate-900">طريقة الإجابة</h2>
        </div>

        {{-- المبدأ الأساسي --}}
        <div class="rounded-2xl border border-brand-100 bg-brand-50/40 p-5 sm:p-6">
            <div class="flex items-start gap-3.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-sm shadow-xs">1</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">اختر تصرفًا واحدًا يشبهك أكثر</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-700">
                        في كل موقف، اقرأ التصرفات المعروضة واختر تصرفًا واحدًا فقط يمثل ما يشبهك أكثر.
                    </p>
                    <p class="mt-2 text-xs font-medium text-brand-800 bg-brand-100/60 inline-block px-3 py-1 rounded-lg">
                        لا توجد إجابة صحيحة أو خاطئة. اختر التصرف الذي يمثل طريقة تعاملك المعتادة، وليس التصرف الذي تظن أنه الأفضل أو الذي يتوقعه الآخرون منك.
                    </p>
                </div>
            </div>
        </div>

        {{-- السلم الخماسي الاختياري --}}
        <div class="rounded-2xl border border-slate-200/90 bg-slate-50/50 p-5 sm:p-6 space-y-4">
            <div class="flex items-start gap-3.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-sm shadow-xs">2</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">التقييم الإضافي (اختياري)</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        بعد اختيار التصرف الأساسي، يمكنك اختياريًا تقييم أي عدد من التصرفات السلوكية الأربعة باستخدام السلم من -2 إلى +2. لا يلزمك تقييمها كلها، ويمكنك ترك بعضها دون تقييم.
                    </p>
                </div>
            </div>

            {{-- معاينة بصرية حية لمستويات السلم الخماسي المعتمدة — تخطيط تبايني: موجبان، محايد بعرض كامل، سالبان --}}
            <div class="mt-3 ps-12">
                <span class="text-xs font-semibold text-slate-500 block mb-2">مستويات التقييم المعتمدة ومؤشراتها:</span>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <span class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200/90 bg-emerald-50/80 px-3.5 py-2 text-xs font-bold text-emerald-800 shadow-2xs transition-all hover:bg-emerald-100">
                        <svg class="h-4 w-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M8 9.5c.5-.8 1.5-.8 2 0" />
                            <path d="M14 9.5c.5-.8 1.5-.8 2 0" />
                            <path d="M8 14c1 2.5 7 2.5 8 0" />
                        </svg>
                        <span>يشبهني جدًا</span>
                        <span class="text-xs text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded-md font-mono font-bold">(+2)</span>
                    </span>

                    <span class="inline-flex items-center justify-center gap-2 rounded-xl border border-teal-200/90 bg-teal-50/80 px-3.5 py-2 text-xs font-bold text-teal-800 shadow-2xs transition-all hover:bg-teal-100">
                        <svg class="h-4 w-4 text-teal-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="9" y1="9.5" x2="9.01" y2="9.5" stroke-width="3" />
                            <line x1="15" y1="9.5" x2="15.01" y2="9.5" stroke-width="3" />
                            <path d="M8.5 13.5c1 1.8 6 1.8 7 0" />
                        </svg>
                        <span>يشبهني</span>
                        <span class="text-xs text-teal-700 bg-teal-100/80 px-1.5 py-0.5 rounded-md font-mono font-bold">(+1)</span>
                    </span>

                    <span class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/90 bg-slate-100/80 px-3.5 py-2 text-xs font-bold text-slate-800 shadow-2xs transition-all hover:bg-slate-200 sm:col-span-2">
                        <svg class="h-4 w-4 text-slate-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="9" y1="9.5" x2="9.01" y2="9.5" stroke-width="3" />
                            <line x1="15" y1="9.5" x2="15.01" y2="9.5" stroke-width="3" />
                            <line x1="8.5" y1="14" x2="15.5" y2="14" stroke-width="2" />
                        </svg>
                        <span>محايد / غير متأكد</span>
                        <span class="text-xs text-slate-600 bg-slate-200/80 px-1.5 py-0.5 rounded-md font-mono font-bold">(0)</span>
                    </span>

                    <span class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-200/90 bg-amber-50/80 px-3.5 py-2 text-xs font-bold text-amber-800 shadow-2xs transition-all hover:bg-amber-100">
                        <svg class="h-4 w-4 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="9" y1="9.5" x2="9.01" y2="9.5" stroke-width="3" />
                            <line x1="15" y1="9.5" x2="15.01" y2="9.5" stroke-width="3" />
                            <path d="M8.5 15.5c1-1.5 6-1.5 7 0" />
                        </svg>
                        <span>لا يشبهني</span>
                        <span class="text-xs text-amber-700 bg-amber-100/80 px-1.5 py-0.5 rounded-md font-mono font-bold">(-1)</span>
                    </span>

                    <span class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200/90 bg-rose-50/80 px-3.5 py-2 text-xs font-bold text-rose-800 shadow-2xs transition-all hover:bg-rose-100">
                        <svg class="h-4 w-4 text-rose-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M8.5 10.5c.5-.5 1.5-.5 2 0" />
                            <path d="M13.5 10.5c.5-.5 1.5-.5 2 0" />
                            <path d="M8 16c1.2-2.5 6.8-2.5 8 0" />
                        </svg>
                        <span>لا يشبهني إطلاقًا</span>
                        <span class="text-xs text-rose-700 bg-rose-100/80 px-1.5 py-0.5 rounded-md font-mono font-bold">(-2)</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- الخيارات الخاصة عند تعذر المطابقة --}}
        <div class="rounded-2xl border border-slate-200/90 bg-slate-50/50 p-5 sm:p-6 space-y-4">
            <div class="flex items-start gap-3.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-sm shadow-xs">3</span>
                <h3 class="text-base font-bold text-slate-900">
                    خيارات بديلة عند تعذر مطابقة أي من التصرفات
                </h3>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 ps-12">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-2xs transition-all hover:border-slate-300">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800 mb-2">
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        خيار عدم تمثيل أي تصرف
                    </span>
                    <p class="text-xs leading-relaxed text-slate-600">
                        إذا فهمت الموقف، لكن لم يشبهك أي من التصرفات الأربعة، اختر:
                    </p>
                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-bold text-slate-900 text-center">
                        لا يشبهني أي من هذه التصرفات
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-2xs transition-all hover:border-slate-300">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800 mb-2">
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                        </svg>
                        خيار عدم القدرة على الحكم
                    </span>
                    <p class="text-xs leading-relaxed text-slate-600">
                        إذا لم تستطع فهم الموقف أو لم تملك معلومات كافية لتكوين إجابة موثوقة، اختر:
                    </p>
                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-bold text-slate-900 text-center">
                        لا أستطيع الحكم على هذا الموقف
                    </div>
                    <p class="mt-2 text-xs text-slate-500 text-center">
                        استخدم هذا الخيار عندما يتعذر عليك الحكم فعلًا، وليس لمجرد التردد العادي بين التصرفات.
                    </p>
                </div>
            </div>
        </div>

        {{-- تنبيه الملاحظة الذهبية قبل البدء --}}
        <div class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50/80 to-amber-100/40 p-5 text-amber-950">
            <div class="flex items-start gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-200 text-amber-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-900">ملاحظة قبل البدء</h4>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-amber-900">
                        أجب وفق ما يشبهك أنت، ولا تحاول اختيار الإجابة التي تبدو أفضل أو أكثر قبولًا. خذ وقتك واقرأ كل موقف بهدوء.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. إجراء البدء الرئيسي (Primary Action CTA) --}}
    <footer class="rounded-3xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm text-center">
        <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">
            @if ($activeSession)
                هل أنت مستعد لمتابعة رحلتك؟
            @else
                جاهز لبدء استكشاف ميولك؟
            @endif
        </h2>
        <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">
            تذكر أن إجاباتك تُحفظ تلقائياً، ويمكنك العودة أو مراجعة أي موقف في أي وقت.
        </p>

        <div class="mt-6">
            <form method="POST" action="{{ route('assessment.sessions.store') }}" class="inline-block">
                @csrf
                <button type="submit" class="btn btn-primary min-w-64 text-base font-bold py-4 px-10 rounded-2xl shadow-lg shadow-brand-600/30 transition-all hover:shadow-xl hover:shadow-brand-600/40 hover:-translate-y-0.5 active:translate-y-0">
                    <span class="flex items-center justify-center gap-2.5">
                        <span>
                            @if ($activeSession)
                                متابعة التقييم
                            @else
                                بدء التقييم
                            @endif
                        </span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </span>
                </button>
            </form>
        </div>
    </footer>

</div>
@endsection
