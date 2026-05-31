<?php

return [
    'page' => [
        'title' => 'Dashboard',
    ],
    'welcome' => [
        'heading' => 'Welcome :name',
        'messages' => [
            'start' => "Let's get your flock set up.",
            'progress' => "You're :percentage% set up — let's finish getting your flock ready.",
            'complete' => "Your flock is all set up. Here's today's snapshot.",
        ],
    ],
    'setup' => [
        'phase_headings' => [
            'new' => '🚀 Getting Started',
            'getting-started' => '📈 Building Your Farm',
            'active' => '⚡ Advanced Features',
            'power' => '🎯 Final Steps',
        ],
        'progress_title' => 'Setup Progress',
        'points' => 'pts',
        'phases' => [
            'new' => [
                'label' => 'New User',
                'message' => 'Get started with basic setup',
            ],
            'getting-started' => [
                'label' => 'Getting Started',
                'message' => 'Expand to core features',
            ],
            'active' => [
                'label' => 'Active User',
                'message' => 'Unlock advanced features',
            ],
            'power' => [
                'label' => 'Power User',
                'message' => "You're using all features!",
            ],
        ],
        'items' => [
            'setup-flock' => [
                'label' => 'Set up your flock',
                'action' => 'Setup Flock',
            ],
            'add-eggs' => [
                'label' => 'Record egg production',
                'action' => 'Add Eggs',
            ],
            'add-expense' => [
                'label' => 'Track an expense',
                'action' => 'Add Expense',
            ],
            'add-feed' => [
                'label' => 'Track feed inventory',
                'action' => 'Add Feed',
            ],
        ],
    ],
    'metrics' => [
        'heading' => 'Production Metrics',
        'total_eggs' => 'Total Eggs',
        'collected' => 'collected',
        'daily_average' => '7-Day Average',
        'eggs_per_day' => 'eggs per day',
        'last_7_days' => 'Last 7 Days',
        'previous' => 'previous',
        'this_month' => 'This Month',
        'last_month' => 'last month',
    ],
    'production_chart' => [
        'title' => '📊 30-Day Production Trend',
        'aria_label' => '30-day egg production bar chart',
        'tooltip_suffix' => 'eggs',
    ],
    'financial' => [
        'heading' => 'Financial Overview',
        'egg_value' => 'Egg Value',
        'potential_revenue' => 'potential revenue',
        'revenue' => 'Revenue',
        'from_sales' => 'from sales',
        'free_eggs' => 'Free Eggs',
        'given_away' => 'given away',
    ],
    'premium_teaser' => [
        'aria_label' => 'Premium feature teaser',
        'feature' => 'financial overview and analytics',
    ],
    'analytics' => [
        'heading' => 'Analytics',
        'desktop_subtitle' => 'Weekly revenue over last 12 weeks',
        'mobile_subtitle' => 'Weekly revenue over last 6 weeks',
        'desktop_aria_label' => 'Weekly revenue trend for last 12 weeks',
        'mobile_aria_label' => 'Weekly revenue trend for last 6 weeks',
        'week_of' => 'Week of',
    ],
    'recent_activity' => [
        'heading' => 'Recent Activity',
        'refresh' => 'Refresh',
        'refresh_aria_label' => 'Refresh recent activity',
        'empty_title' => 'No Recent Activity',
        'empty_description' => 'Start tracking eggs, sales, or flock events to see activity here.',
        'types' => [
            'egg' => 'Egg',
            'sale' => 'Sale',
            'event' => 'Event',
        ],
        'items' => [
            'egg' => ':count eggs collected',
            'sale' => 'Sale: $:amount',
        ],
    ],
    'fallback_probe' => 'Dashboard Fallback Probe',
];
