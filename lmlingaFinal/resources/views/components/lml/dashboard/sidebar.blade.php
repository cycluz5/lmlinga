{{--
    Dashboard sidebar — shared role-aware navigation shell.
    Menu order and visibility are centralized here. Role comes from the layout (UiRole).
--}}
@props([
    'role' => 'admin',
    'active' => 'dashboard',
    'facilityLabel' => 'Health Center',
    'items' => null,
])

@php
    /*
     | Admin order:
     | Dashboard → User Management → Household Requests → Spot Mapping →
     | Household Profiling → Environmental Health → Health Records (expandable).
     |
     | Health Worker order (after role filter):
     | Dashboard → Spot Mapping → Household Profiling → Environmental Health →
     | Health Records (expandable).
     |
     | Admin-only: User Management, Household Requests.
     */
    $defaultItems = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'bi-grid-1x2-fill',
            'href' => route('dashboard'),
            'roles' => ['admin', 'bhw', 'bns', 'bspo'],
        ],
        [
            'key' => 'user-management',
            'label' => 'User Management',
            'icon' => 'bi-person-fill',
            'href' => route('user-management.index'),
            'roles' => ['admin'],
        ],
        [
            'key' => 'household-requests',
            'label' => 'Household Requests',
            'icon' => 'bi-house-add',
            'href' => route('household-requests.index'),
            'roles' => ['admin'],
        ],
        [
            'key' => 'spot-mapping',
            'label' => 'Spot Mapping',
            'icon' => 'bi-geo-alt-fill',
            'href' => route('spot-mapping.index'),
            'roles' => ['admin', 'bhw', 'bns', 'bspo'],
        ],
        [
            'key' => 'household-profiling',
            'label' => 'Household Profiling',
            'icon' => 'bi-house-door-fill',
            'href' => route('household-profiling.index'),
            'roles' => ['admin', 'bhw', 'bns', 'bspo'],
        ],
        [
            'key' => 'environmental-health',
            'label' => 'Environmental Health',
            'icon' => 'bi-tree-fill',
            'href' => route('environmental-health.index'),
            'roles' => ['admin', 'bhw', 'bns', 'bspo'],
        ],
        [
            'key' => 'health-records',
            'label' => 'Health Records',
            'icon' => 'bi-folder2-open',
            'type' => 'collapse',
            'roles' => ['admin', 'bhw', 'bns', 'bspo'],
            'children' => [
                [
                    'key' => 'immunizations',
                    'label' => 'Immunizations',
                    'icon' => 'bi-bandaid-fill',
                    'href' => '#',
                ],
                [
                    'key' => 'operation-timbang',
                    'label' => 'Operation Timbang',
                    'icon' => 'bi-bar-chart-line-fill',
                    'href' => '#',
                ],
                [
                    'key' => 'vitamin-a',
                    'label' => 'Vitamin A',
                    'icon' => 'bi-capsule-pill',
                    'href' => '#',
                ],
                [
                    'key' => 'deworming',
                    'label' => 'Deworming',
                    'icon' => 'bi-capsule',
                    'href' => '#',
                ],
                [
                    'key' => 'risk-assessment',
                    'label' => 'Risk Assessment',
                    'icon' => 'bi-clipboard2-pulse-fill',
                    'href' => '#',
                ],
                [
                    'key' => 'family-planning',
                    'label' => 'Family Planning',
                    'icon' => 'bi-people-fill',
                    'href' => '#',
                ],
                [
                    'key' => 'maternal',
                    'label' => 'Maternal',
                    'icon' => 'bi-heart-pulse-fill',
                    'href' => '#',
                ],
                [
                    'key' => 'death',
                    'label' => 'Death',
                    'icon' => 'bi-journal-medical',
                    'href' => '#',
                ],
            ],
        ],
    ];

    $menuItems = $items ?? $defaultItems;
    $normalizedRole = strtolower((string) $role);

    $visibleItems = collect($menuItems)->filter(function ($item) use ($normalizedRole) {
        if (! isset($item['roles'])) {
            return true;
        }

        return in_array($normalizedRole, $item['roles'], true);
    })->values();

    $childKeysAreActive = function (array $children) use ($active): bool {
        foreach ($children as $child) {
            if (($child['key'] ?? '') === $active) {
                return true;
            }
        }

        return false;
    };
@endphp

<aside
    id="lmlDashboardSidebar"
    class="lml-sidebar offcanvas-lg offcanvas-start"
    tabindex="-1"
>
    <div class="lml-sidebar__inner">
        <div class="lml-sidebar__mobile-header d-flex d-lg-none justify-content-end">
            <button
                type="button"
                class="lml-sidebar__close lml-focus-ring"
                data-bs-dismiss="offcanvas"
                data-bs-target="#lmlDashboardSidebar"
                aria-label="Close navigation menu"
            >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="lml-sidebar__brand">
            <a href="{{ route('dashboard') }}" class="lml-sidebar__logo text-decoration-none lml-focus-ring rounded-2">
                <img
                    src="{{ asset('assets/images/logo/logo.png') }}"
                    alt=""
                    class="lml-sidebar__logo-img"
                >
                <span class="lml-sidebar__logo-text">LMLinga</span>
            </a>

            <div class="lml-sidebar__seal-wrap">
                <img
                    src="{{ asset('assets/images/logo/LMLogo.png') }}"
                    alt="La Medalla, Iriga City official seal"
                    class="lml-sidebar__seal"
                >
            </div>

            <p class="lml-sidebar__facility mb-0">{{ $facilityLabel }}</p>
        </div>

        <nav class="lml-sidebar__nav" aria-label="Dashboard">
            <ul class="lml-sidebar__list list-unstyled mb-0">
                @foreach ($visibleItems as $item)
                    @php
                        $itemType = $item['type'] ?? 'link';
                        $isCollapse = $itemType === 'collapse';
                        $children = $item['children'] ?? [];
                        $hasActiveChild = $isCollapse && $childKeysAreActive($children);
                        $isActiveLink = ($item['key'] ?? '') === $active;
                        $collapseId = 'lml-sidebar-collapse-' . ($item['key'] ?? uniqid('item-'));
                        $parentHref = $item['href'] ?? null;
                        $hasParentHref = filled($parentHref) && $parentHref !== '#';
                    @endphp

                    <li @class(['lml-sidebar__item', 'lml-sidebar__item--collapse' => $isCollapse])>
                        @if ($isCollapse)
                            {{--
                              Parent label is a normal menu item (does not toggle).
                              Only the chevron button expands/collapses the submenu.
                            --}}
                            <div @class([
                                'lml-sidebar__collapse-row',
                                'lml-sidebar__link--parent-active' => $hasActiveChild || $isActiveLink,
                            ])>
                                @if ($hasParentHref)
                                    <a
                                        href="{{ $parentHref }}"
                                        class="lml-sidebar__parent-link lml-focus-ring"
                                    >
                                        @if (! empty($item['icon']))
                                            <i class="bi {{ $item['icon'] }} lml-sidebar__icon" aria-hidden="true"></i>
                                        @endif
                                        <span class="lml-sidebar__label">{{ $item['label'] }}</span>
                                    </a>
                                @else
                                    <span class="lml-sidebar__parent-link">
                                        @if (! empty($item['icon']))
                                            <i class="bi {{ $item['icon'] }} lml-sidebar__icon" aria-hidden="true"></i>
                                        @endif
                                        <span class="lml-sidebar__label">{{ $item['label'] }}</span>
                                    </span>
                                @endif

                                <button
                                    type="button"
                                    class="lml-sidebar__chevron-btn lml-focus-ring"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $collapseId }}"
                                    aria-expanded="{{ $hasActiveChild ? 'true' : 'false' }}"
                                    aria-controls="{{ $collapseId }}"
                                    aria-label="Toggle {{ $item['label'] }} submenu"
                                >
                                    <i class="bi bi-chevron-down lml-sidebar__chevron" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div
                                id="{{ $collapseId }}"
                                @class(['collapse', 'show' => $hasActiveChild])
                            >
                                <ul class="lml-sidebar__sublist list-unstyled mb-0">
                                    @foreach ($children as $child)
                                        @php
                                            $isActiveChild = ($child['key'] ?? '') === $active;
                                        @endphp
                                        <li>
                                            <a
                                                href="{{ $child['href'] ?? '#' }}"
                                                @class([
                                                    'lml-sidebar__sublink',
                                                    'lml-sidebar__sublink--active' => $isActiveChild,
                                                ])
                                                @if ($isActiveChild) aria-current="page" @endif
                                            >
                                                @if (! empty($child['icon']))
                                                    <i class="bi {{ $child['icon'] }} lml-sidebar__subicon" aria-hidden="true"></i>
                                                @endif
                                                <span>{{ $child['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <a
                                href="{{ $item['href'] ?? '#' }}"
                                @class([
                                    'lml-sidebar__link',
                                    'lml-sidebar__link--active' => $isActiveLink,
                                ])
                                @if ($isActiveLink) aria-current="page" @endif
                            >
                                @if (! empty($item['icon']))
                                    <i class="bi {{ $item['icon'] }} lml-sidebar__icon" aria-hidden="true"></i>
                                @endif
                                <span class="lml-sidebar__label">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="lml-sidebar__footer">
            <a href="{{ route('login') }}" class="lml-sidebar__logout lml-focus-ring">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>
