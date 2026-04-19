@extends('layouts.app')

@section('title', 'Egg Tracking')

@section('content')
<div class="egg-counter">
    {{-- Animated Hero Section --}}
    <div class="egg-hero">
        <img
            src="/images/hen-on-eggs.webp"
            alt="Hen sitting on eggs"
            class="egg-hero__image egg-hero__image--animated"
        >
        <div class="egg-hero__badge" aria-hidden="true">
            🥚 Egg Counter
        </div>
    </div>

    <div class="flex justify-start pl-4">
        <div class="egg-hero__welcome" role="status">
            <div class="text-lg font-medium text-gray-800 dark:text-gray-200">Count your eggs!</div>
        </div>
    </div>

    <x-layout.page-header title="Egg Tracking">
        @if($entries->total() === 0)
            <x-slot:actions>
                <button type="button" class="btn btn--sm btn--secondary egg-counter__backfill-btn"
                    hx-get="{{ route('app.eggs.backfill-form') }}"
                    hx-target="#backfill-modal"
                    hx-swap="innerHTML">
                    Backfill History
                </button>
            </x-slot:actions>
        @endif
    </x-layout.page-header>

    {{-- Neumorphic Form --}}
    <form action="{{ route('app.eggs.store') }}"
          method="POST"
          hx-post="{{ route('app.eggs.store') }}"
          hx-target="#egg-entries-body"
          hx-swap="afterbegin"
          x-data="{ detailed: false, submitting: false, success: false }"
          class="egg-counter__form"
          hx-on::before-request="submitting = true; success = false"
          hx-on::after-request="if(event.detail.successful) { submitting = false; success = true; setTimeout(() => success = false, 3000); this.reset(); detailed = false; }">
        @csrf

        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Log Daily Eggs</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-600 dark:text-gray-400 text-sm mb-2">
                    Date<span class="text-red-500 ml-1">*</span>
                </label>
                <input type="date"
                       name="date"
                       class="egg-counter__input"
                       value="{{ now()->format('Y-m-d') }}"
                       max="{{ now()->format('Y-m-d') }}"
                       required>
            </div>
            <div>
                <label class="block text-gray-600 dark:text-gray-400 text-sm mb-2">
                    Number of Eggs<span class="text-red-500 ml-1">*</span>
                </label>
                <input type="number"
                       name="count"
                       class="egg-counter__input"
                       value="0"
                       min="0"
                       required
                       placeholder="0">
            </div>
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                <input type="checkbox"
                       x-model="detailed"
                       class="egg-counter__checkbox"
                       aria-controls="advanced-fields">
                Enable detailed tracking (size &amp; color)
            </label>
        </div>

        {{-- Advanced Fields --}}
        <div id="advanced-fields"
             class="egg-counter__advanced-section mb-4"
             x-show="detailed"
             x-transition.opacity.duration.200ms
             x-cloak
             :aria-expanded="detailed.toString()">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 dark:text-gray-400 text-sm mb-2">Egg Size</label>
                    <select name="size" class="egg-counter__input" x-bind:disabled="!detailed">
                        <option value="">Select size (optional)</option>
                        <option value="small">Small (42.5g / 1.5 oz)</option>
                        <option value="medium">Medium (49.6g / 1.75 oz)</option>
                        <option value="large">Large (56.8g / 2 oz)</option>
                        <option value="extra-large">Extra Large (63.8g / 2.25 oz)</option>
                        <option value="jumbo">Jumbo (70.9g / 2.5 oz)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 dark:text-gray-400 text-sm mb-2">Egg Color</label>
                    <select name="color" class="egg-counter__input" x-bind:disabled="!detailed">
                        <option value="">Select color (optional)</option>
                        <option value="white">White</option>
                        <option value="brown">Brown</option>
                        <option value="blue">Blue</option>
                        <option value="green">Green</option>
                        <option value="speckled">Speckled</option>
                        <option value="cream">Cream</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Notes field --}}
        <div class="mb-6">
            <label class="block text-gray-600 dark:text-gray-400 text-sm mb-2">Notes</label>
            <input type="text"
                   name="notes"
                   class="egg-counter__input"
                   placeholder="Optional notes about this entry">
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-center">
            <button type="submit"
                    :disabled="submitting || success"
                    class="neu-button shiny-cta bg-blue-600 text-white transition-all duration-200 font-medium rounded-lg px-6 py-2 min-w-[200px]"
                    :class="{
                        'opacity-70 cursor-not-allowed': submitting,
                        'bg-green-600 hover:bg-green-700': success
                    }">
                <template x-if="submitting">
                    <div class="flex items-center justify-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-current border-t-transparent"></div>
                        <span>Saving...</span>
                    </div>
                </template>
                <template x-else-if="success">
                    <div class="flex items-center justify-center gap-2">
                        <span>✓</span>
                        <span>Saved Successfully!</span>
                    </div>
                </template>
                <template x-else>
                    <span>Log Eggs</span>
                </template>
            </button>
        </div>
    </form>

    <div id="duplicate-confirm-area"></div>

    @if($stats)
        <div class="egg-counter__stats">
            {{-- Monthly Goal Progress --}}
            @if($yearlyGoal)
                <x-ui.progress-card
                    title="Monthly Egg Production Goal"
                    :value="$stats['thisMonthTotal']"
                    :max="round($yearlyGoal / 12)"
                    label="Monthly target ({{ number_format($yearlyGoal) }}/year)"
                    variant="detailed"
                />
            @else
                @include('eggs.partials.set-goal-cta')
            @endif

            {{-- Comparison Cards --}}
            <div class="egg-counter__stats-grid egg-counter__stats-grid--comparison">
                <x-ui.comparison-card
                    title="7 Day Comparison"
                    :before="['value' => $stats['previousWeekTotal'], 'label' => 'Previous 7 Days']"
                    :after="['value' => $stats['thisWeekTotal'], 'label' => 'Last 7 Days']"
                />
                <x-ui.comparison-card
                    title="Monthly Comparison"
                    :before="['value' => $stats['previousMonthTotal'], 'label' => 'Previous Month']"
                    :after="['value' => $stats['thisMonthTotal'], 'label' => 'This Month']"
                />
            </div>

            {{-- Stat Cards --}}
            <div class="egg-counter__stats-grid egg-counter__stats-grid--stat-cards">
                <x-ui.stat-card title="Total Eggs" :total="number_format($stats['totalEggs'])" label="eggs collected" icon="🥚" variant="corner-gradient" />
                <x-ui.stat-card title="Average Daily" :total="$stats['averageDaily']" label="eggs per day" icon="📈" variant="corner-gradient" />
                <x-ui.stat-card title="Lay Rate" total="--" label="available after flock setup" icon="🐔" variant="corner-gradient" />
                <x-ui.stat-card title="Protein Generated" :total="$stats['proteinLbs'] . ' lbs'" label="of protein" icon="🧙‍♂️" variant="corner-gradient" />
            </div>
        </div>
    @endif

    @if($entries->isEmpty())
        <x-ui.empty-state
            title="No egg entries yet"
            description="Start tracking your daily egg production."
            icon="🥚"
        />
    @else
        {{-- Recent Entries Header --}}
        <h2 class="egg-counter__table-header-title">Recent Entries</h2>

        <div class="data-table-wrapper">
            <table class="data-table data-table--striped">
                <thead class="data-table__head">
                    <tr>
                        <th scope="col" class="data-table__header">Date</th>
                        <th scope="col" class="data-table__header">Eggs</th>
                        <th scope="col" class="data-table__header">Size</th>
                        <th scope="col" class="data-table__header">Color</th>
                        <th scope="col" class="data-table__header">Notes</th>
                        <th scope="col" class="data-table__header">Actions</th>
                    </tr>
                </thead>
                <tbody id="egg-entries-body" class="data-table__body">
                    @foreach($entries as $entry)
                        @include('eggs.partials.entry-row', ['entry' => $entry])
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-tables.pagination :paginator="$entries" />
    @endif
    <div id="backfill-modal"></div>
</div>
@endsection
