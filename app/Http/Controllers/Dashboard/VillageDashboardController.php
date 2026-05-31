<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Sppt;
use App\Models\Pembayaran;
use Illuminate\Http\Request;

class VillageDashboardController extends Controller
{
    /**
     * Show the village dashboard with village-scoped data.
     *
     * Kades, Kasun RW, RT can view dashboards, filters, and statistics for their scope.
     */
    public function index()
    {
        $this->authorize('view-village-dashboard');

        // Get village-scoped data based on user role
        $rt = auth()->user()->rt ?? null;
        $rw = auth()->user()->rw ?? null;

        $spptQuery = Sppt::query();
        if ($rt) {
            $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) {
                $q->where('RT', auth()->user()->rt);
            });
        }
        if ($rw && !$rt) {
            $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) {
                $q->where('RW', auth()->user()->rw);
            });
        }

        $totalSppt = $spptQuery->count();
        $paidSppt = (clone $spptQuery)->where('status_bayar', 'lunas')->count();
        $pendingSppt = (clone $spptQuery)->where('status_bayar', 'piutang')->count();

        return view('village.dashboard', [
            'totalSppt' => $totalSppt,
            'paidSppt' => $paidSppt,
            'pendingSppt' => $pendingSppt,
            'userRole' => auth()->user()->role,
        ]);
    }

    /**
     * View payments within the village scope.
     */
    public function payments(Request $request)
    {
        $this->authorize('view-scoped-payments');

        $rt = auth()->user()->rt ?? null;
        $rw = auth()->user()->rw ?? null;

        $paymentsQuery = Pembayaran::query();

        if ($rt) {
            $paymentsQuery->whereHas('sppt.objekPajak.subjekPajak', function ($q) {
                $q->where('RT', $rt);
            });
        } elseif ($rw) {
            $paymentsQuery->whereHas('sppt.objekPajak.subjekPajak', function ($q) {
                $q->where('RW', $rw);
            });
        }

        $payments = $paymentsQuery->paginate(20);

        return view('village.payments', compact('payments'));
    }

    /**
     * View statistics for the village scope.
     */
    public function statistics(Request $request)
    {
        $this->authorize('view-village-dashboard');

        $rt = auth()->user()->rt ?? null;
        $rw = auth()->user()->rw ?? null;

        $spptQuery = Sppt::query();
        if ($rt) {
            $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) {
                $q->where('RT', $rt);
            });
        }
        if ($rw && !$rt) {
            $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) {
                $q->where('RW', $rw);
            });
        }

        $statistics = [
            'total_pajak_terhutang' => $spptQuery->sum('pajak_terhutang'),
            'total_pembayaran' => Pembayaran::whereHas('sppt', function ($q) use ($rt, $rw) {
                if ($rt) {
                    $q->whereHas('objekPajak.subjekPajak', function ($q2) use ($rt) {
                        $q2->where('RT', $rt);
                    });
                } elseif ($rw) {
                    $q->whereHas('objekPajak.subjekPajak', function ($q2) use ($rw) {
                        $q2->where('RW', $rw);
                    });
                }
            })->sum('jumlah_bayar'),
            'collection_rate' => 0, // Calculated below
        ];

        if ($statistics['total_pajak_terhutang'] > 0) {
            $statistics['collection_rate'] = round(
                ($statistics['total_pembayaran'] / $statistics['total_pajak_terhutang']) * 100,
                2
            );
        }

        return view('village.statistics', compact('statistics'));
    }
}
