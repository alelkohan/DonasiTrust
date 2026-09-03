<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, AuditLogger $audit)
    {
        $logs = AuditLog::with('user:id,name')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit', [
            'logs' => $logs,
            'chainStatus' => $audit->verifyChain(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
