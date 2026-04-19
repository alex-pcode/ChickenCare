@extends('layouts.app')

@section('title', 'Card Components Showcase')

@section('content')

<div x-data="{ activeTab: 'components' }">
    <h1 class="showcase-header">Card Components Showcase</h1>

    {{-- Tab Navigation --}}
    <div class="showcase-tabs-wrapper">
        <div class="showcase-tabs" role="tablist" aria-label="Showcase sections">
            @foreach(['components' => 'Components', 'layouts' => 'Layouts', 'modals' => 'Modals', 'tables' => 'Tables', 'forms' => 'Forms', 'charts' => 'Charts', 'timeline' => 'Timeline'] as $key => $label)
                <button class="showcase-tabs__tab"
                        :class="{ 'showcase-tabs__tab--active': activeTab === '{{ $key }}' }"
                        @click="activeTab = '{{ $key }}'"
                        role="tab"
                        :aria-selected="activeTab === '{{ $key }}' ? 'true' : 'false'"
                        aria-controls="panel-{{ $key }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Tab Panels --}}
    <div x-show="activeTab === 'components'" x-cloak id="panel-components" role="tabpanel">
        <div class="showcase-content">
            @include('app.showcase-tabs.components')
        </div>
    </div>

    <div x-show="activeTab === 'layouts'" x-cloak id="panel-layouts" role="tabpanel">
        <div class="showcase-content">
            @include('app.showcase-tabs.layouts')
        </div>
    </div>

    <div x-show="activeTab === 'modals'" x-cloak id="panel-modals" role="tabpanel">
        <div class="showcase-content">
            @include('app.showcase-tabs.modals')
        </div>
    </div>

    <div x-show="activeTab === 'tables'" x-cloak id="panel-tables" role="tabpanel">
        <div class="showcase-content">
            @include('app.showcase-tabs.tables')
        </div>
    </div>

    <div x-show="activeTab === 'forms'" x-cloak id="panel-forms" role="tabpanel">
        <div class="showcase-content">
            @include('app.showcase-tabs.forms')
        </div>
    </div>

    <div x-show="activeTab === 'charts'" x-cloak id="panel-charts" role="tabpanel">
        @include('app.showcase-tabs.charts')
    </div>

    <div x-show="activeTab === 'timeline'" x-cloak id="panel-timeline" role="tabpanel">
        @include('app.showcase-tabs.timeline')
    </div>
</div>

@endsection
