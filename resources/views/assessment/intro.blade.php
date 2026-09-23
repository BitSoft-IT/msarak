@extends('layouts.app')

@section('title', 'استكشاف ميولك')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
        {{-- رأس الصفحة --}}
        <header class="mb-8 border-b border-slate-100 pb-6 text-center">
            <span class="mb-2 inline-block rounded-full bg-brand-50 px-4 py-1 text-sm font-semibold text-brand-700">
                رحلة التقييم
            </span>
            <h1 class="text-3xl font-extrabold text-slate-900 sm:text-4xl">استكشاف ميولك</h1>
            <p class="mt-4 text-base leading-relaxed text-slate-700 sm:text-lg">
                يساعدك هذا التقييم على استكشاف الأنشطة والمجالات التي قد تستمتع بها أو ترغب في التعرف إليها أكثر.
            </p>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                التقييم أداة استكشافية وإرشادية، وليس اختبارًا للقدرات أو تشخيصًا للشخصية، ولا يحدد تخصصًا أو مهنة واحدة مناسبة لك بشكل نهائي.
            </p>
        </header>

        {{-- تنبيه استئناف الجلسة إن وجدت --}}
        @if ($activeSession)
            <div class="mb-8 rounded-xl border-s-4 border-brand-600 bg-brand-50 p-4 text-slate-800" role="alert">
                <div class="flex items-start gap-3">
                    <span class="text-2xl" aria-hidden="true">💡</span>
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
            <h2 id="instructions-heading" class="text-xl font-bold text-slate-900">طريقة الإجابة</h2>

            <div class="rounded-xl bg-slate-50 p-4 sm:p-5">
                <p class="font-medium leading-relaxed">
                    في كل موقف، اقرأ التصرفات المعروضة واختر تصرفًا واحدًا فقط يمثل ما يشبهك أكثر.
                </p>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    لا توجد إجابة صحيحة أو خاطئة. اختر التصرف الذي يمثل طريقة تعاملك المعتادة، وليس التصرف الذي تظن أنه الأفضل أو الذي يتوقعه الآخرون منك.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">خيار عدم تمثيل أي تصرف</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        إذا فهمت الموقف، لكن لم يشبهك أي من التصرفات الأربعة، اختر:
                    </p>
                    <span class="mt-3 inline-block rounded-md bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-800">
                        لا يشبهني أي من هذه التصرفات
                    </span>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-bold text-slate-900">خيار عدم القدرة على الحكم</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        إذا لم تستطع فهم الموقف أو لم تملك معلومات كافية لتكوين إجابة موثوقة، اختر:
                    </p>
                    <span class="mt-3 inline-block rounded-md bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-800">
                        لا أستطيع الحكم على هذا الموقف
                    </span>
                    <p class="mt-2 text-xs text-slate-500">
                        استخدم هذا الخيار عندما يتعذر عليك الحكم فعلًا، وليس لمجرد التردد العادي بين التصرفات.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4 sm:p-5">
                <h3 class="font-bold text-slate-900">التقييم الإضافي (اختياري)</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    بعد اختيار التصرف الأساسي، يمكنك اختياريًا تقييم أي عدد من التصرفات السلوكية الأربعة باستخدام السلم من -2 إلى +2. لا يلزمك تقييمها كلها، ويمكنك ترك بعضها دون تقييم.
                </p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-amber-900">
                <p class="text-sm font-medium leading-relaxed">
                    <strong>ملاحظة:</strong> أجب وفق ما يشبهك أنت، ولا تحاول اختيار الإجابة التي تبدو أفضل أو أكثر قبولًا. خذ وقتك واقرأ كل موقف بهدوء.
                </p>
            </div>
        </section>

        {{-- زر البدء / الاستئناف --}}
        <footer class="mt-10 border-t border-slate-100 pt-6 text-center">
            <form method="POST" action="{{ route('assessment.sessions.store') }}" class="inline-block">
                @csrf
                <button type="submit" class="btn btn-primary min-w-56 text-lg py-3 shadow-md shadow-brand-600/20">
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
