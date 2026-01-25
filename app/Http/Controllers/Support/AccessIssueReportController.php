<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\AccessIssueReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessIssueReportController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AccessIssueReport::with('handler:id,name')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('issue_type')) {
            $query->where('issue_type', $request->issue_type);
        }

        $reports = $query->paginate(20);

        $stats = [
            'total' => AccessIssueReport::count(),
            'pending' => AccessIssueReport::where('status', 'pending')->count(),
            'contacted' => AccessIssueReport::where('status', 'contacted')->count(),
            'resolved' => AccessIssueReport::where('status', 'resolved')->count(),
        ];

        return Inertia::render('support/access-issues/index', [
            'reports' => $reports,
            'stats' => $stats,
            'filters' => $request->only(['status', 'issue_type']),
        ]);
    }

    public function show(AccessIssueReport $report): Response
    {
        $report->load('handler:id,name');

        return Inertia::render('support/access-issues/show', [
            'report' => $report,
        ]);
    }

    public function updateStatus(Request $request, AccessIssueReport $report): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:contacted,resolved',
        ]);

        // No se puede resolver sin antes contactar
        if ($request->status === 'resolved' && $report->status === 'pending') {
            return redirect()->back()
                ->with('error', 'Debe marcar como contactado antes de resolver.');
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'contacted' && ! $report->contacted_at) {
            $updateData['contacted_at'] = now();
            $updateData['handled_by'] = auth()->id();
        }

        if ($request->status === 'resolved' && ! $report->resolved_at) {
            $updateData['resolved_at'] = now();
            if (! $report->handled_by) {
                $updateData['handled_by'] = auth()->id();
            }
        }

        $report->update($updateData);

        $statusLabels = [
            'pending' => 'pendiente',
            'contacted' => 'contactado',
            'resolved' => 'resuelto',
        ];

        return redirect()->back()
            ->with('success', "Estado cambiado a {$statusLabels[$request->status]}.");
    }

    public function updateNotes(Request $request, AccessIssueReport $report): RedirectResponse
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $report->update([
            'admin_notes' => $request->admin_notes,
            'handled_by' => $report->handled_by ?? auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', 'Notas actualizadas.');
    }

    public function destroy(AccessIssueReport $report): RedirectResponse
    {
        $report->delete();

        return redirect()->route('support.access-issues.index')
            ->with('success', 'Reporte eliminado.');
    }
}
