<?php

namespace App\Jobs;

use App\Models\ObjekPajak;
use App\Models\Sppt;
use App\Models\SubjekPajak;
use Dompdf\Dompdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportBulkDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $jobId;
    public string $module;
    public string $format;
    public int $userId;

    public function __construct(string $jobId, string $module, string $format, int $userId)
    {
        $this->jobId = $jobId;
        $this->module = $module;
        $this->format = $format;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $cacheKey = "bulk_export:{$this->jobId}";
        Cache::put($cacheKey, [
            'status' => 'processing',
            'progress' => 0,
            'module' => $this->module,
            'format' => $this->format,
            'message' => 'Generating export file',
            'download_path' => null,
        ], 3600);

        $filename = sprintf('%s_export_%s.%s', $this->module, now()->format('Ymd_His'), $this->format === 'pdf' ? 'pdf' : 'xlsx');
        $relativePath = "exports/{$this->jobId}/{$filename}";
        Storage::disk('local')->makeDirectory("exports/{$this->jobId}");

        if ($this->format === 'pdf') {
            $this->generatePdf($relativePath);
        } else {
            $this->generateExcel($relativePath);
        }

        Cache::put($cacheKey, [
            'status' => 'completed',
            'progress' => 100,
            'module' => $this->module,
            'format' => $this->format,
            'message' => 'Export ready for download.',
            'download_path' => $relativePath,
            'completed_at' => now()->toDateTimeString(),
        ], 3600);
    }

    protected function generateExcel(string $relativePath): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->expectedHeaders();

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')->getFont()->setBold(true);

        $row = 2;
        $dataQuery = $this->queryRecords();
        $chunkSize = 500;
        $processed = 0;
        $totalRows = $dataQuery->count();

        $dataQuery->chunk($chunkSize, function ($records) use (&$row, &$processed, $sheet, $headers, $totalRows) {
            foreach ($records as $record) {
                $sheet->fromArray(array_map(fn ($field) => $record->{$field}, $headers), null, "A{$row}");
                $row++;
                $processed++;
                $this->updateProgress($processed, $totalRows, "Generating export file ({$processed} rows)");
            }
        });

        foreach (range('A', Coordinate::stringFromColumnIndex(count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('local')->path($relativePath));
    }

    protected function generatePdf(string $relativePath): void
    {
        $records = $this->queryRecords()->get();
        $html = View::make('admin.export-report', [
            'module' => $this->module,
            'headers' => $this->expectedHeaders(),
            'rows' => $records,
        ])->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        file_put_contents(Storage::disk('local')->path($relativePath), $dompdf->output());
    }

    protected function expectedHeaders(): array
    {
        return match ($this->module) {
            'subjek_pajak' => ['NIK', 'nama', 'alamat', 'RT', 'RW', 'no_hp'],
            'objek_pajak' => ['nop', 'nik_pemilik', 'letak_objek', 'luas_bumi', 'luas_bangunan', 'status_aktif'],
            'sppt' => ['nop', 'tahun', 'njop_bumi', 'njop_bangunan', 'pajak_terhutang', 'status_bayar'],
            default => [],
        };
    }

    protected function queryRecords()
    {
        return match ($this->module) {
            'subjek_pajak' => SubjekPajak::query(),
            'objek_pajak' => ObjekPajak::query(),
            'sppt' => Sppt::query(),
            default => throw new \RuntimeException('Unknown export module.'),
        };
    }

    protected function updateProgress(int $processed, int $total, string $message): void
    {
        $percent = $total > 0 ? min(100, (int) floor(($processed / $total) * 100)) : 50;
        Cache::put("bulk_export:{$this->jobId}", [
            'status' => 'processing',
            'progress' => $percent,
            'module' => $this->module,
            'format' => $this->format,
            'message' => $message,
            'download_path' => null,
        ], 3600);
    }
}
