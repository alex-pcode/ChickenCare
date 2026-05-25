window.viabilityCalculator = function(defaults, translations = {}) {
    const i18n = translations.js ?? {};

    return {
        translations,
        i18n,
        birdCount: defaults.birdCount ?? 5,
        eggPrice: defaults.eggPrice ?? 0.30,
        startingCost: defaults.startingCost ?? 50,
        selectedStartingCostId: 'minimal',
        selectedAcquisitionId: 'laying_hens',
        activeCarouselIndex: 0,
        startingCostOptions: i18n.startingCostOptions ?? [],
        acquisitionOptions: i18n.acquisitionOptions ?? [],

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

        feedOptions: i18n.feedOptions ?? [],

        productionOptions: i18n.productionOptions ?? [],

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

        interpolate(template, params = {}) {
            return Object.entries(params).reduce(
                (result, [key, value]) => result.replaceAll(`:${key}`, value),
                template,
            );
        },

        formatUsd(value) {
            const n = Number.isFinite(value) ? value : 0;
            return new Intl.NumberFormat(i18n.locale ?? 'en-US', {
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
            if (p === null || p <= 0) return this.i18n.payback?.never ?? 'Never';

            return this.interpolate(this.i18n.payback?.months ?? ':count months', {
                count: p.toFixed(1),
            });
        },

        get assessmentText() {
            const r = this.results;
            const acq = this.selectedAcquisition.title.toLowerCase();
            const feed = this.selectedFeed.title.toLowerCase();
            const prod = this.selectedProduction.title.toLowerCase();
            const assessment = this.i18n.assessment ?? {};

            if (r.monthlyProfit > 0) {
                let text = this.interpolate(assessment.positive_base ?? '', {
                    count: this.birdCount,
                    acquisition: acq,
                    feed,
                    production: prod,
                    profit: this.formatUsd(r.monthlyProfit),
                });

                if (r.layingDelayMonths > 0) {
                    text += this.interpolate(assessment.positive_delay ?? '', {
                        months: r.layingDelayMonths,
                        cost: this.formatUsd(r.nonLayingFeedCost),
                    });
                }

                if (r.paybackPeriod && r.paybackPeriod > 0) {
                    text += this.interpolate(assessment.positive_payback ?? '', {
                        investment: this.formatUsd(this.startingCost),
                        months: r.paybackPeriod.toFixed(1),
                    });
                }

                return text;
            }

            let text = this.interpolate(assessment.negative_base ?? '', {
                count: this.birdCount,
                acquisition: acq,
                feed,
                production: prod,
                loss: this.formatUsd(Math.abs(r.monthlyProfit)),
            });

            if (r.layingDelayMonths > 0) {
                text += this.interpolate(assessment.negative_delay ?? '', {
                    months: r.layingDelayMonths,
                });
            }

            text += assessment.negative_suffix ?? '';

            return text;
        },

        get recommendationText() {
            if (this.results.monthlyProfit > 0) {
                return this.i18n.recommendations?.positive ?? '';
            }

            return this.i18n.recommendations?.negative ?? '';
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
