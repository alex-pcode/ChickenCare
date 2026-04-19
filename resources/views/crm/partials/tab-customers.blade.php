<div class="crm-page__content-enter">
<div class="crm-customers" x-data="customerForm()">
    {{-- Header --}}
    <div class="crm-customers__header">
        <h2 class="crm-customers__title">Customers</h2>
        <button type="button" class="shiny-cta" :disabled="formOpen"
                @click="openAddForm()">
            <span>+ Add Customer</span>
        </button>
    </div>

    {{-- Error Display --}}
    <template x-if="error">
        <div class="crm-customers__error">
            <p x-text="error"></p>
            <button type="button" class="btn btn--sm btn--secondary" @click="error = null">Dismiss</button>
        </div>
    </template>

    {{-- Inline Add/Edit Form --}}
    <div x-show="formOpen" x-collapse x-cloak class="crm-customers__form-wrapper">
        <div class="form-card">
            <div class="form-card__header">
                <h2 class="form-card__title" x-text="editing ? 'Edit Customer' : 'Add New Customer'"></h2>
                <p class="form-card__subtitle">Manage customer information and contact details</p>
            </div>
            <form @submit.prevent="submitCustomer" class="form-card__form">
                @csrf
                <div class="form-row form-row--2-col">
                    <div class="form-group">
                        <label for="cf-name" class="form-label">
                            Customer Name <span class="form-label__required" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="cf-name" class="form-input"
                               placeholder="Enter customer name" required
                               x-model="form.name">
                    </div>
                    <div class="form-group">
                        <label for="cf-phone" class="form-label">Phone Number</label>
                        <input type="text" id="cf-phone" class="form-input"
                               placeholder="Enter phone number"
                               x-model="form.phone">
                    </div>
                </div>
                <div class="form-group">
                    <label for="cf-notes" class="form-label">Notes</label>
                    <textarea id="cf-notes" class="form-textarea" rows="3"
                              placeholder="Any notes about this customer..."
                              x-model="form.notes"></textarea>
                </div>
                <div class="crm-customers__form-actions">
                    <button type="submit" class="shiny-cta"
                            :disabled="submitting || !form.name.trim()">
                        <span x-show="submitting" class="crm-customers__spinner"></span>
                        <span x-text="editing ? 'Update Customer' : 'Add Customer'"></span>
                    </button>
                    <button type="button" class="btn btn--secondary" @click="closeForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Customers Table --}}
    <div id="crm-customers-table">
        @if($customers->isEmpty())
            <x-ui.empty-state
                icon="👥"
                title="No Customers Yet"
                description="Add your first customer to start tracking sales and building relationships."
            />
        @else
            @include('crm.partials.customers-table', ['customers' => $customers, 'sort' => $sort, 'dir' => $dir])
        @endif
    </div>
</div>
</div>

@push('scripts')
<script>
function customerForm() {
    return {
        formOpen: false,
        editing: null,
        submitting: false,
        error: null,
        deleteArmed: null,
        deleteTimer: null,
        form: { name: '', phone: '', notes: '' },

        openAddForm() {
            this.editing = null;
            this.form = { name: '', phone: '', notes: '' };
            this.formOpen = true;
        },

        openEditForm(customer) {
            this.editing = customer.id;
            this.form = {
                name: customer.name,
                phone: customer.phone || '',
                notes: customer.notes || '',
            };
            this.formOpen = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        closeForm() {
            this.formOpen = false;
            this.editing = null;
            this.form = { name: '', phone: '', notes: '' };
            this.error = null;
        },

        async submitCustomer() {
            this.submitting = true;
            this.error = null;

            const url = this.editing
                ? `/app/customers/${this.editing}`
                : '{{ route("app.customers.store") }}';
            const method = this.editing ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'HX-Request': 'true',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                if (!response.ok) {
                    const data = await response.json();
                    if (data.errors) {
                        this.error = Object.values(data.errors).flat()[0];
                    } else {
                        this.error = data.message || 'Failed to save customer';
                    }
                    return;
                }

                this.closeForm();
                // Refresh customers tab
                htmx.ajax('GET', '{{ route("app.crm.index", ["tab" => "customers"]) }}', {
                    target: '#crm-tab-content',
                    swap: 'innerHTML',
                });
            } catch (err) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.submitting = false;
            }
        },

        armDelete(customerId) {
            if (this.deleteArmed === customerId) {
                this.confirmDelete(customerId);
                return;
            }

            this.deleteArmed = customerId;
            clearTimeout(this.deleteTimer);
            this.deleteTimer = setTimeout(() => {
                this.deleteArmed = null;
            }, 3000);
        },

        async confirmDelete(customerId) {
            clearTimeout(this.deleteTimer);
            try {
                await fetch(`/app/customers/${customerId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'HX-Request': 'true',
                    },
                });

                const row = document.getElementById(`crm-customer-${customerId}`);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => row.remove(), 300);
                }
                this.deleteArmed = null;
                document.body.dispatchEvent(new CustomEvent('crm:changed'));
            } catch (err) {
                this.error = 'Failed to delete customer.';
                this.deleteArmed = null;
            }
        },
    };
}
</script>
@endpush
