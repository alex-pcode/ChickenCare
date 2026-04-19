{{-- Data Table with Status Badges --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Data Table Examples</h2>
        <p class="showcase-section__subtitle">Tables for displaying structured data with sorting and actions</p>
    </div>

    {{-- Egg Production Table --}}
    <x-tables.data-table :headers="['Date', 'Flock', 'Eggs Collected', 'Status', 'Notes', 'Actions']">
        <tr class="data-table__row">
            <td class="data-table__cell">2026-04-09</td>
            <td class="data-table__cell">Flock A</td>
            <td class="data-table__cell">24</td>
            <td class="data-table__cell"><span class="badge badge--indigo">Active</span></td>
            <td class="data-table__cell">Normal production</td>
            <td class="data-table__cell">
                <button class="btn btn--sm btn--secondary" aria-label="Edit entry">Edit</button>
            </td>
        </tr>
        <tr class="data-table__row">
            <td class="data-table__cell">2026-04-09</td>
            <td class="data-table__cell">Flock B</td>
            <td class="data-table__cell">18</td>
            <td class="data-table__cell"><span class="badge badge--indigo-dark">In Progress</span></td>
            <td class="data-table__cell">Slight decrease</td>
            <td class="data-table__cell">
                <button class="btn btn--sm btn--secondary" aria-label="Edit entry">Edit</button>
            </td>
        </tr>
        <tr class="data-table__row">
            <td class="data-table__cell">2026-04-08</td>
            <td class="data-table__cell">Flock C</td>
            <td class="data-table__cell">12</td>
            <td class="data-table__cell"><span class="badge badge--indigo-darkest">New</span></td>
            <td class="data-table__cell">New flock, ramping up</td>
            <td class="data-table__cell">
                <button class="btn btn--sm btn--secondary" aria-label="Edit entry">Edit</button>
            </td>
        </tr>
        <tr class="data-table__row">
            <td class="data-table__cell">2026-04-08</td>
            <td class="data-table__cell">Flock A</td>
            <td class="data-table__cell">22</td>
            <td class="data-table__cell"><span class="badge badge--success">Completed</span></td>
            <td class="data-table__cell">Good day</td>
            <td class="data-table__cell">
                <button class="btn btn--sm btn--secondary" aria-label="Edit entry">Edit</button>
            </td>
        </tr>
    </x-tables.data-table>
</div>

{{-- Empty State --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Empty State</h2>
        <p class="showcase-section__subtitle">Displayed when no data is available</p>
    </div>

    <x-ui.empty-state
        title="No Egg Entries Yet"
        description="Start tracking your flock's egg production by adding your first entry."
        action="#"
        actionLabel="Add First Entry"
    />
</div>

{{-- Badges --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Badge Variants</h2>
        <p class="showcase-section__subtitle">Status indicators and labels</p>
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        <span class="badge">Default</span>
        <span class="badge badge--success">Healthy</span>
        <span class="badge badge--warning">Low Stock</span>
        <span class="badge badge--error">Critical</span>
        <span class="badge badge--premium">Premium</span>
        <span class="badge badge--indigo">Active</span>
        <span class="badge badge--indigo-dark">In Progress</span>
        <span class="badge badge--indigo-darkest">New</span>
    </div>
</div>
