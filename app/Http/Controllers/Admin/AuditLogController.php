<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('audit-logs.view'), 403);

        $companyId = $request->user()->company_id;

        $logs = Activity::query()
            ->with('causer:id,name')
            // Scope to activity caused by users of this company (single-company installs see all).
            ->when($companyId, fn ($q) => $q->whereIn(
                'causer_id', User::where('company_id', $companyId)->select('id')
            ))
            ->when($request->string('log')->toString(), fn ($q, $log) => $q->where('log_name', $log))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'log' => $a->log_name,
                'description' => $a->description,
                'subjectType' => class_basename($a->subject_type ?? ''),
                'causer' => $a->causer?->name,
                'properties' => $a->properties,
                'at' => $a->created_at?->toIso8601String(),
            ]);

        $logNames = Activity::query()
            ->when($companyId, fn ($q) => $q->whereIn('causer_id', User::where('company_id', $companyId)->select('id')))
            ->select('log_name')->distinct()->pluck('log_name')->filter()->values();

        return Inertia::render('admin/audit/index', [
            'logs' => $logs,
            'logNames' => $logNames,
            'filters' => $request->only('log'),
        ]);
    }
}
