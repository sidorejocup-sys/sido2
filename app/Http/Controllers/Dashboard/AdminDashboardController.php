<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\ExportBulkDataJob;
use App\Jobs\ImportBulkDataJob;
use App\Models\Sppt;
use App\Models\Pembayaran;
use App\Traits\SecurityTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
        $totalSubjekPajak = \App\Models\SubjekPajak::count();
        $totalRevenue = Pembayaran::sum('jumlah_bayar');
        $pendingPayments = Sppt::where('status_bayar', 'piutang')->count();
        $approvalPending = Sppt::where('status_bayar', 'proses_pengajuan')->count();

        return view('admin.dashboard', [
            'totalSppt' => $totalSppt,
            'totalSubjekPajak' => $totalSubjekPajak,
            'totalRevenue' => $totalRevenue,
            'pendingPayments' => $pendingPayments,
            'approvalPending' => $approvalPending,
        ]);
    }

    public function exportPage()
    {
        $this->authorize('admin');
        return redirect()->route('admin.dashboard');
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
            'module' => 'required|in:subjek_pajak,objek_pajak,sppt',
        ]);

        $uploaded = $request->file('file');
        $filename = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();
        $path = $uploaded->storeAs('imports', $filename);
        $jobId = Str::uuid()->toString();

        Cache::put("bulk_import:{$jobId}", [
            'status' => 'queued',
            'progress' => 0,
            'module' => $request->input('module'),
            'message' => 'Import queued for background processing',
            'errors' => [],
        ], 3600);

        ImportBulkDataJob::dispatch($jobId, $path, $request->input('module'), $request->user()->id);

        $this->logDatabaseOperation('import', 'BulkData', [
            'file' => $uploaded->getClientOriginalName(),
            'job_id' => $jobId,
            'module' => $request->input('module'),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['job_id' => $jobId]);
        }

        return back()->with('success', 'Import queued successfully. Tracking will begin shortly.')->with('import_job_id', $jobId);
    }

    /**
     * Handle data export.
     */
    public function export(Request $request)
    {
        $this->authorize('admin');
        $this->authorize('import-export');

        $request->validate([
            'module' => 'required|in:subjek_pajak,objek_pajak,sppt',
            'format' => 'required|in:xlsx,pdf',
        ]);

        $jobId = Str::uuid()->toString();

        Cache::put("bulk_export:{$jobId}", [
            'status' => 'queued',
            'progress' => 0,
            'module' => $request->input('module'),
            'format' => $request->input('format'),
            'message' => 'Export queued for background processing',
            'download_path' => null,
        ], 3600);

        ExportBulkDataJob::dispatch($jobId, $request->input('module'), $request->input('format'), $request->user()->id);

        $this->logDatabaseOperation('export', 'BulkData', [
            'module' => $request->input('module'),
            'format' => $request->input('format'),
            'job_id' => $jobId,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['job_id' => $jobId]);
        }

        return back()->with('success', 'Export queued successfully. You will be able to download the file when it is ready.')->with('export_job_id', $jobId);
    }

    public function downloadTemplate(string $module)
    {
        $this->authorize('admin');
        $this->authorize('import-export');

        $allowed = ['subjek_pajak', 'objek_pajak', 'sppt'];
        if (!in_array($module, $allowed, true)) {
            abort(404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = match ($module) {
            'subjek_pajak' => ['NIK', 'nama', 'alamat', 'RT', 'RW', 'no_hp'],
            'objek_pajak' => ['nop', 'nik_pemilik', 'letak_objek', 'luas_bumi', 'luas_bangunan', 'status_aktif'],
            'sppt' => ['nop', 'tahun', 'njop_bumi', 'njop_bangunan', 'pajak_terhutang', 'status_bayar'],
        };

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF1F2937');
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($module === 'objek_pajak') {
            $validation = $sheet->getCell('F2')->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Invalid status');
            $validation->setError('Only 0 or 1 is accepted.');
            $validation->setFormula1('0,1');
        }

        if ($module === 'sppt') {
            $validation = $sheet->getCell('F2')->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Invalid status');
            $validation->setError('Only piutang, proses_pengajuan, lunas, atau ditolak.');
            $validation->setFormula1('"piutang,proses_pengajuan,lunas,ditolak"');
        }

        foreach (range('A', Coordinate::stringFromColumnIndex(count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf('template_%s.xlsx', $module);
        $tempPath = storage_path('app/exports/' . $filename);
        Storage::disk('local')->makeDirectory('exports');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function importStatus(string $jobId)
    {
        $this->authorize('admin');
        return response()->json(Cache::get("bulk_import:{$jobId}", [
            'status' => 'unknown',
            'progress' => 0,
            'message' => 'No import status found.',
        ]));
    }

    public function exportStatus(string $jobId)
    {
        $this->authorize('admin');
        return response()->json(Cache::get("bulk_export:{$jobId}", [
            'status' => 'unknown',
            'progress' => 0,
            'message' => 'No export status found.',
            'download_path' => null,
        ]));
    }

    public function downloadExport(string $jobId)
    {
        $this->authorize('admin');

        $status = Cache::get("bulk_export:{$jobId}");
        if (!$status || $status['status'] !== 'completed' || empty($status['download_path'])) {
            abort(404, 'Export is not ready yet.');
        }

        $path = Storage::disk('local')->path($status['download_path']);
        if (!file_exists($path)) {
            abort(404, 'Export file missing.');
        }

        return response()->download($path);
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
