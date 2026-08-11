<section class="lml-mc__panel" aria-labelledby="lml-mc-landing-title" data-mc-landing>
    <header class="lml-mc__panel-head">
        <div class="lml-mc__panel-titles">
            <h2 id="lml-mc-landing-title" class="lml-mc__panel-title">
                MATERNAL CARE
            </h2>
            <p class="lml-mc__panel-subtitle">
                Record, monitor and manage maternal healthcare throughout pregnancy and beyond.
            </p>
        </div>
    </header>

    <div class="lml-mc__empty" data-mc-no-record>
        <span class="lml-mc__empty-icon" aria-hidden="true">
            <i class="bi bi-clipboard2-pulse"></i>
        </span>
        <p class="lml-mc__empty-title">NO RECORD</p>
        <p class="lml-mc__empty-copy">
            No maternal care pregnancy record has been registered for this member yet.
        </p>
        <a
            href="{{ route('household-profiling.members.maternal-care.register', [
                'householdNo' => $householdNo,
                'memberId' => $memberId,
            ]) }}"
            class="lml-mc__btn lml-mc__btn--primary lml-focus-ring"
            data-mc-register-cta
        >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Register Maternal Record</span>
        </a>
    </div>
</section>
