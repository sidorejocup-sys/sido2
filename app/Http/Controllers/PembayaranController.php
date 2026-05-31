<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Sppt;
use App\Services\TransactionService;
use App\Traits\SecurityTrait;
use Illuminate\Http\Request;

/**
 * Example controller demonstrating secure transaction patterns.
 */
class PembayaranController extends Controller
{
    use SecurityTrait;

    /**
     * Store a new payment record.
     *
     * Uses DB::transaction() to ensure atomicity.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_sppt' => 'required|exists:sppt,id_sppt',
            'jumlah_bayar' => 'required|numeric|min:0.01',
            'tgl_bayar' => 'required|date',
        ]);

        try {
            $pembayaran = TransactionService::execute(function () use ($validated) {
                $sppt = Sppt::lockForUpdate()->find($validated['id_sppt']);

                if (!$sppt) {
                    throw new \Exception('SPPT tidak ditemukan.');
                }

                $pembayaran = Pembayaran::create([
                    'id_sppt' => $validated['id_sppt'],
                    'tgl_bayar' => $validated['tgl_bayar'],
                    'jumlah_bayar' => $validated['jumlah_bayar'],
                    'id_petugas' => auth()->id(),
                ]);

                // Update SPPT status if fully paid
                if ($pembayaran->jumlah_bayar >= $sppt->pajak_terhutang) {
                    $sppt->update(['status_bayar' => 'lunas']);
                }

                return $pembayaran;
            });

            $this->logDatabaseOperation('create', 'Pembayaran', [
                'pembayaran_id' => $pembayaran->id_bayar,
                'sppt_id' => $pembayaran->id_sppt,
            ]);

            return response()->json([
                'message' => 'Pembayaran berhasil dicatat.',
                'pembayaran' => $pembayaran,
            ], 201);
        } catch (\Exception $e) {
            $this->logError('Failed to create pembayaran', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal mencatat pembayaran.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a payment record.
     *
     * Uses DB::transaction() with error handling.
     */
    public function update(Request $request, Pembayaran $pembayaran)
    {
        $validated = $request->validate([
            'jumlah_bayar' => 'required|numeric|min:0.01',
            'tgl_bayar' => 'required|date',
        ]);

        $result = TransactionService::executeWithFallback(
            function () use ($pembayaran, $validated) {
                $pembayaran->update($validated);

                // Recalculate SPPT status
                $sppt = $pembayaran->sppt;
                $totalPaid = Pembayaran::where('id_sppt', $sppt->id_sppt)->sum('jumlah_bayar');

                if ($totalPaid >= $sppt->pajak_terhutang) {
                    $sppt->update(['status_bayar' => 'lunas']);
                }

                return $pembayaran;
            },
            function ($exception) {
                \Log::error('Pembayaran update failed', [
                    'error' => $exception->getMessage(),
                ]);

                return null;
            }
        );

        if (!$result) {
            return response()->json([
                'message' => 'Gagal memperbarui pembayaran.',
            ], 500);
        }

        $this->logDatabaseOperation('update', 'Pembayaran', [
            'pembayaran_id' => $pembayaran->id_bayar,
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil diperbarui.',
            'pembayaran' => $result,
        ]);
    }
}
