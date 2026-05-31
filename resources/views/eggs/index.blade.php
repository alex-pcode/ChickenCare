@extends('layouts.app')

@section('title', __('eggs.page.title'))

@php($skel = $skel ?? false)

@section('content')
<div class="egg-counter"
     x-data="{
        heroStatus: '{{ $skel ? 'neutral' : ($loggedToday ? 'good' : ($daysSinceLastEntry === null ? 'neutral' : 'warn')) }}',
        statusIcon: '{{ $skel ? '🥚' : ($loggedToday ? '✅' : ($daysSinceLastEntry === null ? '🥚' : ($daysSinceLastEntry <= 1 ? '⏰' : '⚠️'))) }}',
        statusTitle: `{{ $skel ? '' : ($loggedToday ? __('eggs.status.logged_today_title') : ($daysSinceLastEntry === null ? __('eggs.status.none_yet_title') : ($daysSinceLastEntry <= 1 ? __('eggs.status.not_today_title') : __('eggs.status.overdue_title', ['days' => $daysSinceLastEntry ?? 0])))) }}`,
        statusShort: `{{ $skel ? '' : ($loggedToday ? __('eggs.status.logged_today_short', ['count' => $todayTotal, 'eggs' => $todayTotal === 1 ? __('eggs.status.egg') : __('eggs.status.eggs')]) : ($daysSinceLastEntry === null ? __('eggs.status.none_yet_short') : ($daysSinceLastEntry <= 1 ? __('eggs.status.not_today_short') : __('eggs.status.overdue_short', ['days' => $daysSinceLastEntry ?? 0])))) }}`,
        statusDetail: `{{ $skel ? '' : ($loggedToday ? __('eggs.status.logged_today_detail', ['count' => $todayTotal, 'eggs' => $todayTotal === 1 ? __('eggs.status.egg') : __('eggs.status.eggs'), 'date' => $lastEntryDate?->format('M j, Y') ?? '']) : ($daysSinceLastEntry === null ? __('eggs.status.none_yet_detail') : ($daysSinceLastEntry <= 1 ? __('eggs.status.not_today_detail', ['ago' => $lastEntryDate?->diffForHumans() ?? '']) : __('eggs.status.overdue_detail', ['date' => $lastEntryDate?->format('M j, Y') ?? ''])))) }}`,
        todayCount: {{ $todayTotal }},
        eggWord: '{{ __('eggs.status.eggs') }}',
        eggWordSingular: '{{ __('eggs.status.egg') }}',
        todayDate: '{{ now()->format('M j, Y') }}',
        logEggs(count) {
            this.todayCount += parseInt(count) || 0;
            this.heroStatus = 'good';
            this.statusIcon = '✅';
            this.statusTitle = '{{ __('eggs.status.logged_today_title') }}';
            const eggs = this.todayCount === 1 ? this.eggWordSingular : this.eggWord;
            this.statusShort = this.todayCount + ' ' + eggs + ' today';
            this.statusDetail = this.todayCount + ' ' + eggs + ' logged for ' + this.todayDate + '.';
        }
     }">
    {{-- Animated Hero Section --}}
    <div class="egg-hero"
         :class="'egg-hero--' + heroStatus">
        @if (! $skel)
            <div class="egg-hero__badge" aria-hidden="true">
                <span class="egg-hero__badge-icon"
                      :class="{
                        'egg-hero__badge-icon--success': heroStatus === 'good',
                        'egg-hero__badge-icon--warning': heroStatus === 'warn',
                        'egg-hero__badge-icon--neutral': heroStatus === 'neutral',
                      }"
                      x-text="statusIcon"></span>
            </div>
        @endif
        <div class="egg-hero__media">
            @if ($skel)
                <x-ui.skel block="block" style="width:100%;height:14rem;border-radius:1rem;" />
            @else
                <img
                    src="/images/hen-on-eggs.webp"
                    alt="{{ __('eggs.hero.image_alt') }}"
                    class="egg-hero__image egg-hero__image--animated"
                >
            @endif
        </div>

        <div class="egg-hero__side">
            @if ($skel)
                <div class="egg-hero__status egg-hero__status--neutral" role="status">
                    <div class="egg-hero__status-text">
                        <h2 class="egg-hero__status-title"><x-ui.skel block="title" /></h2>
                        <p class="egg-hero__status-detail"><x-ui.skel block="body-wide" /></p>
                    </div>
                </div>
                <x-ui.comparison-card
                    :title="__('eggs.comparison.seven_day_title')"
                    :before="['value' => 0, 'label' => '']"
                    :after="['value' => 0, 'label' => '']"
                    :loading="true"
                />
            @else
                <div class="egg-hero__status"
                     :class="{
                        'egg-hero__status--success': heroStatus === 'good',
                        'egg-hero__status--warning': heroStatus === 'warn',
                        'egg-hero__status--neutral': heroStatus === 'neutral',
                     }"
                     role="status">
                    <div class="egg-hero__status-text">
                        <h2 class="egg-hero__status-title">
                            <span class="d-none-mobile" x-text="statusTitle"></span>
                            <span class="d-only-mobile" x-text="statusShort"></span>
                        </h2>
                        <p class="egg-hero__status-detail d-none-mobile" x-text="statusDetail"></p>
                    </div>
                </div>

                @if($stats)
                    <x-ui.comparison-card
                        id="egg-hero-week"
                        :title="__('eggs.comparison.seven_day_title')"
                        :before="['value' => $stats['previousWeekTotal'], 'label' => __('eggs.comparison.previous_7_days')]"
                        :after="['value' => $stats['thisWeekTotal'], 'label' => __('eggs.comparison.last_7_days')]"
                    />
                @endif
            @endif
        </div>
    </div>

    {{-- Neumorphic Form --}}
    <form @if(! $skel) action="{{ route('app.eggs.store') }}"
          method="POST"
          hx-post="{{ route('app.eggs.store') }}"
          hx-target="#egg-entries-body"
          hx-swap="afterbegin"
          data-offline-queue="eggs"
          x-data="{ detailed: false, submitting: false, success: false }"
          x-on:htmx:before-request="submitting = true; success = false; $el.querySelector('#egg-form-errors')?.replaceChildren()"
          x-on:htmx:after-request="if ($event.detail.successful) { let c = $el.querySelector('[name=count]').value; submitting = false; success = true; setTimeout(() => success = false, 3000); logEggs(c); $el.reset(); detailed = false; }" @endif
          class="egg-counter__form">
        @if (! $skel) @csrf @endif

        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">
            @if ($skel) <x-ui.skel block="title" /> @else {{ __('eggs.form.title') }} @endif
        </h3>

        <div id="egg-form-errors"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-forms.input name="date" type="date" :label="__('eggs.form.date_label')" :value="now()->format('Y-m-d')" :required="true" :loading="$skel" />
            <x-forms.input name="count" type="number" min="0" step="1" :label="__('eggs.form.count_label')" value="0" :required="true" :loading="$skel" onfocus="this.select()" onclick="this.select()" />
        </div>

        @if (! $skel)
        <div class="mb-4">
            <label class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                <input type="checkbox"
                       x-model="detailed"
                       class="egg-counter__checkbox"
                       aria-controls="advanced-fields">
                {{ __('eggs.form.detailed_toggle') }}
            </label>
        </div>

        <div id="advanced-fields"
             class="egg-counter__advanced-section mb-4"
             x-show="detailed"
             x-transition.opacity.duration.200ms
             x-cloak
             :aria-expanded="detailed.toString()">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 dark:text-gray-400 text-sm mb-2">{{ __('eggs.form.size_label') }}</label>
                    <select name="size" class="egg-counter__input" x-bind:disabled="!detailed">
                        <option value="">{{ __('eggs.form.size_placeholder') }}</option>
                        @foreach(__('eggs.form.sizes') as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 dark:text-gray-400 text-sm mb-2">{{ __('eggs.form.color_label') }}</label>
                    <select name="color" class="egg-counter__input" x-bind:disabled="!detailed">
                        <option value="">{{ __('eggs.form.color_placeholder') }}</option>
                        @foreach(__('eggs.form.colors') as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <x-forms.input name="notes" :label="__('eggs.form.notes_label')" :placeholder="__('eggs.form.notes_placeholder')" />
            </div>
        </div>
        @endif

        <div class="egg-counter__submit">
            <x-forms.submit-button :label="__('eggs.form.submit')" :loading="$skel" />
        </div>
    </form>

    <div id="duplicate-confirm-area"></div>

    @if ($skel || $stats)
        @include('eggs.partials.stats', ['skel' => $skel, 'stats' => $stats, 'yearlyGoal' => $yearlyGoal])

        {{-- Refreshes the stats section (and hero 7-day card, via OOB) whenever an
             egg entry is logged/updated. Kept out of the skeleton render. --}}
        @unless ($skel)
            <div hidden
                 hx-get="{{ route('app.eggs.stats') }}"
                 hx-trigger="eggLogged from:body"
                 hx-target="#egg-stats"
                 hx-swap="outerHTML"></div>
        @endunless
    @endif

    @php($isEmpty = ! $skel && $entries->isEmpty())

    {{-- The table (and #egg-entries-body) is always rendered so the logging form
         has a resolvable hx-target. Without it, htmx aborts the first submit with
         htmx:targetError and the server-side "first entry" redirect never runs. --}}
    @unless ($isEmpty)
        <h2 class="egg-counter__table-header-title">
            @if ($skel) <x-ui.skel block="title" /> @else {{ __('eggs.table.header') }} @endif
        </h2>
    @endunless

        <div class="data-table-wrapper">
            <table class="data-table {{ $isEmpty ? '' : 'data-table--striped' }}">
                @unless ($isEmpty)
                <thead class="data-table__head">
                    <tr>
                        <th scope="col" class="data-table__header">{{ __('eggs.table.columns.date') }}</th>
                        <th scope="col" class="data-table__header">{{ __('eggs.table.columns.eggs') }}</th>
                        <th scope="col" class="data-table__header">{{ __('eggs.table.columns.size') }}</th>
                        <th scope="col" class="data-table__header">{{ __('eggs.table.columns.color') }}</th>
                        <th scope="col" class="data-table__header">{{ __('eggs.table.columns.notes') }}</th>
                        <th scope="col" class="data-table__header">{{ __('eggs.table.columns.actions') }}</th>
                    </tr>
                </thead>
                @endunless
                <tbody id="egg-entries-body" class="data-table__body">
                    @if ($skel)
                        @for ($i = 0; $i < 8; $i++)
                            <tr class="data-table__row">
                                <td class="data-table__cell"><x-ui.skel block="label" /></td>
                                <td class="data-table__cell"><x-ui.skel block="pill" /></td>
                                <td class="data-table__cell"><x-ui.skel block="label" /></td>
                                <td class="data-table__cell"><x-ui.skel block="label" /></td>
                                <td class="data-table__cell"><x-ui.skel block="body-wide" /></td>
                                <td class="data-table__cell"><x-ui.skel block="pill" /></td>
                            </tr>
                        @endfor
                    @else
                        @forelse($entries as $entry)
                            @include('eggs.partials.entry-row', ['entry' => $entry])
                        @empty
                            <tr>
                                <td colspan="6" class="data-table__cell">
                                    <x-ui.empty-state
                                        :title="__('eggs.empty_state.title')"
                                        :description="__('eggs.empty_state.description')"
                                        icon="🥚"
                                    >
                                        <button type="button" class="shiny-cta"
                                                hx-get="{{ route('app.eggs.backfill-form') }}"
                                                hx-target="#backfill-modal"
                                                hx-swap="innerHTML">
                                            <span>{{ __('eggs.backfill.button') }}</span>
                                        </button>
                                    </x-ui.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        @if (! $skel && $entries->hasPages())
            <x-tables.pagination :paginator="$entries" />
        @endif

    <div id="backfill-modal"></div>
</div>
@endsection
