<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Sppt;
use App\Models\Pembayaran;
use App\Traits\SecurityTrait;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use SecurityTrait;

    /**
     * Show the admin dashboard with full system overview.
     */
    public function index()
    {
        $this->authorize('admin');

        $totalSppt = Sppt::count();
        $totalRevenue = Pembayaran::sum('jumlah_bayar');
        $pendingPayments = Sppt::where('status_bayar', 'piutang')->count();
        $approvalPending = Sppt::where('status_bayar', 'proses_pengajuan')->count();

        return view('admin.dashboard', [
            'totalSppt' => $totalSppt,
            'totalRevenue' => $totalRevenue,
            'pendingPayments' => $pendingPayments,
            'approvalPending' => $approvalPending,
        ]);
    }

    /**
     * Handle data import (CSV/Excel).
     */
    public function import(Request $request)
    {
        $this->authorize('admin');
        $this->authorize('import-export');

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
        ]);

        $this->logDatabaseOperation('import', 'BulkData', [
            'file' => $request->file('file')->getClientOriginalName(),
        ]);

        // TODO: Implement actual import logic

        return back()->with('success', 'Data imported successfully.');
    }

    /**
     * Handle data export to CSV/Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('admin');
        $this->authorize('import-export');

        $this->logDatabaseOperation('export', 'BulkData', [
            'format' => $request->query('format', 'csv'),
        ]);

        // TODO: Implement actual export logic

        return response()->download('path/to/export.csv');
    }

    /**
     * Approve a pending payment.
     */
    public function approvePayment(Pembayaran $pembayaran)
    {
        $this->authorize('admin');
        $this->authorize('approve-payment');

        $sppt = $pembayaran->sppt;

        if ($sppt->status_bayar !== 'proses_pengajuan') {
            return back()->withErrors(['error' => 'Pembayaran tidak dalam status pengajuan.']);
        }

        $sppt->update(['status_bayar' => 'lunas']);

        $this->logDatabaseOperation('approve', 'Pembayaran', [
            'pembayaran_id' => $pembayaran->id_bayar,
            'sppt_id' => $sppt->id_sppt,
        ]);

        return back()->with('success', 'Pembayaran berhasil disetujui.');
    }
}
