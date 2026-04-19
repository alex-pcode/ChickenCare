window.viabilityCalculator = function(defaults) {
    return {
        birdCount: defaults.birdCount ?? 5,
        eggPrice: defaults.eggPrice ?? 0.30,
        startingCost: defaults.startingCost ?? 50,
        selectedStartingCostId: 'minimal',
        selectedAcquisitionId: 'laying_hens',
        activeCarouselIndex: 0,

        startingCostOptions: [
            {
                id: 'minimal', cost: 50, title: 'Minimal Setup',
                description: '~$50 total investment',
                details: ['Existing structure or simple shelter', 'Basic feeders & waterers', 'Repurposed materials', 'Gifted or free chickens']
            },
            {
                id: 'basic', cost: 200, title: 'Basic Setup',
                description: '~$200 total investment',
                details: ['Simple coop construction', 'Basic fencing & security', 'Essential equipment', 'Store-bought chickens']
            },
            {
                id: 'premium', cost: 500, title: 'Premium Setup',
                description: '~$500 total investment',
                details: ['Quality coop with features', 'Professional fencing', 'Automatic systems', 'Premium breeds & equipment']
            },
            {
                id: 'luxury', cost: 1000, title: 'Luxury Setup',
                description: '~$1000+ total investment',
                details: ['Custom-built coop', 'Landscaping & features', 'Automated systems', 'High-end breeds & accessories']
            },
        ],

        acquisitionOptions: [
            {
                id: 'baby_chicks', title: 'Raise Baby Chicks', emoji: '🐣',
                layingDelayMonths: 5, costMultiplier: 0.3,
                description: 'Start with day-old chicks (~$3-5 each)',
                details: ['Lower initial cost per bird', '5 months before laying begins', 'More feed costs before production', 'Higher mortality risk', 'Bond with chickens from day one']
            },
            {
                id: 'laying_hens', title: 'Buy Laying Hens', emoji: '🐔',
                layingDelayMonths: 0, costMultiplier: 1.0,
                description: 'Purchase ready-to-lay hens (~$15-25 each)',
                details: ['Higher upfront cost per bird', 'Immediate egg production', 'Already mature and healthy', 'Lower mortality risk', 'Instant gratification']
            },
        ],

        selectStartingCost(option) {
            this.selectedStartingCostId = option.id;
            this.startingCost = option.cost;
        },

        get selectedAcquisition() {
            return this.acquisitionOptions.find(o => o.id === this.selectedAcquisitionId) || this.acquisitionOptions[1];
        },

        // Feed & Production state (Story 2)
        selectedFeedId: 'standard',
        selectedProductionId: 'realistic',

        feedOptions: [
            {
                id: 'budget', costPerBird: 1.50, title: 'Budget Approach',
                description: '~$1.50 per bird per month',
                details: ['Free-range during day', 'Kitchen scraps & garden waste', 'Buy feed from co-ops in bulk', 'Minimal supplements']
            },
            {
                id: 'standard', costPerBird: 3.50, title: 'Standard Approach',
                description: '~$3.50 per bird per month',
                details: ['Commercial feed only', 'Chain store purchases', 'Basic layer pellets', 'Limited free-ranging']
            },
            {
                id: 'premium', costPerBird: 5.00, title: 'Premium Approach',
                description: '~$5.00 per bird per month',
                details: ['Organic/premium feeds', 'Treats & supplements', 'Scratch grains & extras', 'Spoiled chicken lifestyle']
            },
        ],

        productionOptions: [
            {
                id: 'conservative', eggsPerBirdPerWeek: 4, eggsPerBirdPerMonth: 16, title: 'Conservative Estimate',
                description: '~4 eggs per bird per week',
                details: ['Older hens or winter months', 'Less daylight hours', 'Basic nutrition', 'Stress or health issues']
            },
            {
                id: 'realistic', eggsPerBirdPerWeek: 5.5, eggsPerBirdPerMonth: 22, title: 'Realistic Average',
                description: '~5.5 eggs per bird per week',
                details: ['Healthy adult layers', 'Good nutrition & care', 'Spring/summer months', 'Popular breeds (Rhode Island Red, etc.)']
            },
            {
                id: 'optimistic', eggsPerBirdPerWeek: 6.5, eggsPerBirdPerMonth: 26, title: 'Optimistic Scenario',
                description: '~6.5 eggs per bird per week',
                details: ['Prime laying age (1-2 years)', 'Excellent nutrition & care', 'Long daylight hours', 'High-production breeds']
            },
        ],

        get selectedFeed() {
            return this.feedOptions.find(o => o.id === this.selectedFeedId) || this.feedOptions[1];
        },

        get selectedProduction() {
            return this.productionOptions.find(o => o.id === this.selectedProductionId) || this.productionOptions[1];
        },

        get results() {
            return this.calculateViability();
        },

        get showResults() {
            return this.birdCount > 0;
        },

        calculateViability() {
            const birdCount = Math.max(0, this.birdCount || 0);
            const eggPrice = Math.max(0, this.eggPrice || 0);
            const startingCost = Math.max(0, this.startingCost || 0);
            const feed = this.selectedFeed;
            const production = this.selectedProduction;
            const acquisition = this.selectedAcquisition;

            const monthlyFeedCost = birdCount * feed.costPerBird;
            const monthlyEggProduction = birdCount * production.eggsPerBirdPerMonth;
            const monthlyEggValue = monthlyEggProduction * eggPrice;
            const monthlyProfit = monthlyEggValue - monthlyFeedCost;

            const layingDelayMonths = acquisition.layingDelayMonths;
            const layingMonths = Math.max(0, 12 - layingDelayMonths);
            const nonLayingFeedCost = monthlyFeedCost * layingDelayMonths;
            const layingFeedCost = monthlyFeedCost * layingMonths;
            const annualFeedCost = monthlyFeedCost * 12;
            const annualEggValue = monthlyEggValue * layingMonths;
            const annualProfit = annualEggValue - annualFeedCost;

            const totalFirstYearCost = startingCost + annualFeedCost;
            const paybackPeriod = monthlyProfit > 0
                ? (totalFirstYearCost - annualEggValue) / monthlyProfit + 12
                : null;

            return {
                monthlyFeedCost,
                monthlyEggProduction,
                monthlyEggValue,
                monthlyProfit,
                layingDelayMonths,
                layingMonths,
                nonLayingFeedCost,
                layingFeedCost,
                annualFeedCost,
                annualEggValue,
                annualProfit,
                totalFirstYearCost,
                paybackPeriod,
            };
        },

        formatUsd(value) {
            const n = Number.isFinite(value) ? value : 0;
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
            }).format(n);
        },

        get isProfitable() {
            return this.results.monthlyProfit > 0;
        },

        get paybackColor() {
            const p = this.results.paybackPeriod;
            if (p === null || p <= 0) return 'red';
            if (p <= 12) return 'green';
            if (p <= 24) return 'orange';
            return 'red';
        },

        get paybackText() {
            const p = this.results.paybackPeriod;
            if (p === null || p <= 0) return 'Never';
            return p.toFixed(1) + ' months';
        },

        get assessmentText() {
            const r = this.results;
            const acq = this.selectedAcquisition.title.toLowerCase();
            const feed = this.selectedFeed.title.toLowerCase();
            const prod = this.selectedProduction.title.toLowerCase();

            if (r.monthlyProfit > 0) {
                let text = `With ${this.birdCount} chickens using ${acq}, ${feed}, and ${prod}, you'll make ${this.formatUsd(r.monthlyProfit)}/month once laying begins.`;
                if (r.layingDelayMonths > 0) {
                    text += ` However, with baby chicks, you'll wait ${r.layingDelayMonths} months and spend ${this.formatUsd(r.nonLayingFeedCost)} on feed before seeing any eggs.`;
                }
                if (r.paybackPeriod && r.paybackPeriod > 0) {
                    text += ` Your ${this.formatUsd(this.startingCost)} investment will pay for itself in ${r.paybackPeriod.toFixed(1)} months.`;
                }
                return text;
            }

            let text = `With ${this.birdCount} chickens using ${acq}, ${feed}, and ${prod}, you'll lose ${this.formatUsd(Math.abs(r.monthlyProfit))}/month once laying begins.`;
            if (r.layingDelayMonths > 0) {
                text += ` With baby chicks, you'd also spend ${r.layingDelayMonths} months feeding them before any egg production.`;
            }
            text += ' Consider reducing costs, choosing laying hens for faster returns, or increasing egg production to make it viable.';
            return text;
        },

        get recommendationText() {
            if (this.results.monthlyProfit > 0) {
                return 'This looks like a viable chicken-keeping venture! Consider starting with a small flock and expanding as you gain experience.';
            }
            return 'Consider starting with fewer chickens, using a more budget-friendly feeding approach, or increasing your egg prices to make this viable.';
        },

        init() {
            // Set initial selectedStartingCostId based on startingCost
            const match = this.startingCostOptions.find(o => o.cost === this.startingCost);
            if (match) {
                this.selectedStartingCostId = match.id;
            } else {
                this.selectedStartingCostId = 'custom';
            }

            // Mobile carousel IntersectionObserver
            this.$nextTick(() => {
                const track = this.$refs.carouselTrack;
                if (!track) return;
                const cards = track.querySelectorAll('.viability__option-card');
                if (!cards.length) return;
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const idx = Array.from(cards).indexOf(entry.target);
                            if (idx >= 0) this.activeCarouselIndex = idx;
                        }
                    });
                }, { root: track, threshold: 0.5 });
                cards.forEach(card => observer.observe(card));
            });
        },
    };
};
