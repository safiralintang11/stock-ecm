<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogsExport;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $module = trim((string) $request->input('module', ''));
        $userId = trim((string) $request->input('user_id', ''));
        $from = trim((string) $request->input('from_date', ''));
        $to = trim((string) $request->input('to_date', ''));

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

    public function export(Request $request)
    {
        return Excel::download(
            new AuditLogsExport(
                trim((string) $request->input('search', '')),
                trim((string) $request->input('module', '')),
                trim((string) $request->input('user_id', '')),
                trim((string) $request->input('from_date', '')),
                trim((string) $request->input('to_date', ''))
            ),
            'audit-log-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}