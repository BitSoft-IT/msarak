@extends('layouts.app')

@section('title', 'حسابي')

@section('content')
<div class="mx-auto max-w-2xl">

    <header class="card-surface">
        <div class="auth-head">
            <span class="auth-head-icon">
                <x-ui.icon name="user-plus" class="h-5 w-5" />
            </span>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
            </div>
        </div>
    </header>

    <section aria-labelledby="results-title" class="card-surface mt-6">
        <h2 id="results-title" class="text-lg font-bold text-slate-900">نتائجي</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-700">
            كل نتائج استكشاف الميول التي أتممتها محفوظة لديك، وتفتح كل نتيجة كما صدرت وقتها.
        </p>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('profile.results.index') }}" class="btn btn-primary">سجل نتائجي</a>
            <a href="{{ route('assessment.intro') }}" class="btn btn-secondary">ابدأ تقييمًا جديدًا</a>
        </div>
    </section>

</div>
@endsection
