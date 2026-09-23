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

    {{-- نصوص السلم الخماسي للتقييم الإضافي وفق نصوص C-03 المعتمدة للوصولية والفحص --}}
    <template id="rating-scale-legend">
        <div data-rating="2">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 9.5c.5-.8 1.5-.8 2 0"/><path d="M14 9.5c.5-.8 1.5-.8 2 0"/><path d="M8 14c1 2.5 7 2.5 8 0"/></svg>
            <span>يشبهني جدًا</span>
        </div>
        <div data-rating="1">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="9" y1="9.5" x2="9.01" y2="9.5" stroke-width="3"/><line x1="15" y1="9.5" x2="15.01" y2="9.5" stroke-width="3"/><path d="M8.5 13.5c1 1.8 6 1.8 7 0"/></svg>
            <span>يشبهني</span>
        </div>
        <div data-rating="0">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="9" y1="9.5" x2="9.01" y2="9.5" stroke-width="3"/><line x1="15" y1="9.5" x2="15.01" y2="9.5" stroke-width="3"/><line x1="8.5" y1="14" x2="15.5" y2="14" stroke-width="2"/></svg>
            <span>محايد / غير متأكد</span>
        </div>
        <div data-rating="-1">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="9" y1="9.5" x2="9.01" y2="9.5" stroke-width="3"/><line x1="15" y1="9.5" x2="15.01" y2="9.5" stroke-width="3"/><path d="M8.5 15.5c1-1.5 6-1.5 7 0"/></svg>
            <span>لا يشبهني</span>
        </div>
        <div data-rating="-2">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8.5 10.5c.5-.5 1.5-.5 2 0"/><path d="M13.5 10.5c.5-.5 1.5-.5 2 0"/><path d="M8 16c1.2-2.5 6.8-2.5 8 0"/></svg>
            <span>لا يشبهني إطلاقًا</span>
        </div>
    </template>

    {{-- رأس التقييم ومؤشر التقدم --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 transition-all">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500">
                    <span class="h-2 w-2 rounded-full bg-brand-600"></span>
                    تقييم الميول المهنية والأكاديمية
                </span>
                <h1 class="text-xl font-extrabold text-slate-900 sm:text-2xl tracking-tight">استكشاف ميولك</h1>
            </div>

            <div class="text-start sm:text-end">
                <span id="progress-position-text" class="text-sm font-bold text-brand-700">
                    الموقف <span id="current-position-num">1</span> من <span id="total-questions-num">18</span>
                </span>
                <div id="processed-summary-text" class="text-xs font-medium text-slate-500">
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
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 transition-all hover:shadow-md">
        {{-- سيناريو الموقف --}}
        <div class="mb-6 border-b border-slate-100 pb-5">
            <div class="mb-3 flex items-center justify-between">
                <span id="scenario-badge" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-600 animate-pulse"></span>
                    الموقف رقم <span id="badge-num" class="ms-0.5">1</span>
                </span>
                <div id="save-status-indicator" class="flex items-center gap-1.5 text-xs font-medium" aria-live="polite">
                    <span id="save-status-icon" class="inline-flex items-center"></span>
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
        <div id="error-alert" class="mb-6 hidden rounded-xl border-s-4 border-red-600 bg-red-50 p-4 text-red-900 shadow-xs" role="alert">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-2.5">
                    <svg class="h-5 w-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <p id="error-alert-message" class="text-sm font-medium">تعذر حفظ الإجابة. حاول مرة أخرى.</p>
                    </div>
                </div>
                <button type="button" id="retry-save-btn" class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white shadow-xs hover:bg-red-700 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
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
                <label id="none-fit-card" class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition-all hover:bg-slate-50 hover:border-slate-300">
                    <input type="radio" name="response_state" value="none_selected" id="none-fit-radio" class="mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                    <div>
                        <span class="flex items-center gap-1.5 font-bold text-slate-900 text-sm">
                            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            لا يشبهني أي من هذه التصرفات
                        </span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-500">
                            اختر هذا الخيار إذا فهمت الموقف، لكنك لا ترى نفسك في أي من التصرفات المعروضة.
                        </span>
                    </div>
                </label>

                {{-- لا أستطيع الحكم على هذا الموقف --}}
                <label id="cannot-judge-card" class="relative flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition-all hover:bg-slate-50 hover:border-slate-300">
                    <input type="radio" name="response_state" value="unable_to_judge" id="cannot-judge-radio" class="mt-1 h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                    <div>
                        <span class="flex items-center gap-1.5 font-bold text-slate-900 text-sm">
                            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                            </svg>
                            لا أستطيع الحكم على هذا الموقف
                        </span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-500">
                            اختر هذا الخيار إذا لم تفهم الموقف أو لم تملك معلومات كافية لتكوين إجابة موثوقة.
                        </span>
                    </div>
                </label>
            </div>
        </div>

        {{-- شريط التحكم والتنقل السفلي --}}
        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
            <button type="button" id="prev-btn" class="btn btn-secondary text-sm gap-2 disabled:opacity-40 disabled:pointer-events-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
                <span>السابق</span>
            </button>

            <div class="flex items-center gap-3">
                <button type="button" id="next-btn" class="btn btn-primary text-sm min-w-28 gap-2">
                    <span>التالي</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>

                <button type="button" id="complete-btn" class="btn bg-green-700 text-white hover:bg-green-800 text-sm min-w-32 gap-2 shadow-xs hidden">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>إكمال التقييم</span>
                </button>
            </div>
        </div>
    </div>

    {{-- شبكة التنقل السريع بين المواقف الـ 18 --}}
    <nav aria-label="التنقل بين مواقف التقييم" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-3.5 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">خريطة المواقف (1 — 18)</h3>
            <div class="flex items-center gap-4 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-brand-600 ring-2 ring-brand-100"></span> الحالي</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> مُجاب</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-slate-200"></span> متبقٍ</span>
            </div>
        </div>

        <div id="questions-nav-grid" class="grid grid-cols-6 sm:grid-cols-9 md:grid-cols-18 gap-2">
            {{-- تُحقن أزرار المواقف 1..18 بواسطة JavaScript --}}
        </div>
    </nav>

    {{-- 1. نافذة تأكيد التناقض السلوكي (Conflict Modal) --}}
    <div id="conflict-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="conflict-modal-title">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7 animate-in fade-in zoom-in-95 duration-200">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
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
    <div id="completion-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-900/40 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="completion-modal-title">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7 animate-in fade-in zoom-in-95 duration-200">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
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
