@extends('layouts.app')

@section('title', $question ? 'تعديل سؤال' : 'إضافة سؤال')

@section('content')
@php
    // F-05: نموذج يعيد إنتاج قواعد B-05 في الـHTML (حدود/اختيار محصور) —
    // كلمة الحقيقة تظل في Backend: أخطاؤه تُعرض من نفس الـFormRequest.
    $codes = ['R' => 'عملي/تطبيقي — R', 'I' => 'بحثي/تحليلي — I', 'A' => 'فني/إبداعي — A',
              'S' => 'اجتماعي/مساند — S', 'E' => 'مبادر/تأثيري — E', 'C' => 'تنظيمي/إجرائي — C'];
    $options = $question
        ? $question->questionOptions->keyBy('position')
        : collect();
    $questionCount = $version->questions()->count();
@endphp

<div class="mx-auto max-w-2xl">

    <a href="{{ route('admin.assessment-versions.show', $version) }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
        <x-ui.icon name="chevron-end" class="h-4 w-4" />
        الإصدار {{ $version->version_number }}
    </a>

    {{-- ترويسة النموذج --}}
    <header class="card-surface mt-4">
        <div class="auth-head">
            <span class="auth-head-icon">
                <x-ui.icon :name="$question ? 'pencil' : 'plus'" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $question ? 'تعديل السؤال ' . $question->position : 'إضافة سؤال جديد' }}
                </h1>
                <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <span>الإصدار {{ $version->version_number }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ $questionCount }} من 18 سؤالًا</span>
                </p>
            </div>
        </div>
        <p class="mt-4 rounded-xl bg-brand-50 p-4 text-sm leading-relaxed text-slate-700">
            كل سؤال: موقف واحد وأربعة تصرفات، لكل تصرف رمز RIASEC — والرموز الأربعة مختلفة. الحقول والحدود من قواعد النظام نفسها.
        </p>
    </header>

    <form method="POST" action="{{ $question
        ? route('admin.assessment-versions.questions.update', [$version, $question])
        : route('admin.assessment-versions.questions.store', $version) }}"
        class="mt-6 space-y-6">
        @csrf
        @if ($question) @method('PUT') @endif

        @if ($errors->any())
            <x-ui.alert variant="danger">
                تحقق من الحقول المظللة بالأحمر — القواعد من Backend ولم تُقبل هذه المدخلات.
            </x-ui.alert>
        @endif

        {{-- القسم 1: بيانات السؤال --}}
        <section class="card-surface">
            <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700" aria-hidden="true">
                    <x-ui.icon name="info" class="h-4 w-4" />
                </span>
                بيانات السؤال
            </h2>

            <div class="mt-5 space-y-5">
                <div class="max-w-40">
                    <label for="position" class="field-label">ترتيب السؤال</label>
                    <input id="position" name="position" type="number" min="1" max="18" step="1" required
                           value="{{ old('position', $position) }}"
                           class="field-input ltr-text"
                           @error('position') aria-invalid="true" aria-describedby="position-error" @enderror>
                    @error('position') <p id="position-error" class="mt-1 text-sm text-danger-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="scenario" class="field-label">الموقف <span class="font-normal text-slate-500">(حتى 5000 حرف)</span></label>
                    <textarea id="scenario" name="scenario" required rows="4" maxlength="5000"
                              class="field-input resize-y leading-relaxed"
                              @error('scenario') aria-invalid="true" aria-describedby="scenario-error" @enderror>{{ old('scenario', $question?->scenario) }}</textarea>
                    @error('scenario') <p id="scenario-error" class="mt-1 text-sm text-danger-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- القسم 2: التصرفات الأربعة --}}
        <fieldset class="card-surface">
            <legend class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700" aria-hidden="true">
                    <x-ui.icon name="check" class="h-4 w-4" />
                </span>
                التصرفات الأربعة
            </legend>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                لكل تصرف نصّه ورمز RIASEC خاصته — والرموز الأربعة يجب أن تكون مختلفة.
            </p>

            <div class="mt-5 space-y-4">
                @for ($i = 0; $i < 4; $i++)
                    @php
                        $pos = $i + 1;
                        $option = $options->get($pos);
                        $errText = 'options.'.$i.'.text';
                        $errCode = 'options.'.$i.'.riasec_code';
                        $hasError = $errors->has($errText) || $errors->has($errCode);
                    @endphp
                    <div class="rounded-xl border bg-white p-4 transition-colors focus-within:border-brand-400 focus-within:ring-4 focus-within:ring-brand-600/10 {{ $hasError ? 'border-danger-300 bg-danger-50/40' : 'border-slate-200' }}">
                        <label for="option-text-{{ $pos }}" class="flex items-center gap-3 text-xs font-bold text-slate-500">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-[11px] font-extrabold text-white" aria-hidden="true">{{ $pos }}</span>
                            التصرف {{ $pos }}
                        </label>
                        <input id="option-text-{{ $pos }}" type="text" name="options[{{ $i }}][text]" required maxlength="5000"
                               placeholder="مثال: يفضّل بناء نموذج أو إصلاح جهاز"
                               value="{{ old('options.'.$i.'.text', $option?->option_text) }}"
                               class="field-input mt-2"
                               @error($errText) aria-invalid="true" aria-describedby="option-text-error-{{ $pos }}" @enderror>
                        @error($errText) <p id="option-text-error-{{ $pos }}" class="mt-1.5 text-sm text-danger-700">{{ $message }}</p> @enderror

                        <div class="mt-3 max-w-52">
                            <label for="option-code-{{ $pos }}" class="field-label text-xs">رمز RIASEC</label>
                            <select id="option-code-{{ $pos }}" name="options[{{ $i }}][riasec_code]" required
                                    class="field-input ltr-text"
                                    @error($errCode) aria-invalid="true" aria-describedby="option-code-error-{{ $pos }}" @enderror>
                                <option value="">— اختر الرمز —</option>
                                @foreach ($codes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('options.'.$i.'.riasec_code', $option?->riasec_code) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error($errCode) <p id="option-code-error-{{ $pos }}" class="mt-1.5 text-sm text-danger-700">{{ $message }}</p> @enderror
                        </div>
                        <input type="hidden" name="options[{{ $i }}][position]" value="{{ $pos }}">
                    </div>
                @endfor
            </div>
        </fieldset>

        {{-- شريط الإجراءات --}}
        <div class="card-surface flex flex-wrap items-center justify-between gap-3 p-5">
            <p class="text-xs text-slate-500">يُحفظ السؤال بترتيبه ويُضاف مباشرة إلى الإصدار.</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.assessment-versions.show', $version) }}" class="btn btn-secondary">إلغاء</a>
                <button type="submit" class="btn btn-primary">{{ $question ? 'حفظ التعديلات' : 'إضافة السؤال' }}</button>
            </div>
        </div>
    </form>

</div>
@endsection
