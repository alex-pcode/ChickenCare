<section class="savings__section" aria-labelledby="savings-lifetime-heading">
    <h2 class="savings__section-title" id="savings-lifetime-heading">Lifetime Impact</h2>

    <div class="savings__lifetime-grid">
        <x-ui.stat-card
            variant="corner-gradient"
            title="You've Gone"
            :total="number_format($lifetime['daysGone'])"
            label="days without buying eggs"
            icon="🏪"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            title="You've Given"
            :total="number_format($lifetime['freeEggs'])"
            label="eggs for free (lifetime)"
            icon="🎁"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            title="You've Eaten"
            :total="number_format($lifetime['omelettes'])"
            label="omelettes (5 eggs each)"
            icon="🍳"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            title="You Saw"
            :total="number_format($lifetime['comedyHours'])"
            label="hours of chicken comedy"
            icon="📺"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            title="You Saved"
            :total="number_format($lifetime['chickensSaved'])"
            label="chickens from caged life"
            icon="🕊️"
        />

        <x-ui.stat-card
            variant="corner-gradient"
            title="You Raised"
            :total="number_format($lifetime['flocksRaised'])"
            label="flocks from baby chickens"
            icon="🐣"
        />
    </div>
</section>
