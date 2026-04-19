@include('feed.partials.records-table', ['feeds' => $feeds, 'sort' => $sort ?? 'opened_date', 'dir' => $dir ?? 'desc'])
