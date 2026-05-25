<section class="savings__section" aria-labelledby="savings-lifetime-heading">
    <h2 class="savings__section-title" id="savings-lifetime-heading">{{ __('savings.lifetime.title') }}</h2>

    <div class="savings__lifetime-grid">
        <x-ui.stat-card
            variant="corner-gradient"
            :title="__('savings.lifetime.cards.gone.title')"
            :total="number_format($lifetime['daysGone'])"
            :label="__('savings.lifetime.cards.gone.label')"
            icon="🏪"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            :title="__('savings.lifetime.cards.given.title')"
            :total="number_format($lifetime['freeEggs'])"
            :label="__('savings.lifetime.cards.given.label')"
            icon="🎁"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            :title="__('savings.lifetime.cards.eaten.title')"
            :total="number_format($lifetime['omelettes'])"
            :label="__('savings.lifetime.cards.eaten.label')"
            icon="🍳"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            :title="__('savings.lifetime.cards.saw.title')"
            :total="number_format($lifetime['comedyHours'])"
            :label="__('savings.lifetime.cards.saw.label')"
            icon="📺"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            :title="__('savings.lifetime.cards.saved.title')"
            :total="number_format($lifetime['chickensSaved'])"
            :label="__('savings.lifetime.cards.saved.label')"
            icon="🕊️"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            :title="__('savings.lifetime.cards.raised.title')"
            :total="number_format($lifetime['flocksRaised'])"
            :label="__('savings.lifetime.cards.raised.label')"
            icon="🐣"
        />
    </div>
</section>
