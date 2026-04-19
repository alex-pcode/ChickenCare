@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="sales">
    <x-layout.page-header title="Sales" />

    <x-forms.form-card title="Record Sale" :action="route('app.sales.store')"
        hx-post="{{ route('app.sales.store') }}"
        hx-target="#sales-body"
        hx-swap="afterbegin"
        hx-on::after-request="if(event.detail.successful) this.reset()">

        <x-forms.form-row :cols="2">
            <x-forms.date-input name="sale_date" label="Date" :value="today()->format('Y-m-d')" required />
            <x-forms.input name="total_amount" type="number" step="0.01" min="0" label="Amount ($)" required />
        </x-forms.form-row>

        <x-forms.form-row :cols="2">
            <x-forms.input name="dozen_count" type="number" min="0" label="Dozens" value="0" />
            <x-forms.input name="individual_count" type="number" min="0" label="Individual" value="0" />
        </x-forms.form-row>

        <x-forms.form-row :cols="2">
            <div>
                <label for="customer_id" class="form-label">Customer</label>
                <select name="customer_id" id="customer_id" class="form-input">
                    <option value="">Walk-in / No Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sales__paid-field">
                <label class="form-label">
                    <input type="checkbox" name="paid" value="1" aria-label="Mark sale as paid" />
                    Paid
                </label>
            </div>
        </x-forms.form-row>

        <x-forms.textarea name="notes" label="Notes" :rows="2" placeholder="Optional notes about this sale" />

        <x-forms.submit-button />
    </x-forms.form-card>

    @if($sales->isEmpty())
        <x-ui.empty-state
            title="No sales recorded yet"
            description="Record your first egg sale above to start tracking your revenue."
            icon="🥚"
        />
    @else
        <div id="sales-table-container">
            @include('sales.partials.table', ['sales' => $sales])
        </div>
    @endif
</div>
@endsection
