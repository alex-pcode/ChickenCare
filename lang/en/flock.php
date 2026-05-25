<?php

return [
    'page' => [
        'title' => 'Flock Profile',
        'header' => 'Flock Profile',
    ],
    'hero' => [
        'image_alt' => 'Chickens on a farm',
        'badge_fallback' => 'My Flock',
        'status' => [
            'no_recount_title' => 'No Recount Logged',
            'no_recount_short' => 'No recount yet',
            'no_recount_detail' => 'Run a periodic bird count to make sure no bird is missing or injured.',
            'no_recount_expected' => 'You should have :count chickens.',
            'up_to_date_title' => 'Flock Count Up to Date',
            'up_to_date_short' => ':count birds counted',
            'up_to_date_detail' => 'Counted :count :birds on :date.',
            'due_soon_title' => 'Recount Due Soon',
            'due_soon_short' => 'Recount due',
            'due_soon_detail' => 'Last recount was :ago. Consider doing another check.',
            'overdue_title' => 'Recount Overdue',
            'overdue_short' => 'Recount overdue',
            'overdue_detail' => 'Last counted on :date. Your flock may have changed.',
            'bird' => 'bird',
            'birds' => 'birds',
        ],
        'comparison' => [
            'title' => 'Bird Count',
            'recount_label' => 'Last Recount',
            'system_label' => 'System Count',
        ],
    ],
    'sections' => [
        'add_event' => 'Add New Event',
        'timeline' => 'Events Timeline',
    ],
    'overview' => [
        'manage_batches' => 'Manage Batches',
        'cards' => [
            'laying' => 'Laying',
            'not_laying' => 'Not Laying',
            'brooding' => 'Brooding',
            'roosters' => 'Roosters',
            'chicks' => 'Chicks',
        ],
        'labels' => [
            'laying_batches' => '{1} :count batch laying|[2,*] :count batches laying',
            'not_laying_batches' => '{1} :count batch|[2,*] :count batches',
            'brooding_hens' => '{1} :count hen brooding|[2,*] :count hens brooding',
            'rooster_batches' => '{1} :count batch|[2,*] :count batches',
            'chick_batches' => '{1} :count batch|[2,*] :count batches',
        ],
    ],
    'form' => [
        'fields' => [
            'type' => 'Event Type',
            'date' => 'Date',
            'affected_birds' => 'Number of Birds',
            'description' => 'Description',
            'notes' => 'Additional Notes',
        ],
        'placeholders' => [
            'affected_birds' => 'Optional',
            'description' => 'What happened?',
            'notes' => 'Optional notes...',
        ],
        'submit' => [
            'create' => 'Add Event',
            'edit' => 'Update Event',
            'cancel' => 'Cancel Edit',
        ],
        'types' => [
            'acquisition' => '🐔 New Birds Acquired',
            'laying_start' => '🥚 Started Laying',
            'broody' => '🪺 Went Broody',
            'hatching' => '🐥 Eggs Hatched',
            'recount' => '🔢 Periodic Recount',
            'other' => '📝 Other Event',
        ],
    ],
    'timeline' => [
        'empty_title' => 'No events recorded yet',
        'empty_description' => "Add your first event above to start tracking your flock's timeline!",
        'types' => [
            'acquisition' => 'New Birds Acquired',
            'laying_start' => 'Started Laying',
            'broody' => 'Went Broody',
            'hatching' => 'Eggs Hatched',
            'recount' => 'Periodic Recount',
            'other' => 'Other Event',
        ],
        'affected_birds' => '{1} :count bird affected|[2,*] :count birds affected',
        'actions' => [
            'edit' => 'Edit',
            'delete' => 'Delete',
            'edit_aria_label' => 'Edit event: :description',
            'delete_aria_label' => 'Delete event: :description',
            'delete_confirm' => 'Remove this event?',
        ],
    ],
    'messages' => [
        'profile_updated' => 'Flock profile updated.',
        'event_added' => 'Event added.',
        'event_updated' => 'Event updated.',
        'event_removed' => 'Event removed.',
    ],
];
