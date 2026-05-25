@props(['variant' => 'page-shell'])

@switch($variant)
    @case('crm-tab')
        <div class="skeleton-loader skeleton-loader--crm-tab" aria-hidden="true">
            <div class="skeleton-loader__header">
                <div class="skeleton-loader__block skeleton-loader__block--eyebrow"></div>
                <div class="skeleton-loader__block skeleton-loader__block--title"></div>
            </div>
            <div class="skeleton-loader__grid skeleton-loader__grid--cards">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton-loader__card">
                        <div class="skeleton-loader__block skeleton-loader__block--metric"></div>
                        <div class="skeleton-loader__block skeleton-loader__block--body"></div>
                    </div>
                @endfor
            </div>
            <div class="skeleton-loader__panel skeleton-loader__panel--chart"></div>
            <div class="skeleton-loader__panel skeleton-loader__panel--list">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton-loader__line"></div>
                @endfor
            </div>
        </div>
        @break

    @case('account-tab')
        <div class="skeleton-loader skeleton-loader--account-tab" aria-hidden="true">
            <div class="skeleton-loader__header">
                <div class="skeleton-loader__block skeleton-loader__block--title"></div>
                <div class="skeleton-loader__block skeleton-loader__block--body skeleton-loader__block--body-wide"></div>
            </div>
            <div class="skeleton-loader__panel skeleton-loader__panel--form">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton-loader__field">
                        <div class="skeleton-loader__block skeleton-loader__block--label"></div>
                        <div class="skeleton-loader__block skeleton-loader__block--input"></div>
                    </div>
                @endfor
            </div>
            <div class="skeleton-loader__actions">
                <div class="skeleton-loader__block skeleton-loader__block--button"></div>
            </div>
        </div>
        @break

    @default
        <div class="skeleton-loader skeleton-loader--page-shell" aria-hidden="true">
            <div class="skeleton-loader__header">
                <div class="skeleton-loader__block skeleton-loader__block--eyebrow"></div>
                <div class="skeleton-loader__block skeleton-loader__block--hero"></div>
                <div class="skeleton-loader__block skeleton-loader__block--body skeleton-loader__block--body-wide"></div>
            </div>
            <div class="skeleton-loader__grid skeleton-loader__grid--stats">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton-loader__card">
                        <div class="skeleton-loader__block skeleton-loader__block--metric"></div>
                        <div class="skeleton-loader__block skeleton-loader__block--body"></div>
                    </div>
                @endfor
            </div>
            <div class="skeleton-loader__grid skeleton-loader__grid--panels">
                <div class="skeleton-loader__panel skeleton-loader__panel--chart"></div>
                <div class="skeleton-loader__panel skeleton-loader__panel--stacked">
                    <div class="skeleton-loader__line"></div>
                    <div class="skeleton-loader__line"></div>
                    <div class="skeleton-loader__line"></div>
                </div>
            </div>
        </div>
@endswitch