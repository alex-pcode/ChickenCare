# Testing Strategy

## Test Distribution

- **Unit (~45%):** Models, Services, Policies, Enums, Support classes (~40+ test files)
- **Feature (~55%):** HTTP requests, CRUD, validation, HTMX responses, middleware, auth flows (~45+ test files)

### Test Organization

```
tests/
├── Feature/                        # 45+ files
│   ├── Auth/                      # Login, Logout, Registration, PasswordReset, AuthRedirect
│   ├── FlockBatches/              # BatchDetail, BatchDetailModals
│   ├── Http/Controllers/          # ExpenseStatsEndpoint
│   ├── *ControllerTest.php        # Per-controller CRUD tests
│   ├── *DataLayerTest.php         # Database query tests
│   └── *EdgeCaseTest.php          # Boundary condition tests
├── Unit/                           # 40+ files
│   ├── Enums/                     # BatchAgeAtAcquisition, BatchEventType, DeathCause
│   ├── Models/                    # FlockBatchComposition
│   ├── Services/                  # ExpenseStatsService, FlockBatchStatsService
│   ├── Views/                     # ExpenseIndexHero
│   ├── *PolicyTest.php            # Per-model policy tests
│   ├── *ServiceTest.php           # Per-service unit tests
│   └── *ModelTest.php             # Model logic tests
└── TestCase.php
```

## Running Tests

```bash
php artisan test --compact                          # All tests
php artisan test --compact --filter=EggEntry        # Specific test
php artisan test --compact tests/Feature/ExampleTest.php  # Specific file
```

## Key Testing Patterns

- Every resource controller test includes HTMX and standard request paths
- Every resource controller test includes cross-user access denial
- `RefreshDatabase` trait for clean state per test
- Factory states for user tiers: `User::factory()->premium()->create()`
- Edge case tests cover empty states, boundary values, and error scenarios
- Service tests validate business logic independently from HTTP layer
- PHPUnit classes only — no Pest syntax

---
