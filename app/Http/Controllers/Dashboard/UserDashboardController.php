<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ObjekPajak;
use App\Models\Sppt;
use App\Models\SubjekPajak;
use App\Services\TransactionService;
use App\Traits\SecurityTrait;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    use SecurityTrait;

    /**
     * Show the user dashboard with ultra-restricted view.
     *
     * Regular users (pengguna) see ONLY their own SPPT bills.
     * No sensitive village or global financial metrics are displayed.
     */
    public function index()
    {
        // Regular users only
        if (auth()->user()->role !== 'pengguna') {
            abort(403, 'Unauthorized');
        }

        // Users are shown a simple dashboard without sensitive data
        return view('user.dashboard', [
            'message' => 'Selamat datang. Lihat tagihan pajak Anda di bawah.',
        ]);
    }

    /**
     * Show user's own SPPT bills.
     *
     * Data is immutable - users cannot edit or delete.
     * They can only view their bills and submit payment proposals.
     */
    public function mySppt()
    {
        if (auth()->user()->role !== 'pengguna') {
            abort(403, 'Unauthorized');
        }

        // Get the user's property data based on their NIK
        $nik = auth()->user()->nik ?? null;

        if (!$nik) {
            return view('user.sppt', [
                'sppts' => [],
                'message' => 'Data properti tidak terkait dengan akun Anda.',
            ]);
        }

        // Get SPPTs linked to user's NIK through ObjekPajak → SubjekPajak
        $sppts = Sppt::whereHas('objekPajak', function ($query) use ($nik) {
            $query->where('nik_pemilik', $nik);
        })->get();

        return view('user.sppt', [
            'sppts' => $sppts,
        ]);
    }

    /**
     * Submit a payment proposal for a specific SPPT.
     *
     * Changes SPPT status from 'piutang' to 'proses_pengajuan'.
     * Only accessible to regular users.
     */
    public function submitPaymentProposal(Request $request, Sppt $sppt)
    {
        if (auth()->user()->role !== 'pengguna') {
            abort(403, 'Unauthorized');
        }

        // Verify the SPPT belongs to the user
        $nik = auth()->user()->nik;
        $ownedByUser = $sppt->objekPajak->nik_pemilik === $nik;

        if (!$ownedByUser) {
            abort(403, 'You do not have access to this SPPT.');
        }

        // Only allow proposal submission if status is 'piutang'
        if ($sppt->status_bayar !== 'piutang') {
            return back()->withErrors([
                'error' => 'Tidak dapat mengajukan pembayaran untuk tagihan ini.',
            ]);
        }

        try {
            TransactionService::execute(function () use ($sppt) {
                $sppt->update(['status_bayar' => 'proses_pengajuan']);
            });

            $this->logDatabaseOperation('payment_proposal', 'Sppt', [
                'sppt_id' => $sppt->id_sppt,
                'proposed_by' => auth()->id(),
            ]);

            return back()->with('success', 'Pengajuan pembayaran berhasil dikirim untuk persetujuan admin.');
        } catch (\Exception $e) {
            $this->logError('Failed to submit payment proposal', [
                'error' => $e->getMessage(),
                'sppt_id' => $sppt->id_sppt,
            ]);

            return back()->withErrors([
                'error' => 'Gagal mengirim pengajuan. Silakan coba lagi.',
            ]);
        }
    }
}
