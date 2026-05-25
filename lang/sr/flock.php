<?php

return [
    'page' => [
        'title' => 'Profil jata',
        'header' => 'Profil jata',
    ],
    'hero' => [
        'image_alt' => 'Kokoske na farmi',
        'badge_fallback' => 'Moje jato',
        'status' => [
            'no_recount_title' => 'Prebrojavanje nije evidentirano',
            'no_recount_short' => 'Nema prebrojavanja',
            'no_recount_detail' => 'Obavite periodicno prebrojavanje ptica kako biste proverili da li neka nedostaje ili je povredjena.',
            'no_recount_expected' => 'Trebalo bi da imate :count pilica.',
            'up_to_date_title' => 'Broj ptica je azuriran',
            'up_to_date_short' => ':count ptica prebrojano',
            'up_to_date_detail' => 'Prebrojano :count :birds dana :date.',
            'due_soon_title' => 'Vreme je za prebrojavanje',
            'due_soon_short' => 'Prebrojavanje uskoro',
            'due_soon_detail' => 'Poslednje prebrojavanje je bilo :ago. Razmislite o novom prebrojavanju.',
            'overdue_title' => 'Prebrojavanje je zakasnelo',
            'overdue_short' => 'Kasni prebrojavanje',
            'overdue_detail' => 'Poslednje prebrojavanje je bilo :date. Stanje jata se mozda promenilo.',
            'bird' => 'ptica',
            'birds' => 'ptica',
        ],
        'comparison' => [
            'title' => 'Broj ptica',
            'recount_label' => 'Poslednje prebrojavanje',
            'system_label' => 'Sistemski broj',
        ],
    ],
    'sections' => [
        'add_event' => 'Dodaj novi dogadjaj',
        'timeline' => 'Vremenska linija dogadjaja',
    ],
    'overview' => [
        'manage_batches' => 'Upravljaj grupama',
        'cards' => [
            'laying' => 'Nosi',
            'not_laying' => 'Ne nose',
            'brooding' => 'Kvocaju',
            'roosters' => 'Petlovi',
            'chicks' => 'Pilici',
        ],
        'labels' => [
            'laying_batches' => '{1} :count grupa nosi|[2,4] :count grupe nose|[5,*] :count grupa nose',
            'not_laying_batches' => '{1} :count grupa|[2,4] :count grupe|[5,*] :count grupa',
            'brooding_hens' => '{1} :count kvocka|[2,4] :count kvocke|[5,*] :count kvocaka',
            'rooster_batches' => '{1} :count grupa|[2,4] :count grupe|[5,*] :count grupa',
            'chick_batches' => '{1} :count grupa|[2,4] :count grupe|[5,*] :count grupa',
        ],
    ],
    'form' => [
        'fields' => [
            'type' => 'Tip dogadjaja',
            'date' => 'Datum',
            'affected_birds' => 'Broj ptica',
            'description' => 'Opis',
            'notes' => 'Dodatne napomene',
        ],
        'placeholders' => [
            'affected_birds' => 'Opcionalno',
            'description' => 'Sta se dogodilo?',
            'notes' => 'Opcione napomene...',
        ],
        'submit' => [
            'create' => 'Dodaj dogadjaj',
            'edit' => 'Azuriraj dogadjaj',
            'cancel' => 'Otkazi izmenu',
        ],
        'types' => [
            'acquisition' => '🐔 Nabavljene su nove ptice',
            'laying_start' => '🥚 Pocele su da nose',
            'broody' => '🪺 Kvocanje',
            'hatching' => '🐥 Izlegla su se jaja',
            'recount' => '🔢 Periodicno prebrojavanje',
            'other' => '📝 Drugi dogadjaj',
        ],
    ],
    'timeline' => [
        'empty_title' => 'Jos nema evidentiranih dogadjaja',
        'empty_description' => 'Dodajte prvi dogadjaj iznad da biste poceli da pratite vremensku liniju svog jata!',
        'types' => [
            'acquisition' => 'Nabavljene su nove ptice',
            'laying_start' => 'Pocele su da nose',
            'broody' => 'Kvocanje',
            'hatching' => 'Izlegla su se jaja',
            'recount' => 'Periodicno prebrojavanje',
            'other' => 'Drugi dogadjaj',
        ],
        'affected_birds' => '{1} :count ptica zahvacena|[2,4] :count ptice zahvacene|[5,*] :count ptica zahvaceno',
        'actions' => [
            'edit' => 'Izmeni',
            'delete' => 'Obrisi',
            'edit_aria_label' => 'Izmeni dogadjaj: :description',
            'delete_aria_label' => 'Obrisi dogadjaj: :description',
            'delete_confirm' => 'Ukloniti ovaj dogadjaj?',
        ],
    ],
    'messages' => [
        'profile_updated' => 'Profil jata je azuriran.',
        'event_added' => 'Dogadjaj je dodat.',
        'event_updated' => 'Dogadjaj je azuriran.',
        'event_removed' => 'Dogadjaj je uklonjen.',
    ],
];
