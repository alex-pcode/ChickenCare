# Epic: Egg Counter - Complete Feature Replication

## Epic Goal

Replicate the React Egg Counter component exactly in Laravel + HTMX to achieve 100% feature parity with the original application at `d:\Koke\Aplikacija\src\components\features\eggs\EggCounter.tsx`.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade egg tracking with basic CRUD, stats, and forms
- **Reference Implementation:** React 19 component at `d:\Koke\Aplikacija\src\components\features\eggs\EggCounter.tsx`
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22
- **Integration Points:** EggEntry model, EggEntryController, Flock/Batch data for lay rate calculation

### Enhancement Details

**What's Being Added/Changed:**

1. **Animated Hero Section** - Add animated hen-on-eggs image with gentle rotation and scale animations
2. **Neumorphic Form Styling** - Apply neu-form/glass-card styling patterns from original CSS
3. **Enhanced Data Table** - Add color indicator dots, proper truncation, delete icon matching original
4. **Stats Section Refinements** - Exact layout with comparison cards, stat cards, and progress card
5. **Modal Components** - Historical backfill modal with proper styling
6. **Success States** - Animated success feedback on form submission
7. **Button Styling** - Shiny-cta and neumorphic button variants

**How It Integrates:**

- Builds on existing EggEntry model, factory, and seeder
- Uses existing `EggStatsService` for statistics calculations
- Integrates with Flock/Batch data for lay rate calculation
- Leverages existing HTMX patterns for form submissions

**Success Criteria:**

- Visual parity with original component achieved
- All animations and transitions work smoothly
- Stats calculations match original exactly
- Responsive behavior maintained
- Dark mode support preserved

---

## Stories

### Story 1: Visual Foundation & Animated Hero Section

**User Story:**

As a user,
I want to see the animated hen-on-eggs graphic when viewing the egg counter,
So that the page feels alive and engaging, matching the original experience.

**Acceptance Criteria:**

1. Hero section with `/hen-on-eggs.webp` image displays at top of egg counter page
2. Gentle rotation animation applied (±5° oscillating)
3. Scale animation from 0.8 to 1 on entry
4. Y-axis animation from 20px to 0 on entry
5. "🥚 Egg Counter" badge in orange positioned at top-right
6. "Count your eggs!" welcome message below animation
7. Container is responsive (h-64 = 256px height)
8. Animations respect `prefers-reduced-motion`

**Technical Requirements:**

- Use CSS keyframes for animations (no Framer Motion dependency)
- Image asset placed in `public/images/hen-on-eggs.webp`
- Alpine.js x-data for conditional show/hide if needed
- SCSS classes in `_egg-counter.scss`

---

### Story 2: Neumorphic Form & Enhanced Table Styling

**User Story:**

As a user,
I want the egg entry form and data table to match the original neumorphic design,
So that the UI feels consistent and polished.

**Acceptance Criteria:**

**Form Styling:**
1. Apply `neu-form` class to form card with proper shadows
2. Use `neu-input` class for all form inputs
3. Use `neu-checkbox` for the advanced tracking toggle
4. "Enable detailed egg tracking (size & color)" checkbox label styling
5. Glass-card-compact wrapper for advanced fields (when shown)
6. Submit button uses shiny-cta styling with success state animation
7. Success state shows checkmark + "Saved Successfully!" text

**Data Table Styling:**
1. "Recent Entries" header above table (h2)
2. Date column displays in "M d, Y" format (e.g., "Apr 15, 2026")
3. Count column in bold (font-medium)
4. Size and Color columns show "—" when empty
5. Color indicator dot (3x3 rounded-full) with matching hex colors:
   - White: #ffffff
   - Brown: #8B4513
   - Blue: #87CEEB
   - Green: #90EE90
   - Speckled: #F5DEB3
   - Cream: #FFFDD0
6. Notes column with max-width and truncation
7. Delete action shows trash icon (not text)
8. Hover state on delete button: red hover with background color change

**SCSS Additions:**

- Add neu-form, neu-input, neu-button, neu-checkbox styles
- Add color indicator styles for egg colors
- Add table row hover animations

---

### Story 3: Stats Section, Modals & Success States

**User Story:**

As a user,
I want to see complete statistics and have smooth modal interactions,
So that I can track my egg production efficiently with a polished experience.

**Acceptance Criteria:**

**Stats Cards Layout:**
1. Progress Card (Monthly Egg Production Goal):
   - Only shows when yearly_goal is set on user
   - Value = thisMonthTotal
   - Max = yearly_goal / 12
   - Label = "Monthly target (X/year)"
   - Animated progress bar

2. "Set Your Annual Goal" CTA:
   - Shows when yearly_goal is 0
   - Icon: 🎯
   - Description text
   - "Set Goal Now" button linking to account goals (placeholder for now)

3. Comparison Cards (2 columns on md+):
   - "7 Day Comparison" with previous 7 days vs last 7 days
   - "Monthly Comparison" with previous month vs this month
   - Trend indicator (↑ or ↓) with percentage
   - Icons: 📅 and 📊

4. Stat Cards (4 columns on lg+):
   - Total Eggs (icon: 🥚)
   - Average Daily (icon: 📈)
   - Lay Rate: `(averageDaily / layingHens) * 100` (icon: 🐔)
   - Protein Generated: `totalEggs * 0.125 lbs` (icon: 🧙‍♂️)

**Historical Backfill Modal:**
1. Shows "Backfill History" button when entries count is 0
2. Button has gradient background (blue-to-indigo) with icon 📊
3. Modal overlay with backdrop blur
4. Multiple date/count rows (default 5, addable)
5. Date range limited to 90 days in past
6. "Add Row" button to create additional entries
7. "Save Entries" and "Cancel" buttons
8. Close on ESC key and overlay click

**Success States:**
1. Form submit shows spinner while saving
2. Success state changes button to green with checkmark
3. Success state persists for 3 seconds
4. Form resets after successful submission
5. Advanced fields reset to hidden state

---

## Compatibility Requirements

- [ ] Existing API endpoints (store, update, destroy) remain unchanged
- [ ] Database schema: no changes required (egg_entries and users tables already correct)
- [ ] UI changes are additive only (no breaking changes to existing patterns)
- [ ] Performance impact: negligible (CSS animations only)
- [ ] Dark mode support: preserved and enhanced

---

## Risk Mitigation

### Primary Risk

Visual/functional parity gaps due to differences between React+Framer Motion and Laravel+HTMX+CSS animations

### Mitigation

1. Use CSS keyframes and transitions as direct equivalents to Framer Motion
2. Test animations on multiple browsers for consistency
3. Use SCSS variables for easy tweaking of animation parameters
4. Implement animations as progressive enhancements (work without JS)

### Rollback Plan

- SCSS additions are isolated to `_egg-counter.scss`
- New image asset can be removed from public/
- Blade component changes can be reverted with git
- No database migrations required

---

## Definition of Done

- [ ] All stories completed with acceptance criteria met
- [ ] Visual parity verified against original component (side-by-side comparison)
- [ ] Existing functionality regression tested
- [ ] Animations smooth across browsers (Chrome, Firefox, Safari)
- [ ] Dark mode verified
- [ ] Mobile responsiveness confirmed
- [ ] Accessibility verified (ARIA labels, reduced motion support)
- [ ] Code follows Laravel Boost guidelines
- [ ] Tests pass (existing test suite)
- [ ] Code formatted with Pint

---

## Visual References

**Original Component:**
- Location: `d:\Koke\Aplikacija\src\components\features\eggs\EggCounter.tsx`
- Styles: `d:\Koke\Aplikacija\src\index.css` (neu-form, glass-card, shiny-cta, etc.)
- Animation: `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedEggCounterPNG.tsx`

**Current Implementation:**
- Location: `E:\ChickenCare\resources\views\eggs\index.blade.php`
- Styles: `E:\ChickenCare\resources\scss\features\_egg-counter.scss`
- Controller: `E:\ChickenCare\app\Http\Controllers\EggEntryController.php`

---

## Technical Notes

### Image Asset Requirements

- Source image: `d:\Koke\Aplikacija\public\hen-on-eggs.webp`
- Destination: `E:\ChickenCare\public\images\hen-on-eggs.webp`
- Dimensions: Ensure responsive scaling

### CSS Animation Equivalents

| Framer Motion | CSS Equivalent |
|---------------|----------------|
| `initial={{ opacity: 0 }}` | `opacity: 0` in base class |
| `animate={{ opacity: 1 }}` | `@keyframes fadeIn { to { opacity: 1; } }` |
| `transition={{ duration: 0.8 }}` | `transition: opacity 0.8s` |
| `rotate: [-5, 5, -5]` | `@keyframes rotate { 0%, 100% { transform: rotate(-5deg); } 50% { transform: rotate(5deg); } }` |

### Alpine.js Integration

- Use `x-data` for form state (advanced toggle, success state)
- Use `x-show` / `x-transition` for smooth visibility toggles
- Use `x-init` for initialization of form values

---

## Dependencies

### External Dependencies
- None (uses existing Alpine.js and Tailwind)

### Internal Dependencies
- `EggEntry` model
- `EggStatsService` (may need enhancements for exact parity)
- Flock/Batch data for lay rate calculation
- User `yearly_egg_goal` column

### Story Dependencies
- Story 2 depends on Story 1 (visual foundation needed before form styling)
- Story 3 depends on Story 2 (form and table styling needed before stats)
