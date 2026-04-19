<div class="flock__overview-stats {{ $overviewStats['showBrooding'] ? 'flock__overview-stats--5' : 'flock__overview-stats--4' }}">

    <div style="animation-delay: 0ms" class="flock-card-entrance">
        <x-ui.stat-card
            title="Laying"
            :total="$overviewStats['laying']['total']"
            :label="$overviewStats['laying']['label']"
            icon="🐔"
            variant="default"
        />
    </div>

    <div style="animation-delay: 50ms" class="flock-card-entrance">
        <x-ui.stat-card
            title="Not Laying"
            :total="$overviewStats['notLaying']['total']"
            :label="$overviewStats['notLaying']['label']"
            icon="⏳"
            variant="default"
        />
    </div>

    @if($overviewStats['showBrooding'])
    <div style="animation-delay: 100ms" class="flock-card-entrance">
        <x-ui.stat-card
            title="Brooding"
            :total="$overviewStats['brooding']['total']"
            :label="$overviewStats['brooding']['label']"
            icon="🐣"
            variant="default"
        />
    </div>
    @endif

    <div style="animation-delay: {{ $overviewStats['showBrooding'] ? '150' : '100' }}ms" class="flock-card-entrance">
        <x-ui.stat-card
            title="Roosters"
            :total="$overviewStats['roosters']['total']"
            :label="$overviewStats['roosters']['label']"
            icon="🐓"
            variant="default"
        />
    </div>

    <div style="animation-delay: {{ $overviewStats['showBrooding'] ? '200' : '150' }}ms" class="flock-card-entrance">
        <x-ui.stat-card
            title="Chicks"
            :total="$overviewStats['chicks']['total']"
            :label="$overviewStats['chicks']['label']"
            icon="🐥"
            variant="default"
        />
    </div>

</div>
