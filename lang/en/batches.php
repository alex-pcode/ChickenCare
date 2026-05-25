<?php

return [
    'page' => [
        'title' => 'Flock Batches',
        'header' => 'Flock Batches',
        'create_title' => 'Add New Batch',
    ],
    'actions' => [
        'add_batch' => 'Add Batch',
        'back_to_batches' => 'Back to Batches',
    ],
    'filters' => [
        'label' => 'Filter batches',
        'active' => 'Active',
        'archived' => 'Archived',
        'all' => 'All',
    ],
    'table' => [
        'empty_title' => 'No Batches Yet',
        'empty_description' => 'Start organizing your flock by adding your first batch',
        'empty_action' => 'Add First Batch',
        'hint' => '💡 Click any row to view details, composition, and timeline.',
        'status' => [
            'laying' => 'Laying',
            'not_laying' => 'Not Laying',
        ],
        'not_set' => 'Not set',
        'edit_laying_date' => 'Edit laying date',
        'aria' => [
            'view_details' => 'View details for :batch',
            'edit_laying_date' => 'Edit laying date for :batch',
        ],
        'columns' => [
            'batch_name' => 'Batch Name',
            'current_count' => 'Current Count',
            'status' => 'Status',
            'initial_count' => 'Started With',
            'acquisition_date' => 'Acquired',
            'source' => 'Source',
            'laying_since' => 'Laying Since',
        ],
    ],
    'messages' => [
        'created' => 'Batch created successfully.',
        'updated' => 'Batch updated successfully.',
        'archived' => 'Batch archived successfully.',
        'composition_updated' => 'Batch composition updated.',
        'laying_date_set' => 'Laying date set.',
        'laying_date_cleared' => 'Laying date cleared.',
        'event_added' => 'Event added successfully.',
        'event_added_timeline' => 'Event added to timeline',
        'event_updated' => 'Event updated successfully.',
        'event_deleted' => 'Event deleted successfully.',
        'loss_logged' => 'Loss logged successfully',
        'loss_updated' => 'Loss updated',
        'death_added' => 'Death record added successfully.',
        'death_updated' => 'Death record updated successfully.',
        'death_deleted' => 'Death record deleted successfully.',
    ],
    'age' => [
        'chick' => 'Chick (0-8 weeks)',
        'juvenile' => 'Juvenile (8-18 weeks)',
        'adult' => 'Adult (18+ weeks)',
    ],
];
