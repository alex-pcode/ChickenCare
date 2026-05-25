<details class="savings__preferences">
    <summary class="savings__preferences-toggle">{{ __('savings.preferences.toggle') }}</summary>

    <form
        class="savings__preferences-form"
        method="POST"
        action="{{ route('app.savings.preferences.update') }}"
        hx-patch="{{ route('app.savings.preferences.update') }}"
        hx-swap="none"
    >
        @csrf
        @method('PATCH')

        <div class="savings__preferences-fields">
            <label class="savings__preferences-label">
                {{ __('savings.preferences.egg_price') }}
                <input
                    type="number"
                    name="egg_price"
                    value="{{ old('egg_price', $user->egg_price) }}"
                    step="0.01"
                    min="0"
                    max="999.99"
                    class="savings__preferences-input"
                />
            </label>

            <label class="savings__preferences-label">
                {{ __('savings.preferences.goal') }}
                <select name="chicken_goal" class="savings__preferences-input">
                    @foreach(\App\Enums\ChickenGoal::cases() as $goal)
                        <option value="{{ $goal->value }}" @selected(old('chicken_goal', $user->chicken_goal?->value) === $goal->value)>
                            {{ $goal->label() }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <button type="submit" class="savings__preferences-save">{{ __('savings.preferences.save') }}</button>
    </form>
</details>
