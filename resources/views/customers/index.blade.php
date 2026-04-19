@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="crm">
    <x-layout.page-header title="Customers" />

    <div class="crm__controls">
        <div class="crm__search" role="search">
            <input type="text"
                class="form-input crm__search-input"
                placeholder="Search customers by name..."
                aria-label="Search customers by name"
                name="search"
                value="{{ $search ?? '' }}"
                hx-get="{{ route('app.customers.index') }}"
                hx-trigger="keyup changed delay:300ms"
                hx-target="#customers-table-container"
                hx-swap="innerHTML"
                hx-vals='{"status": "{{ $currentStatus ?? 'active' }}"}'>
        </div>

        <div class="crm__filters">
            @php
                $statuses = [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'all' => 'All',
                ];
            @endphp
            @foreach($statuses as $value => $label)
                <a href="{{ route('app.customers.index', ['status' => $value]) }}"
                   class="crm__filter {{ ($currentStatus ?? 'active') === $value ? 'crm__filter--active' : '' }}"
                   hx-get="{{ route('app.customers.index', ['status' => $value]) }}"
                   hx-target="#customers-table-container"
                   hx-swap="innerHTML"
                   hx-push-url="true"
                   @if(($currentStatus ?? 'active') === $value) aria-current="true" @endif>
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <x-forms.form-card title="Add Customer" :action="route('app.customers.store')"
        hx-post="{{ route('app.customers.store') }}"
        hx-target="#customers-body"
        hx-swap="afterbegin"
        hx-on::after-request="if(event.detail.successful) this.reset()">

        <x-forms.form-row :cols="2">
            <x-forms.input name="name" label="Name" required placeholder="Customer name" />
            <x-forms.input name="phone" label="Phone" placeholder="Phone number (optional)" />
        </x-forms.form-row>

        <x-forms.form-row :cols="2">
            <x-forms.textarea name="notes" label="Notes" :rows="2" placeholder="Notes about this customer (optional)" />
            <div class="crm__submit-wrapper">
                <button type="submit" class="shiny-cta shiny-cta--full"><span>Add Customer</span></button>
            </div>
        </x-forms.form-row>
    </x-forms.form-card>

    <div id="customers-table-container">
        @if($customers->isEmpty())
            <x-ui.empty-state
                title="No customers found"
                description="Add your first customer above, or adjust your search filters."
                icon="👥"
            />
        @else
            @include('customers.partials.table', ['customers' => $customers])
        @endif
    </div>
</div>
@endsection
