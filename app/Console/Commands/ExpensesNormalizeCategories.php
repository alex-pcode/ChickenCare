<?php

namespace App\Console\Commands;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('expenses:normalize-categories')]
#[Description('Normalize legacy expense categories to new enum values')]
class ExpensesNormalizeCategories extends Command
{
    public function handle(): int
    {
        $mapping = [
            'feed' => 'Feed',
            'medical' => 'Veterinary',
            'housing' => 'Maintenance',
            'utilities' => 'Other',
            'equipment' => 'Equipment',
            'other' => 'Other',
        ];

        $normalized = 0;
        $untouched = 0;

        Expense::chunk(100, function ($expenses) use ($mapping, &$normalized, &$untouched) {
            foreach ($expenses as $expense) {
                $current = $expense->category;

                if (array_key_exists($current, $mapping)) {
                    $expense->update(['category' => $mapping[$current]]);
                    $normalized++;
                } elseif (in_array($current, array_column(ExpenseCategory::cases(), 'value'))) {
                    $untouched++;
                } else {
                    $expense->update(['category' => 'Other']);
                    $normalized++;
                }
            }
        });

        $this->info("{$normalized} rows normalized, {$untouched} untouched.");

        return self::SUCCESS;
    }
}
