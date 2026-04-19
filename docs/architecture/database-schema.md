# Database Schema

## Full MariaDB 10.6.22 DDL

```sql
-- ============================================
-- ChickenCare Database Schema
-- MariaDB 10.6.22
-- ============================================

-- Users table (extended by Laravel Breeze)
ALTER TABLE users ADD COLUMN tier ENUM('free', 'premium') NOT NULL DEFAULT 'free' AFTER remember_token;
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER tier;


CREATE TABLE flock_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    farm_name VARCHAR(255) NOT NULL DEFAULT 'My Chicken Farm',
    location VARCHAR(255) NULL,
    flock_size INT UNSIGNED NOT NULL DEFAULT 0,
    breed VARCHAR(500) NULL COMMENT 'Comma-separated breeds',
    start_date DATE NULL,
    hens INT UNSIGNED NOT NULL DEFAULT 0,
    roosters INT UNSIGNED NOT NULL DEFAULT 0,
    chicks INT UNSIGNED NOT NULL DEFAULT 0,
    brooding INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_flock_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_flock_profiles_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE flock_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    flock_profile_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    type ENUM('acquisition', 'laying_start', 'broody', 'hatching', 'other') NOT NULL,
    description VARCHAR(500) NOT NULL,
    affected_birds INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_flock_events_profile FOREIGN KEY (flock_profile_id) REFERENCES flock_profiles(id) ON DELETE CASCADE,
    INDEX idx_flock_events_profile_date (flock_profile_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE egg_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 0,
    size ENUM('small', 'medium', 'large', 'extra-large', 'jumbo') NULL,
    color ENUM('white', 'brown', 'blue', 'green', 'speckled', 'cream') NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_egg_entries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_egg_entries_user_date (user_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE feed_inventory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unit ENUM('kg', 'lbs') NOT NULL,
    purchase_date DATE NULL,
    expiry_date DATE NULL,
    total_cost DECIMAL(10,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_feed_inventory_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_feed_quantity CHECK (quantity >= 0),
    INDEX idx_feed_inventory_user (user_id, purchase_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    description VARCHAR(500) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_expense_amount CHECK (amount >= 0),
    INDEX idx_expenses_user_date (user_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_customers_user (user_id),
    INDEX idx_customers_active (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    sale_date DATE NOT NULL,
    dozen_count INT UNSIGNED NOT NULL DEFAULT 0,
    individual_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT chk_sales_amount CHECK (total_amount >= 0),
    CONSTRAINT chk_sales_dozen CHECK (dozen_count >= 0),
    CONSTRAINT chk_sales_individual CHECK (individual_count >= 0),
    INDEX idx_sales_user_date (user_id, sale_date DESC),
    INDEX idx_sales_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE flock_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    batch_name VARCHAR(255) NOT NULL,
    breed VARCHAR(255) NOT NULL,
    acquisition_date DATE NOT NULL,
    initial_count INT UNSIGNED NOT NULL,
    current_count INT UNSIGNED NOT NULL DEFAULT 0,
    hens_count INT UNSIGNED NOT NULL DEFAULT 0,
    roosters_count INT UNSIGNED NOT NULL DEFAULT 0,
    chicks_count INT UNSIGNED NOT NULL DEFAULT 0,
    brooding_count INT UNSIGNED NOT NULL DEFAULT 0,
    type ENUM('hens', 'roosters', 'chicks', 'mixed') NOT NULL,
    age_at_acquisition ENUM('chick', 'juvenile', 'adult') NOT NULL,
    expected_laying_start_date DATE NULL,
    actual_laying_start_date DATE NULL,
    source VARCHAR(255) NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_flock_batches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_batch_initial CHECK (initial_count > 0),
    CONSTRAINT chk_batch_current CHECK (current_count >= 0),
    INDEX idx_flock_batches_user (user_id),
    INDEX idx_flock_batches_active (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE death_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    count INT UNSIGNED NOT NULL,
    cause ENUM('predator', 'disease', 'age', 'injury', 'unknown', 'culled', 'other') NOT NULL,
    description VARCHAR(500) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_death_records_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_death_records_batch FOREIGN KEY (batch_id) REFERENCES flock_batches(id) ON DELETE CASCADE,
    CONSTRAINT chk_death_count CHECK (count > 0),
    INDEX idx_death_records_batch (batch_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE batch_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    type ENUM('health_check', 'vaccination', 'relocation', 'breeding', 'laying_start', 'brooding_start', 'brooding_stop', 'production_note', 'flock_added', 'flock_loss', 'other') NOT NULL,
    description VARCHAR(500) NOT NULL,
    affected_count INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_batch_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_batch_events_batch FOREIGN KEY (batch_id) REFERENCES flock_batches(id) ON DELETE CASCADE,
    INDEX idx_batch_events_batch (batch_id, date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Laravel Migration Mapping

```
database/migrations/
├── 0001_01_01_000000_create_users_table.php          # Breeze default
├── 0001_01_01_000001_create_cache_table.php           # Breeze default
├── 0001_01_01_000002_create_jobs_table.php            # Breeze default
├── 2026_04_07_000001_add_tier_and_admin_to_users.php
├── 2026_04_07_000002_create_flock_profiles_table.php
├── 2026_04_07_000003_create_flock_events_table.php
├── 2026_04_07_000004_create_egg_entries_table.php
├── 2026_04_07_000005_create_feed_inventory_table.php
├── 2026_04_07_000006_create_expenses_table.php
├── 2026_04_07_000007_create_customers_table.php
├── 2026_04_07_000008_create_sales_table.php
├── 2026_04_07_000009_create_flock_batches_table.php
├── 2026_04_07_000010_create_death_records_table.php
└── 2026_04_07_000011_create_batch_events_table.php
```

## Seeders

```
database/seeders/
├── DatabaseSeeder.php              # Master seeder
├── UserSeeder.php                  # 2 users: free + premium
├── FlockProfileSeeder.php          # 1 profile per user
├── FlockEventSeeder.php            # 5-10 events per profile
├── EggEntrySeeder.php              # 90 days of egg data per user
├── FeedInventorySeeder.php         # 5-8 feed entries per user
├── ExpenseSeeder.php               # 20-30 expenses per user
├── CustomerSeeder.php              # 5-10 customers per premium user
├── SaleSeeder.php                  # 30-50 sales per premium user
├── FlockBatchSeeder.php            # 3-5 batches per premium user
├── DeathRecordSeeder.php           # 2-5 records per batch
└── BatchEventSeeder.php            # 5-10 events per batch
```

---
