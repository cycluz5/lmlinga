@php
    use App\Support\DemoMaternalCare;
@endphp

<section class="lml-mc__panel" aria-labelledby="lml-mc-history-title" data-mc-history>
    <header class="lml-mc__panel-head">
        <div class="lml-mc__panel-titles">
            <h2 id="lml-mc-history-title" class="lml-mc__panel-title">Pregnancy History</h2>
            <p class="lml-mc__panel-subtitle">
                Previous pregnancy records for this member.
            </p>
        </div>
        <a
            href="{{ route('household-profiling.members.maternal-care.index', $routeParams) }}"
            class="lml-mc__btn lml-mc__btn--ghost lml-focus-ring"
        >
            Back to Overview
        </a>
    </header>

    @if ($history === [])
        <p class="lml-mc__history-empty" data-mc-history-empty>
            No Record Yet
        </p>
    @else
        <ul class="lml-mc__history-list" data-mc-history-list>
            @foreach ($history as $row)
                @php
                    $outcome = (string) data_get($row, 'delivery.outcome', '');
                    $outcomeLabel = DemoMaternalCare::OUTCOMES[$outcome] ?? '—';
                    $delivered = (string) data_get($row, 'delivery.datetime', '');
                    $deliveredDate = $delivered !== '' ? substr($delivered, 0, 10) : '';
                    $transDate = (string) data_get($row, 'trans_out.date_transferred_out', '');
                    $statusNote = $transDate !== ''
                        ? 'Transferred out '.DemoMaternalCare::formatDate($transDate)
                        : ($deliveredDate !== ''
                            ? 'Delivered '.DemoMaternalCare::formatDate($deliveredDate)
                            : 'Record closed');
                @endphp
                <li class="lml-mc__history-item" data-mc-history-item="{{ $row['id'] ?? '' }}">
                    <div class="lml-mc__history-main">
                        <span class="lml-mc__history-title">
                            Pregnancy {{ $row['number'] ?? '' }}
                            @if (! empty($row['lmp']))
                                (LMP: {{ $row['lmp_label'] ?? DemoMaternalCare::formatDate($row['lmp']) }})
                            @endif
                        </span>
                        <span class="lml-mc__pill" data-mc-history-outcome>
                            {{ $outcome !== '' ? $outcomeLabel : 'Closed' }}
                        </span>
                    </div>
                    <p class="lml-mc__history-meta">{{ $statusNote }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</section>
