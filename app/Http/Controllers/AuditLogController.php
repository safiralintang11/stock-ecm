<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $module = $request->input('module', '');
        $userId = $request->input('user_id', '');
        $from = $request->input('from_date', '');
        $to = $request->input('to_date', '');

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('action', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('reason', 'like', "%{$search}%")))
            ->when($module !== '', fn ($q) => $q->where('module', $module))
            ->when($userId !== '', fn ($q) => $q->where('user_id', $userId))
            ->when($from !== '', fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'module' => $module,
                'user_id' => $userId,
                'from_date' => $from,
                'to_date' => $to,
            ],
        ]);
    }
}
