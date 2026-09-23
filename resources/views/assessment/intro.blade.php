@extends('layouts.app')

@section('title', 'استكشاف ميولك')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10 transition-shadow hover:shadow-md">
        {{-- رأس الصفحة --}}
        <header class="mb-8 border-b border-slate-100 pb-6 text-center">
            <span class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3.5 py-1 text-xs font-bold text-brand-700">
                <svg class="h-3.5 w-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                رحلة التقييم الأكاديمي والمهني
            </span>
            <h1 class="text-3xl font-extrabold text-slate-900 sm:text-4xl tracking-tight">استكشاف ميولك</h1>
            <p class="mt-4 text-base leading-relaxed text-slate-700 sm:text-lg">
                يساعدك هذا التقييم على استكشاف الأنشطة والمجالات التي قد تستمتع بها أو ترغب في التعرف إليها أكثر.
            </p>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                التقييم أداة استكشافية وإرشادية، وليس اختبارًا للقدرات أو تشخيصًا للشخصية، ولا يحدد تخصصًا أو مهنة واحدة مناسبة لك بشكل نهائي.
            </p>
        </header>

        {{-- تنبيه استئناف الجلسة إن وجدت --}}
        @if ($activeSession)
            <div class="mb-8 rounded-xl border-s-4 border-brand-600 bg-brand-50/80 p-4 text-slate-800 shadow-xs" role="alert">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.516 0c.85.493 1.508 1.333 1.508 2.316V18" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-brand-700">لديك تقييم غير مكتمل</h2>
                        <p class="mt-1 text-sm leading-relaxed text-slate-700">
                            لديك تقييم غير مكتمل. تم حفظ تقدمك، ويمكنك المتابعة من حيث توقفت.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- إرشادات التقييم المعتمدة --}}
        <section aria-labelledby="instructions-heading" class="space-y-6 text-slate-800">
            <h2 id="instructions-heading" class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <svg class="h-5 w-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                طريقة الإجابة
            </h2>

            <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 sm:p-5">
                <p class="font-medium leading-relaxed text-slate-900">
                    في كل موقف، اقرأ التصرفات المعروضة واختر تصرفًا واحدًا فقط يمثل ما يشبهك أكثر.
                </p>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    لا توجد إجابة صحيحة أو خاطئة. اختر التصرف الذي يمثل طريقة تعاملك المعتادة، وليس التصرف الذي تظن أنه الأفضل أو الذي يتوقعه الآخرون منك.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4 transition-colors hover:border-slate-300">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </span>
                        <h3 class="font-bold text-slate-900 text-sm">خيار عدم تمثيل أي تصرف</h3>
                    </div>
                    <p class="mt-2.5 text-xs leading-relaxed text-slate-600">
                        إذا فهمت الموقف، لكن لم يشبهك أي من التصرفات الأربعة، اختر:
                    </p>
                    <span class="mt-3 inline-block rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-800 border border-slate-200">
                        لا يشبهني أي من هذه التصرفات
                    </span>
                </div>

                <div class="rounded-xl border border-slate-200 p-4 transition-colors hover:border-slate-300">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                            </svg>
                        </span>
                        <h3 class="font-bold text-slate-900 text-sm">خيار عدم القدرة على الحكم</h3>
                    </div>
                    <p class="mt-2.5 text-xs leading-relaxed text-slate-600">
                        إذا لم تستطع فهم الموقف أو لم تملك معلومات كافية لتكوين إجابة موثوقة، اختر:
                    </p>
                    <span class="mt-3 inline-block rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-800 border border-slate-200">
                        لا أستطيع الحكم على هذا الموقف
                    </span>
                    <p class="mt-2 text-[11px] text-slate-500">
                        استخدم هذا الخيار عندما يتعذر عليك الحكم فعلًا، وليس لمجرد التردد العادي بين التصرفات.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4 sm:p-5">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                        </svg>
                    </span>
                    <h3 class="font-bold text-slate-900 text-sm">التقييم الإضافي (اختياري)</h3>
                </div>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    بعد اختيار التصرف الأساسي، يمكنك اختياريًا تقييم أي عدد من التصرفات السلوكية الأربعة باستخدام السلم من -2 إلى +2. لا يلزمك تقييمها كلها، ويمكنك ترك بعضها دون تقييم.
                </p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-amber-950">
                <div class="flex items-start gap-2.5">
                    <svg class="h-5 w-5 text-amber-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <p class="text-sm font-medium leading-relaxed">
                        <strong>ملاحظة:</strong> أجب وفق ما يشبهك أنت، ولا تحاول اختيار الإجابة التي تبدو أفضل أو أكثر قبولًا. خذ وقتك واقرأ كل موقف بهدوء.
                    </p>
                </div>
            </div>
        </section>

        {{-- زر البدء / الاستئناف --}}
        <footer class="mt-10 border-t border-slate-100 pt-6 text-center">
            <form method="POST" action="{{ route('assessment.sessions.store') }}" class="inline-block">
                @csrf
                <button type="submit" class="btn btn-primary min-w-56 text-base font-bold py-3.5 px-8 shadow-md shadow-brand-600/25 transition-all hover:shadow-lg hover:shadow-brand-600/30">
                    @if ($activeSession)
                        متابعة التقييم
                    @else
                        بدء التقييم
                    @endif
                </button>
            </form>
        </footer>
    </div>
</div>
@endsection
