<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $activities) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $logs = $this->activities->list($request->only([
            'log_name', 'event', 'causer_id', 'date_from', 'date_to',
        ]));
        $logNames = $this->activities->logNames();
        $events = $this->activities->events();

        return view('admin.audit-log.index', compact('logs', 'logNames', 'events'));
    }

    public function show(Activity $activity): View
    {
        $this->authorize('view', $activity);

        $activity = $this->activities->find($activity->id);

        return view('admin.audit-log.show', compact('activity'));
    }
}
