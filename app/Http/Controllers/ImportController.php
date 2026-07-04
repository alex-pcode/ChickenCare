<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDataRequest;
use App\Services\CrmReportsService;
use App\Services\ImportDataService;
use App\Traits\HandlesHtmx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ImportController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request): View
    {
        return view('import.index');
    }

    public function store(ImportDataRequest $request, ImportDataService $service): RedirectResponse|Response
    {
        $file = $request->file('import_file');
        $contents = file_get_contents($file->getRealPath());
        $data = json_decode($contents, true);

        $result = $service->import($request->user(), $data);

        app(CrmReportsService::class)->clearCacheForUser($request->user());

        if ($this->isHtmx($request)) {
            return $this->htmxRedirect(route('app.import.index', ['success' => 1]));
        }

        return redirect()->route('app.import.index')
            ->with('import_counts', $result['counts'])
            ->with('import_errors', $result['errors'])
            ->with('success', 'Data imported successfully!');
    }
}
