@props([
    'id',
    'type' => 'line',
    'data' => [],
    'options' => [],
    'height' => 300,
])

<div class="chart-container">
    <canvas id="{{ $id }}" height="{{ $height }}" aria-label="{{ $attributes->get('aria-label', 'Chart') }}"></canvas>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new window.Chart(document.getElementById('{{ $id }}').getContext('2d'), {
            type: '{{ $type }}',
            data: @json($data),
            options: @json($options),
        });
    });
</script>
