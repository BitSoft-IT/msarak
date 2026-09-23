@extends('layouts.app')

@section('title', 'استكشاف ميولك')

@section('content')
<div id="assessment-app"
     class="mx-auto max-w-4xl"
     data-session-id="{{ $session->id }}"
     data-complete-url="{{ route('assessment.sessions.complete', $session->id) }}"
     data-save-base-url="/assessment/sessions/{{ $session->id }}/answers"
     data-session-url="{{ route('assessment.show', $session->id) }}">

    {{-- حقن البيانات الأولية الآمنة للجلسة لتفادي وميض التحميل --}}
    <script id="assessment-initial-data" type="application/json">
        {!! json_encode($initialData, JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- قالب السلم الخماسي للتقييم الإضافي وفق نصوص C-03 المعتمدة --}}
    <template id="rating-scale-legend">
        <div data-rating="2"><span aria-hidden="true">😊</span><span>يشبهني جدًا</span></div>
        <div data-rating="1"><span aria-hidden="true">🙂</span><span>يشبهني</span></div>
        <div data-rating="0"><span aria-hidden="true">😐</span><span>محايد / غير متأكد</span></div>
        <div data-rating="-1"><span aria-hidden="true">🙁</span><span>لا يشبهني</span></div>
        <div data-rating="-2"><span aria-hidden="true">😞</span><span>لا يشبهني إطلاقًا</span></div>
    </template>

    {{-- رأس التقييم ومؤشر التقدم --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="text-xs font-semibold text-slate-500">تقييم الميول المهنية والأكاديمية</span>
                <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">استكشاف ميولك</h1>
            </div>

            <div class="text-start sm:text-end">
                <span id="progress-position-text" class="text-sm font-bold text-brand-700">
                    الموقف <span id="current-position-num">1</span> من <span id="total-questions-num">18</span>
                </span>
                <div id="processed-summary-text" class="text-xs text-slate-500">
                    تمت معالجة <span id="processed-count-num">0</span> من 18 موقفًا
                </div>
            </div>
        </div>

        {{-- شريط التقدم المرئي --}}
        <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-slate-100" role="progressbar" id="progress-bar-container" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            <div id="progress-bar-fill" class="h-full rounded-full bg-brand-600 transition-all duration-300 ease-out" style="width: 0%;"></div>
        </div>
    </div>

    {{-- بطاقة الموقف الحالية --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        {{-- سيناريو الموقف --}}
        <div class="mb-6 border-b border-slate-100 pb-5">
            <div class="mb-2 flex items-center justify-between">
                <span id="scenario-badge" class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">
                    الموقف رقم <span id="badge-num" class="ms-1">1</span>
                </span>
                <div id="save-status-indicator" class="flex items-center gap-1.5 text-xs font-medium" aria-live="polite">
                    {{-- تُحدث ديناميكياً بواسطة JavaScript --}}
                    <span id="save-status-text" class="text-slate-400">جاهز</span>
                </div>
            </div>
            <h2 id="scenario-text" class="text-lg font-bold leading-relaxed text-slate-900 sm:text-xl">
                جارٍ تحميل الموقف…
            </h2>
            <p class="mt-2 text-sm text-slate-500">
                اقرأ الموقف، ثم اختر تصرفًا واحدًا فقط يشبهك أكثر.
            </p>
        </div>

        {{-- تنبيه الأخطاء إن حدثت --}}
        <div id="error-alert" class="mb-6 hidden rounded-xl border-s-4 border-red-600 bg-red-50 p-4 text-red-900" role="alert">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-2">
                    <span class="text-lg" aria-hidden="true">⚠️</span>
                    <div>
                        <p id="error-alert-message" class="text-sm font-medium">تعذر حفظ الإجابة. حاول مرة أخرى.</p>
                    </div>
                </div>
                <button type="button" id="retry-save-btn" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700">
                    إعادة المحاولة
                </button>
            </div>
        </div>

        {{-- خيارات التصرف الأربعة --}}
        <fieldset class="space-y-4" id="options-fieldset">
            <legend class="mb-3 block text-base font-bold text-slate-900">
                أي تصرف يشبهك أكثر؟
                <span class="block text-xs font-normal text-slate-500 mt-0.5">
                    اختر تصرفًا واحدًا فقط يمثل طريقة تعاملك مع هذا الموقف.
                </span>
            </legend>

            <div id="options-container" class="space-y-3">
                {{-- تُحقن الخيارات عبر JavaScript ديناميكياً --}}
            </div>
        </fieldset>

        {{-- خيارات الاستبعاد الخاصة (حصرية مع الخيار الأساسي) --}}
        <div class="mt-8 border-t border-slate-100 pt-6">
            <span class="mb-3 block text-xs font-bold uppercase tracking-wider text-slate-400">
                خيارات بديلة عند تعذر مطابقة التصرفات
            </span>
            <div class="grid gap-3 sm:grid-cols-2">
                {{-- لا يشبهني أي من هذه التصرفات --}}
                <label id="none-fit-card" class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition-all hover:bg-slate-50">
                    <input type="radio" name="response_state" value="none_selected" id="none-fit-radio" class="mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">
                    <div>
                        <span class="block font-bold text-slate-900 text-sm">لا يشبهني أي من هذه التصرفات</span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-500">
                            اختر هذا الخيار إذا فهمت الموقف، لكنك لا ترى نفسك في أي من التصرفات المعروضة.
                        </span>
                    </div>
                </label>

                {{-- لا أستطيع الحكم على هذا الموقف --}}
                <label id="cannot-judge-card" class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition-all hover:bg-slate-50">
                    <input type="radio" name="response_state" value="unable_to_judge" id="cannot-judge-radio" class="mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600">
                    <div>
                        <span class="block font-bold text-slate-900 text-sm">لا أستطيع الحكم على هذا الموقف</span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-500">
                            اختر هذا الخيار إذا لم تفهم الموقف أو لم تملك معلومات كافية لتكوين إجابة موثوقة.
                        </span>
                    </div>
                </label>
            </div>
        </div>

        {{-- شريط التحكم والتنقل السفلي --}}
        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
            <button type="button" id="prev-btn" class="btn btn-secondary text-sm disabled:opacity-40 disabled:pointer-events-none">
                ← السابق
            </button>

            <div class="flex items-center gap-3">
                <button type="button" id="next-btn" class="btn btn-primary text-sm min-w-28">
                    التالي →
                </button>

                <button type="button" id="complete-btn" class="btn bg-green-700 text-white hover:bg-green-800 text-sm min-w-32 hidden">
                    إكمال التقييم ✓
                </button>
            </div>
        </div>
    </div>

    {{-- شبكة التنقل السريع بين المواقف الـ 18 --}}
    <nav aria-label="التنقل بين مواقف التقييم" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">خريطة المواقف</h3>
            <div class="flex items-center gap-4 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span> الحالي</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-green-500"></span> مُجاب</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-slate-200"></span> متبقٍ</span>
            </div>
        </div>

        <div id="questions-nav-grid" class="grid grid-cols-6 sm:grid-cols-9 md:grid-cols-18 gap-2">
            {{-- تُحقن أزرار المواقف 1..18 بواسطة JavaScript --}}
        </div>
    </nav>

    {{-- 1. نافذة تأكيد التناقض السلوكي (Conflict Modal) --}}
    <div id="conflict-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="conflict-modal-title">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl sm:p-7 animate-in fade-in zoom-in-95 duration-200">
            <div class="mb-4 flex items-center gap-3 text-amber-600">
                <span class="text-2xl" aria-hidden="true">⚠️</span>
                <h3 id="conflict-modal-title" class="text-lg font-bold text-slate-900">تأكيد تقييم التصرف</h3>
            </div>
            <p class="text-sm leading-relaxed text-slate-700">
                اخترت هذا التصرف بوصفه ما يشبهك أكثر، لكنك قيمته بأنه لا يشبهك. هل تريد الاحتفاظ بهذه الإجابة؟
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <button type="button" id="conflict-review-btn" class="btn btn-secondary text-sm">
                    مراجعة الاختيار
                </button>
                <button type="button" id="conflict-keep-btn" class="btn btn-primary text-sm">
                    الاحتفاظ بالإجابة
                </button>
            </div>
        </div>
    </div>

    {{-- 2. نافذة تأكيد إكمال التقييم النهائي (Completion Modal) --}}
    <div id="completion-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="completion-modal-title">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl sm:p-7 animate-in fade-in zoom-in-95 duration-200">
            <div class="mb-4 flex items-center gap-3 text-brand-600">
                <span class="text-2xl" aria-hidden="true">🎯</span>
                <h3 id="completion-modal-title" class="text-lg font-bold text-slate-900">تأكيد إكمال التقييم</h3>
            </div>
            <p class="text-sm leading-relaxed text-slate-700">
                تأكد من مراجعة إجاباتك. بعد إكمال التقييم لن تتمكن من تعديل هذه الجلسة.
            </p>
            <div id="completion-modal-error" class="mt-3 hidden text-xs font-semibold text-red-600">
                لم تكتمل المواقف المطلوبة بعد. راجع المواقف التي تحتاج معالجة، ثم حاول إكمال التقييم مرة أخرى.
            </div>
            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <button type="button" id="completion-cancel-btn" class="btn btn-secondary text-sm">
                    متابعة المراجعة
                </button>
                <button type="button" id="completion-confirm-btn" class="btn bg-green-700 text-white hover:bg-green-800 text-sm">
                    إكمال التقييم
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
