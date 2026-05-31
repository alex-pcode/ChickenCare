<?php

return [
    'page' => [
        'title' => 'Kontrolna tabla',
    ],
    'welcome' => [
        'heading' => 'Dobro dosli, :name',
        'messages' => [
            'start' => 'Hajde da podesimo vase jato.',
            'progress' => 'Podeseno :percentage% — zavrsimo podesavanje vaseg jata.',
            'complete' => 'Vase jato je potpuno podeseno. Evo danasnjeg pregleda.',
        ],
    ],
    'setup' => [
        'phase_headings' => [
            'new' => '🚀 Pocetak rada',
            'getting-started' => '📈 Razvijanje gazdinstva',
            'active' => '⚡ Napredne funkcije',
            'power' => '🎯 Zavrsni koraci',
        ],
        'progress_title' => 'Napredak podesavanja',
        'points' => 'poena',
        'phases' => [
            'new' => [
                'label' => 'Novi korisnik',
                'message' => 'Zapocnite osnovno podesavanje',
            ],
            'getting-started' => [
                'label' => 'Pocetak rada',
                'message' => 'Prosirite rad na osnovne funkcije',
            ],
            'active' => [
                'label' => 'Aktivan korisnik',
                'message' => 'Otkljucajte napredne funkcije',
            ],
            'power' => [
                'label' => 'Napredni korisnik',
                'message' => 'Koristite sve funkcije!',
            ],
        ],
        'items' => [
            'setup-flock' => [
                'label' => 'Podesite svoje jato',
                'action' => 'Podesite jato',
            ],
            'add-eggs' => [
                'label' => 'Evidentirajte proizvodnju jaja',
                'action' => 'Dodaj jaja',
            ],
            'add-expense' => [
                'label' => 'Zabelezite trosak',
                'action' => 'Dodaj trosak',
            ],
            'add-feed' => [
                'label' => 'Pratite zalihe hrane',
                'action' => 'Dodaj hranu',
            ],
        ],
    ],
    'metrics' => [
        'heading' => 'Metrike proizvodnje',
        'total_eggs' => 'Ukupno jaja',
        'collected' => 'sakupljeno',
        'daily_average' => 'Prosek za 7 dana',
        'eggs_per_day' => 'jaja dnevno',
        'last_7_days' => 'Poslednjih 7 dana',
        'previous' => 'prethodno',
        'this_month' => 'Ovaj mesec',
        'last_month' => 'prosli mesec',
    ],
    'production_chart' => [
        'title' => '📊 Trend proizvodnje za 30 dana',
        'aria_label' => 'stubicasti grafikon proizvodnje jaja za 30 dana',
        'tooltip_suffix' => 'jaja',
    ],
    'financial' => [
        'heading' => 'Finansijski pregled',
        'egg_value' => 'Vrednost jaja',
        'potential_revenue' => 'potencijalni prihod',
        'revenue' => 'Prihod',
        'from_sales' => 'od prodaje',
        'free_eggs' => 'Besplatna jaja',
        'given_away' => 'poklonjeno',
    ],
    'premium_teaser' => [
        'aria_label' => 'pregled premium pogodnosti',
        'feature' => 'finansijskog pregleda i analitike',
    ],
    'analytics' => [
        'heading' => 'Analitika',
        'desktop_subtitle' => 'Nedeljni prihod u poslednjih 12 nedelja',
        'mobile_subtitle' => 'Nedeljni prihod u poslednjih 6 nedelja',
        'desktop_aria_label' => 'grafikon kretanja nedeljnog prihoda u poslednjih 12 nedelja',
        'mobile_aria_label' => 'grafikon kretanja nedeljnog prihoda u poslednjih 6 nedelja',
        'week_of' => 'Nedelja od',
    ],
    'recent_activity' => [
        'heading' => 'Nedavne aktivnosti',
        'refresh' => 'Osvezi',
        'refresh_aria_label' => 'Osvezi nedavne aktivnosti',
        'empty_title' => 'Nema nedavnih aktivnosti',
        'empty_description' => 'Pocnite da pratite jaja, prodaju ili dogadjaje jata da biste ovde videli aktivnosti.',
        'types' => [
            'egg' => 'Jaja',
            'sale' => 'Prodaja',
            'event' => 'Dogadjaj',
        ],
        'items' => [
            'egg' => 'Sakupljeno je :count jaja',
            'sale' => 'Prodaja: $:amount',
        ],
    ],
];
