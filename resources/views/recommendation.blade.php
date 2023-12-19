@php
    $apiUrl = $apiUrl ?? url('/api/recommendation');
@endphp
<script src="{{ asset('vendor/mr4-lc/recommendation/js/recommendation.js') }}"></script>
<link rel="stylesheet" href="{{ asset('vendor/mr4-lc/recommendation/css/recommendation.css') }}">
<div class="mr4-lc-recommendation {{ $className ?? '' }}" data-content-id="{{ $itemId }}"
    data-content-name="{{ $itemName }}" data-content-url="{{ $apiUrl }}"
    data-content-builder="{{ $builder ?? 'commonBuildView' }}">
    <img class="mr4-lc-recommendation-loading" src="{{ asset('vendor/mr4-lc/recommendation/img/loading.svg') }}"
        width="96px" height="96px" alt="Loading..." onload='LoadRecommendation(this.parentElement)'>
    <div class="mr4-lc-recommendation-items container"></div>
</div>
