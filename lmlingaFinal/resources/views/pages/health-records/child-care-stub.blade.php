{{-- Health Records → Child Care related destination stub (Vitamin A / Deworming / Operation Timbang). --}}
@extends('layouts.dashboard')

@section('title', ($stubTitle ?? 'Child Care') . ' - LMLinga')

@section('content')
    <div class="lml-hr-child-care lml-hr-child-care--stub">
        <div class="lml-hr-child-care__panel">
            <header class="lml-hr-child-care__top">
                <div class="lml-hr-child-care__title-row">
                    <h2 class="lml-hr-child-care__title">{{ $stubTitle ?? 'Summary' }}</h2>
                    <nav class="lml-hr-child-care__nav-pills" aria-label="Child Care related summaries">
                        <a
                            href="{{ route($summaryRoute ?? 'health-records.child-care.index') }}"
                            class="lml-hr-child-care__pill lml-focus-ring"
                        >
                            Child Care
                        </a>
                    </nav>
                </div>
            </header>

            <p class="lml-hr-child-care__stub-message">
                This destination is reserved for the {{ $stubTitle ?? 'summary' }} module.
                Full UI is not implemented in this phase.
            </p>
        </div>
    </div>
@endsection
