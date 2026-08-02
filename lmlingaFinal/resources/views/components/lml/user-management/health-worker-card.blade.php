{{--
    Health Worker card — User Management grid item (UI only).
    Overflow Photo/Delete remain placeholders. Edit/View navigate to dedicated pages.
--}}
@props([
    'id' => '',
    'name' => '',
    'role' => '',
    'zone' => '',
    'status' => 'Active',
    'photo' => null,
])

@php
    $workerId = $id !== '' ? $id : 'hw-'.uniqid();
    $menuId = 'lml-hw-menu-'.$workerId;
    $isActive = strtolower((string) $status) === 'active';
    $statusLabel = $isActive ? 'Active' : 'Inactive';
@endphp

<article
    {{ $attributes->class(['lml-hw-card']) }}
    role="listitem"
    data-hw-card
    data-hw-id="{{ $workerId }}"
    data-hw-name="{{ $name }}"
    data-hw-role="{{ $role }}"
    data-hw-zone="{{ $zone }}"
    data-hw-status="{{ $statusLabel }}"
>
    <div class="lml-hw-card__top">
        <div class="lml-hw-card__identity">
            <div class="lml-hw-card__avatar" aria-hidden="true">
                @if ($photo)
                    <img src="{{ $photo }}" alt="" class="lml-hw-card__avatar-img">
                @else
                    <i class="bi bi-person-fill"></i>
                @endif
            </div>

            <div class="lml-hw-card__meta">
                <h3 class="lml-hw-card__name">{{ $name }}</h3>
                <p class="lml-hw-card__role">{{ $role }}</p>
            </div>
        </div>

        <div class="lml-hw-card__menu" data-hw-menu>
            <button
                type="button"
                class="lml-hw-card__menu-btn lml-focus-ring"
                data-hw-menu-toggle
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="{{ $menuId }}"
                aria-label="Actions for {{ $name }}"
            >
                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
            </button>

            <ul
                id="{{ $menuId }}"
                class="lml-hw-card__menu-list"
                role="menu"
                hidden
                data-hw-menu-list
            >
                <li role="none">
                    <button
                        type="button"
                        class="lml-hw-card__menu-item"
                        role="menuitem"
                        tabindex="-1"
                        data-hw-action="photo"
                        data-hw-id="{{ $workerId }}"
                        data-hw-worker="{{ $name }}"
                    >
                        <i class="bi bi-camera" aria-hidden="true"></i>
                        <span>Photo</span>
                    </button>
                </li>
                <li role="none">
                    <a
                        href="{{ route('user-management.health-workers.view', ['id' => $workerId]) }}"
                        class="lml-hw-card__menu-item"
                        role="menuitem"
                        tabindex="-1"
                        data-hw-action="view"
                        data-hw-id="{{ $workerId }}"
                        data-hw-worker="{{ $name }}"
                    >
                        <i class="bi bi-eye" aria-hidden="true"></i>
                        <span>View</span>
                    </a>
                </li>
                <li role="none">
                    <a
                        href="{{ route('user-management.health-workers.edit', ['id' => $workerId]) }}"
                        class="lml-hw-card__menu-item"
                        role="menuitem"
                        tabindex="-1"
                        data-hw-action="edit"
                        data-hw-id="{{ $workerId }}"
                        data-hw-worker="{{ $name }}"
                    >
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                        <span>Edit</span>
                    </a>
                </li>
                <li role="none">
                    <button
                        type="button"
                        class="lml-hw-card__menu-item lml-hw-card__menu-item--danger"
                        role="menuitem"
                        tabindex="-1"
                        data-hw-action="delete"
                        data-hw-id="{{ $workerId }}"
                        data-hw-worker="{{ $name }}"
                    >
                        <i class="bi bi-trash" aria-hidden="true"></i>
                        <span>Delete</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <dl class="lml-hw-card__details">
        <div class="lml-hw-card__row">
            <dt>Assigned</dt>
            <dd>{{ $zone }}</dd>
        </div>
        <div class="lml-hw-card__row">
            <dt>Status</dt>
            <dd @class([
                'lml-hw-card__status',
                'lml-hw-card__status--active' => $isActive,
                'lml-hw-card__status--inactive' => ! $isActive,
            ])>
                {{ $statusLabel }}
            </dd>
        </div>
    </dl>

    <div class="lml-hw-card__actions">
        <a
            href="{{ route('user-management.health-workers.edit', ['id' => $workerId]) }}"
            class="lml-hw-card__action-btn lml-hw-card__action-btn--edit lml-focus-ring"
            data-hw-action="edit"
            data-hw-id="{{ $workerId }}"
            data-hw-worker="{{ $name }}"
        >
            Edit
        </a>
        <a
            href="{{ route('user-management.health-workers.view', ['id' => $workerId]) }}"
            class="lml-hw-card__action-btn lml-hw-card__action-btn--view lml-focus-ring"
            data-hw-action="view"
            data-hw-id="{{ $workerId }}"
            data-hw-worker="{{ $name }}"
        >
            View
        </a>
    </div>
</article>
