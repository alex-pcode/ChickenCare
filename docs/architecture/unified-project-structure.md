# Unified Project Structure

```
ChickenCare/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── ExpensesNormalizeCategories.php
│   │       └── WarmupRoutes.php
│   ├── Enums/
│   │   ├── BatchAgeAtAcquisition.php
│   │   ├── BatchEventType.php
│   │   ├── ChickenGoal.php
│   │   ├── DeathCause.php
│   │   ├── ExpenseCategory.php
│   │   └── FeedType.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                         # Breeze auth controllers (9 files)
│   │   │   ├── AccountController.php
│   │   │   ├── BatchEventController.php
│   │   │   ├── CrmController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DeathRecordController.php
│   │   │   ├── EggEntryController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── FeedInventoryController.php
│   │   │   ├── FlockBatchController.php
│   │   │   ├── FlockEventController.php
│   │   │   ├── FlockProfileController.php
│   │   │   ├── ImportController.php
│   │   │   ├── SaleController.php
│   │   │   ├── SalesReportController.php
│   │   │   ├── SavingsController.php
│   │   │   ├── SavingsPreferencesController.php
│   │   │   └── ViabilityController.php
│   │   ├── Middleware/
│   │   │   ├── EnsurePremiumTier.php
│   │   │   └── DetectHtmx.php
│   │   └── Requests/                         # 27 Form Requests + 1 Auth
│   ├── Models/                               # 11 Eloquent models
│   ├── Policies/                             # 10 authorization policies
│   ├── Services/                             # 12 service classes
│   │   ├── CrmReportsService.php
│   │   ├── DashboardService.php
│   │   ├── EggStatsService.php
│   │   ├── ExpenseStatsService.php
│   │   ├── FeedStatsService.php
│   │   ├── FlockBatchStatsService.php
│   │   ├── ImportDataService.php
│   │   ├── ReportService.php
│   │   ├── SavingsAnalysisService.php
│   │   ├── SavingsService.php
│   │   ├── SetupProgressService.php
│   │   └── ViabilityService.php
│   ├── Support/
│   │   ├── Money.php
│   │   ├── SavingsPeriod.php
│   │   └── WeekStart.php
│   ├── Traits/
│   │   └── HandlesHtmx.php
│   ├── View/
│   │   └── Components/
│   │       ├── AppLayout.php
│   │       └── GuestLayout.php
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   └── app.php
├── config/                                   # Standard Laravel config
├── database/
│   ├── factories/                            # 11 model factories
│   ├── migrations/                           # 19 migrations
│   └── seeders/                              # 12 seeders
├── public/
│   └── index.php
├── resources/
│   ├── js/
│   │   ├── app.js                            # HTMX + Alpine + Chart.js
│   │   ├── bootstrap.js
│   │   └── charts/
│   │       └── expense-pie-chart.js
│   ├── scss/                                 # ~30 SCSS files
│   │   ├── app.scss
│   │   ├── _variables.scss
│   │   ├── _mixins.scss
│   │   ├── _base.scss
│   │   ├── _layout.scss
│   │   ├── _animations.scss
│   │   ├── components/                       # 12 component stylesheets
│   │   └── features/                         # 13 feature stylesheets
│   └── views/                                # ~100+ Blade files
│       ├── layouts/                          # app, guest
│       ├── components/                       # ui/, forms/, tables/, modals/, layout/
│       ├── dashboard/                        # index + 7 partials
│       ├── eggs/                             # index + 7 partials
│       ├── flock/                            # index + 7 partials
│       ├── batches/                          # create, edit, index, show + 11 partials
│       ├── expenses/                         # index + 9 partials
│       ├── feed/                             # index + 11 partials
│       ├── customers/                        # index + 3 partials
│       ├── sales/                            # index, reports + 4 partials
│       ├── crm/                             # index + partials
│       ├── savings/                          # index + 6 partials
│       ├── viability/                        # index + 1 partial
│       ├── import/                           # index
│       ├── account/                          # index + 4 tab partials
│       ├── app/                             # components-showcase, placeholder
│       ├── partials/                        # premium-gate
│       ├── auth/                            # 6 auth views
│       └── welcome.blade.php
├── routes/
│   ├── web.php
│   ├── auth.php
│   └── console.php
├── tests/
│   ├── Feature/                              # 45+ feature test files
│   │   ├── Auth/                            # 5 auth tests
│   │   ├── FlockBatches/                    # 2 batch detail tests
│   │   └── Http/Controllers/               # 1 stats endpoint test
│   ├── Unit/                                 # 40+ unit test files
│   │   ├── Enums/                           # 3 enum tests
│   │   ├── Models/                          # 1 model test
│   │   ├── Services/                        # 2 service tests
│   │   └── Views/                           # 1 view test
│   └── TestCase.php
├── docs/
│   └── architecture/                         # Architecture documentation
├── docker-compose.yml                        # MariaDB container
├── artisan
├── composer.json
├── package.json
├── vite.config.js
└── phpunit.xml
```

---
