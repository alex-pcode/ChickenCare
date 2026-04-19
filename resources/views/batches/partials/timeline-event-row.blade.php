@php
    $isEven = $index % 2 === 0;
    $delay  = min($index * 50, 400);
@endphp

<div
    class="relative flock-timeline-entry"
    style="animation-delay: {{ $delay }}ms"
    data-index="{{ $index }}">

    {{-- Desktop: alternating layout --}}
    <div class="hidden lg:flex items-center gap-8 {{ $isEven ? '' : 'flex-row-reverse' }}">
        <div class="w-[calc(50%-2rem)] {{ $isEven ? 'text-right' : 'text-left' }}">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-5 relative">
                <div class="flex gap-2 mb-3 {{ $isEven ? 'justify-end' : 'justify-start' }}">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                        {{ $event->date->format('M j, Y') }}
                    </span>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        {{ $event->type instanceof \App\Enums\BatchEventType ? $event->type->label() : \Illuminate\Support\Str::title(str_replace('_', ' ', $event->type)) }}
                    </span>
                </div>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ $event->description }}</h4>
                @if($event->affected_count)
                    <span class="inline-flex items-center gap-1 text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full font-medium mb-2">
                        🐔 {{ $event->affected_count }} birds affected
                    </span>
                @endif
                @if($event->notes)
                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border-l-4 border-gray-200 dark:border-gray-600">
                        <p class="text-xs text-gray-600 dark:text-gray-400 italic">{{ $event->notes }}</p>
                    </div>
                @endif
                <div class="absolute top-1/2 {{ $isEven ? '-right-8' : '-left-8' }} transform -translate-y-1/2 w-8 h-0.5 bg-gray-300 dark:bg-gray-600"></div>
            </div>
        </div>

        <div class="relative z-10 shrink-0" aria-hidden="true">
            <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-4 border-indigo-400 dark:border-indigo-500 flex items-center justify-center text-base">
                {{ $event->type instanceof \App\Enums\BatchEventType ? $event->type->icon() : '📋' }}
            </div>
        </div>

        <div class="w-[calc(50%-2rem)]"></div>
    </div>

    {{-- Mobile: stacked layout --}}
    <div class="lg:hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-base" aria-hidden="true">
                {{ $event->type instanceof \App\Enums\BatchEventType ? $event->type->icon() : '📋' }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-2 mb-1">
                    <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                        {{ $event->date->format('M j, Y') }}
                    </span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                        {{ $event->type instanceof \App\Enums\BatchEventType ? $event->type->label() : \Illuminate\Support\Str::title(str_replace('_', ' ', $event->type)) }}
                    </span>
                </div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ $event->description }}</h4>
                @if($event->affected_count)
                    <span class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 mb-1">
                        🐔 {{ $event->affected_count }} birds
                    </span>
                @endif
                @if($event->notes)
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-1">{{ $event->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
