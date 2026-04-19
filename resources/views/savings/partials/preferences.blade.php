<details class="savings__preferences">
    <summary class="savings__preferences-toggle">⚙️ Savings Preferences</summary>

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
                Egg Price (€)
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
                Goal
                <select name="chicken_goal" class="savings__preferences-input">
                    @foreach(\App\Enums\ChickenGoal::cases() as $goal)
                        <option value="{{ $goal->value }}" @selected(old('chicken_goal', $user->chicken_goal?->value) === $goal->value)>
                            {{ $goal->label() }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <button type="submit" class="savings__preferences-save">Save</button>
    </form>
</details>
