<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Sppt;
use App\Models\Pembayaran;
use App\Models\SubjekPajak;
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

        $search = $request->query('search', '');
        $rtFilter = $request->query('rt', '');
        $rwFilter = $request->query('rw', '');

        $spptQuery = Sppt::with(['objekPajak.subjekPajak'])
            ->where('status_bayar', 'piutang');

        if (auth()->user()->role === 'rt') {
            $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) {
                $q->where('RT', auth()->user()->rt);
            });
            $rtFilter = auth()->user()->rt;
        } elseif (auth()->user()->role === 'kasun_rw') {
            $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) {
                $q->where('RW', auth()->user()->rw);
            });
            $rwFilter = auth()->user()->rw;
        } else {
            if ($rtFilter) {
                $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) use ($rtFilter) {
                    $q->where('RT', $rtFilter);
                });
            }
            if ($rwFilter) {
                $spptQuery->whereHas('objekPajak.subjekPajak', function ($q) use ($rwFilter) {
                    $q->where('RW', $rwFilter);
                });
            }
        }

        if ($search) {
            $spptQuery->where(function ($q) use ($search) {
                $q->whereHas('objekPajak.subjekPajak', function ($query) use ($search) {
                    $query->where('NIK', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                })->orWhere('nop', 'like', "%{$search}%");
            });
        }

        $sppts = $spptQuery->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $rtOptions = SubjekPajak::distinct()->orderBy('RT')->pluck('RT');
        $rwOptions = SubjekPajak::distinct()->orderBy('RW')->pluck('RW');

        return view('village.payments', compact('sppts', 'search', 'rtFilter', 'rwFilter', 'rtOptions', 'rwOptions'));
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
