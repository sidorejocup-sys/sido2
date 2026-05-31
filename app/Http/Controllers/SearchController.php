<?php

namespace App\Http\Controllers;

use App\Models\ObjekPajak;
use App\Models\Sppt;
use App\Models\SubjekPajak;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->validate([
            'q' => 'required|string|min:3',
        ])['q'];

        $searchable = '%'.str_replace(' ', '%', $query).'%';

        $subjects = SubjekPajak::select(['NIK as id', 'nama', 'NIK as identifier'])
            ->where('NIK', 'like', $searchable)
            ->orWhere('nama', 'like', $searchable)
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'type' => 'Subjek Pajak',
                'title' => $item->nama,
                'subtitle' => $item->identifier,
                'url' => '#',
            ]);

        $objects = ObjekPajak::select(['nop as id', 'nop as identifier'])
            ->where('nop', 'like', $searchable)
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'type' => 'Objek Pajak',
                'title' => $item->identifier,
                'subtitle' => 'NOP',
                'url' => '#',
            ]);

        $sppts = Sppt::select(['id_sppt as id', 'nop as identifier'])
            ->where('nop', 'like', $searchable)
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'type' => 'SPPT',
                'title' => $item->identifier,
                'subtitle' => 'SPPT ID ' . $item->id,
                'url' => '#',
            ]);

        return response()->json([
            'results' => $subjects->concat($objects)->concat($sppts)->values(),
        ]);
    }
}
