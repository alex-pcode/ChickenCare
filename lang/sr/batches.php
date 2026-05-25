<?php

return [
    'page' => [
        'title' => 'Grupe jata',
        'header' => 'Grupe jata',
        'create_title' => 'Dodaj novu grupu',
    ],
    'actions' => [
        'add_batch' => 'Dodaj grupu',
        'back_to_batches' => 'Nazad na grupe',
    ],
    'filters' => [
        'label' => 'Filtriraj grupe',
        'active' => 'Aktivne',
        'archived' => 'Arhivirane',
        'all' => 'Sve',
    ],
    'table' => [
        'empty_title' => 'Jos nema grupa',
        'empty_description' => 'Pocnite organizaciju jata dodavanjem prve grupe',
        'empty_action' => 'Dodaj prvu grupu',
        'hint' => '💡 Kliknite na bilo koji red da vidite detalje, sastav i vremensku liniju.',
        'status' => [
            'laying' => 'Nosi',
            'not_laying' => 'Ne nosi',
        ],
        'not_set' => 'Nije postavljeno',
        'edit_laying_date' => 'Izmeni datum nosenja',
        'aria' => [
            'view_details' => 'Prikazi detalje za :batch',
            'edit_laying_date' => 'Izmeni datum nosenja za :batch',
        ],
        'columns' => [
            'batch_name' => 'Naziv grupe',
            'current_count' => 'Trenutni broj',
            'status' => 'Status',
            'initial_count' => 'Pocetni broj',
            'acquisition_date' => 'Nabavljeno',
            'source' => 'Izvor',
            'laying_since' => 'Nosi od',
        ],
    ],
    'messages' => [
        'created' => 'Grupa je uspesno kreirana.',
        'updated' => 'Grupa je uspesno azurirana.',
        'archived' => 'Grupa je uspesno arhivirana.',
        'composition_updated' => 'Sastav grupe je azuriran.',
        'laying_date_set' => 'Datum nosenja je postavljen.',
        'laying_date_cleared' => 'Datum nosenja je uklonjen.',
        'event_added' => 'Dogadjaj je uspesno dodat.',
        'event_added_timeline' => 'Dogadjaj je dodat na vremensku liniju',
        'event_updated' => 'Dogadjaj je uspesno azuriran.',
        'event_deleted' => 'Dogadjaj je uspesno obrisan.',
        'loss_logged' => 'Gubitak je uspesno evidentiran',
        'loss_updated' => 'Gubitak je azuriran',
        'death_added' => 'Evidencija uginuce je uspesno dodata.',
        'death_updated' => 'Evidencija uginuce je uspesno azurirana.',
        'death_deleted' => 'Evidencija uginuce je uspesno obrisana.',
    ],
    'age' => [
        'chick' => 'Pilence (0-8 nedelja)',
        'juvenile' => 'Mlada zivina (8-18 nedelja)',
        'adult' => 'Odrasla zivina (18+ nedelja)',
    ],
];
