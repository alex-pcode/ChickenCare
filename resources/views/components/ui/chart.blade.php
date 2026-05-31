@props([
    'id',
    'type' => 'line',
    'data' => [],
    'options' => [],
    'height' => 300,
])

<div class="chart-container" style="position: relative; height: {{ $height }}px; width: 100%; overflow: hidden;">
    <canvas id="{{ $id }}" aria-label="{{ $attributes->get('aria-label', 'Chart') }}"></canvas>
</div>
<script>
    (function initChart_{{ preg_replace('/[^A-Za-z0-9_]/', '_', $id) }}() {
        const ctx = document.getElementById('{{ $id }}');
        if (!ctx) return;
        if (!window.Chart) {
            document.addEventListener('DOMContentLoaded', initChart_{{ preg_replace('/[^A-Za-z0-9_]/', '_', $id) }}, { once: true });
            return;
        }
        window.Chart.getChart(ctx)?.destroy();
        const userOptions = @json((object) $options);
        const chart = new window.Chart(ctx.getContext('2d'), {
            type: '{{ $type }}',
            data: @json($data),
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
            }, userOptions),
        });
        requestAnimationFrame(() => { if (chart.canvas) chart.resize(); });
    })();
</script>
