<div class="crm-page__content-enter">
<div class="crm-quick-sale" x-data="quickSale()">
    {{-- Error Banner --}}
    <template x-if="error">
        <div class="crm-quick-sale__error" x-transition:enter="slide-in">
            <p x-text="error"></p>
        </div>
    </template>

    {{-- Main Form --}}
    <div class="form-card">
        <div class="form-card__header">
            <h2 class="form-card__title">{{ __('crm.quick_sale.form.title') }}</h2>
            <p class="form-card__subtitle">{{ __('crm.quick_sale.form.subtitle') }}</p>
        </div>
        <form @submit.prevent="submitSale" class="form-card__form">
            @csrf
            <div class="form-group">
                <label for="qs-customer" class="form-label">
                    {{ __('crm.quick_sale.form.fields.customer') }} <span class="form-label__required" aria-hidden="true">*</span>
                </label>
                <select id="qs-customer" class="form-select" x-model="customer_id" required @disabled($customers->isEmpty())>
                    <option value="">
                        {{ $customers->isEmpty()
                            ? __('crm.quick_sale.form.placeholders.customer_empty')
                            : __('crm.quick_sale.form.placeholders.customer') }}
                    </option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="qs-date" class="form-label">
                    {{ __('crm.quick_sale.form.fields.sale_date') }} <span class="form-label__required" aria-hidden="true">*</span>
                </label>
                <input type="date" id="qs-date" class="form-input"
                       x-model="sale_date" required
                       max="{{ today()->format('Y-m-d') }}">
            </div>

            <div class="form-row form-row--2-col">
                <div class="form-group">
                    <label for="qs-eggs" class="form-label">
                        {{ __('crm.quick_sale.form.fields.number_of_eggs') }} <span class="form-label__required" aria-hidden="true">*</span>
                    </label>
                    <input type="number" id="qs-eggs" class="form-input"
                           min="0" placeholder="{{ __('crm.quick_sale.form.placeholders.eggs_count') }}" required
                           x-model.number="eggs_count"
                           @input="recalcTotal()"
                           @click="$el.select()"
                           @focus="$el.select()">
                </div>
                <div class="form-group">
                    <label for="qs-price-per-egg" class="form-label">{{ __('crm.quick_sale.form.fields.price_per_egg') }}</label>
                    <input type="number" id="qs-price-per-egg" class="form-input"
                           step="0.01" min="0" placeholder="{{ __('crm.quick_sale.form.placeholders.price_per_egg') }}"
                           x-model.number="price_per_egg"
                           @input="recalcTotal()"
                           @click="$el.select()"
                           @focus="$el.select()">
                </div>
            </div>

            {{-- Dozen display --}}
            <template x-if="eggs_count >= 12">
                <p class="crm-quick-sale__dozen-info">
                    <span x-text="Math.floor(eggs_count / 12)"></span> {{ __('crm.quick_sale.form.labels.dozen') }} + <span x-text="eggs_count % 12"></span> {{ __('crm.quick_sale.form.labels.individual') }}
                </p>
            </template>

            <div class="form-group">
                <label for="qs-notes" class="form-label">{{ __('crm.quick_sale.form.fields.notes') }}</label>
                <textarea id="qs-notes" class="form-textarea" rows="2"
                          placeholder="{{ __('crm.quick_sale.form.placeholders.notes') }}"
                          x-model="notes"></textarea>
            </div>

            {{-- Submit Button --}}
            <div class="crm-quick-sale__submit">
                <button type="submit"
                        class="shiny-cta"
                        :class="{
                            'shiny-cta--success': success,
                            'shiny-cta--free': !success && total_amount === 0 && eggs_count > 0
                        }"
                        :disabled="submitting || success || !customer_id || eggs_count === 0"
                        x-text="buttonLabel">
                </button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    if (Alpine.data && typeof Alpine._data === 'undefined') {
        // Only register if not already registered
    }
});

function quickSale() {
    return {
        customer_id: '',
        sale_date: '{{ today()->format("Y-m-d") }}',
        eggs_count: 0,
        price_per_egg: 0.30,
        total_amount: 0,
        notes: '',
        submitting: false,
        success: false,
        error: null,
        manualTotal: false,

        get buttonLabel() {
            const amountLabel = `$${this.total_amount.toFixed(2)}`;

            if (this.submitting) return @js(__('crm.quick_sale.actions.recording'));
            if (this.success) return @js(__('crm.quick_sale.actions.recorded'));
            if (this.total_amount === 0 && this.eggs_count > 0) return @js(__('crm.quick_sale.actions.record_free'));

            return @js(__('crm.quick_sale.actions.record_with_amount', ['amount' => '__amount__']))
                .replace('__amount__', amountLabel);
        },

        recalcTotal() {
            if (!this.manualTotal) {
                this.total_amount = parseFloat((this.eggs_count * this.price_per_egg).toFixed(2));
            }
        },

        async submitSale() {
            this.error = null;
            this.submitting = true;

            const dozen_count = Math.floor(this.eggs_count / 12);
            const individual_count = this.eggs_count % 12;

            try {
                const response = await fetch('{{ route("app.sales.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'HX-Request': 'true',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        customer_id: this.customer_id,
                        sale_date: this.sale_date,
                        dozen_count,
                        individual_count,
                        total_amount: this.total_amount,
                        notes: this.notes,
                    }),
                });

                if (!response.ok) {
                    const data = await response.json();
                    if (data.errors) {
                        const firstError = Object.values(data.errors).flat()[0];
                        this.error = firstError;
                    } else {
                        this.error = data.message || @js(__('crm.quick_sale.errors.record_failed'));
                    }
                    return;
                }

                // Success
                this.success = true;
                const savedPrice = this.price_per_egg;
                setTimeout(() => {
                    this.success = false;
                    this.customer_id = '';
                    this.eggs_count = 0;
                    this.total_amount = 0;
                    this.notes = '';
                    this.manualTotal = false;
                    this.price_per_egg = savedPrice;
                }, 2500);

                // Trigger CRM refresh
                document.body.dispatchEvent(new CustomEvent('crm:changed'));
            } catch (err) {
                this.error = @js(__('crm.quick_sale.errors.network'));
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
