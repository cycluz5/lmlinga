{{--
    Placeholder dashboard page — shell only (no full dashboard widgets yet).
--}}
@extends('layouts.dashboard')

@section('title', 'Dashboard - LMLinga')

@section('content')
    <div class="lml-dashboard-placeholder">
        <div class="lml-dashboard-placeholder__icon" aria-hidden="true">
            <i class="bi bi-layout-text-window-reverse"></i>
        </div>
        <h2 class="lml-dashboard-placeholder__title">Dashboard content coming soon</h2>
        <p class="lml-dashboard-placeholder__text">
            This is the shared authenticated shell for Admin, BHW, BNS, and BSPO.
            Metric cards, maps, and health indicators will be added in a later module.
        </p>
    </div>
@endsection
