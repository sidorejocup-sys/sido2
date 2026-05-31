<?php

namespace App\Jobs;

use App\Models\ObjekPajak;
use App\Models\Sppt;
use App\Models\SubjekPajak;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

class ImportBulkDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $jobId;
    public string $filePath;
    public string $module;
    public int $userId;

    public function __construct(string $jobId, string $filePath, string $module, int $userId)
    {
        $this->jobId = $jobId;
        $this->filePath = $filePath;
        $this->module = $module;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $cacheKey = "bulk_import:{$this->jobId}";
        Cache::put($cacheKey, [
            'status' => 'processing',
            'progress' => 0,
            'module' => $this->module,
            'message' => 'Preparing import batch',
            'errors' => [],
        ], 3600);

        try {
            $reader = IOFactory::createReaderForFile($this->filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $headers = $this->extractHeaderRow($sheet);
            $this->validateHeaders($headers);
            $columnCount = count($headers);
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = Coordinate::stringFromColumnIndex($columnCount);
            $batchSize = 500;
            $currentRow = 2;
            $processed = 0;

            DB::transaction(function () use ($sheet, $headers, $highestRow, $highestColumn, $batchSize, &$currentRow, &$processed, $cacheKey) {
                $chunk = [];
                for ($row = 2; $row <= $highestRow; $row++) {
                    $spreadsheetRow = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false)[0];
                    if ($this->isRowEmpty($spreadsheetRow)) {
                        continue;
                    }

                    $rowData = array_combine($headers, $spreadsheetRow);
                    $validatedData = $this->validateRow($rowData, $row);
                    $chunk[] = $validatedData;
                    $processed++;

                    if (count($chunk) >= $batchSize) {
                        $this->insertChunk($chunk);
                        $chunk = [];
                    }

                    $this->updateProgress($processed, $highestRow - 1, $cacheKey, "Importing rows {$processed} of " . max(1, $highestRow - 1));
                }

                if (!empty($chunk)) {
                    $this->insertChunk($chunk);
                }
            });

            Cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'module' => $this->module,
                'message' => "Import completed: {$processed} row(s) added.",
                'errors' => [],
                'completed_at' => now()->toDateTimeString(),
            ], 3600);
        } catch (Throwable $exception) {
            Cache::put($cacheKey, [
                'status' => 'failed',
                'progress' => 0,
                'module' => $this->module,
                'message' => $exception->getMessage(),
                'errors' => [],
            ], 3600);

            throw $exception;
        } finally {
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
            }

            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        }
    }

    protected function extractHeaderRow($sheet): array
    {
        $headerRow = $sheet->rangeToArray('A1:Z1', null, true, false)[0];
        return array_map(fn ($value) => trim((string) $value), array_filter($headerRow, fn ($value) => $value !== null && $value !== ''));
    }

    protected function validateHeaders(array $headers): void
    {
        $expected = $this->expectedHeaders();
        if ($headers !== $expected) {
            throw new RuntimeException(sprintf(
                'Template header mismatch for %s. Expected: %s. Found: %s',
                $this->module,
                implode(', ', $expected),
                implode(', ', $headers)
            ));
        }
    }

    protected function expectedHeaders(): array
    {
        return match ($this->module) {
            'subjek_pajak' => ['NIK', 'nama', 'alamat', 'RT', 'RW', 'no_hp'],
            'objek_pajak' => ['nop', 'nik_pemilik', 'letak_objek', 'luas_bumi', 'luas_bangunan', 'status_aktif'],
            'sppt' => ['nop', 'tahun', 'njop_bumi', 'njop_bangunan', 'pajak_terhutang', 'status_bayar'],
            default => throw new RuntimeException('Unknown import module: ' . $this->module),
        };
    }

    protected function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }
        return true;
    }

    protected function validateRow(array $row, int $rowNumber): array
    {
        $validator = Validator::make($row, $this->rules(), $this->messages());
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            throw new RuntimeException(sprintf('Row %d: %s', $rowNumber, implode('; ', $errors)));
        }

        return $this->normalizeRow($validator->validated());
    }

    protected function rules(): array
    {
        return match ($this->module) {
            'subjek_pajak' => [
                'NIK' => ['required', 'digits:16', 'unique:subjek_pajak,NIK'],
                'nama' => ['required', 'string', 'max:150'],
                'alamat' => ['required', 'string'],
                'RT' => ['required', 'string', 'size:3'],
                'RW' => ['required', 'string', 'size:3'],
                'no_hp' => ['required', 'string', 'max:15'],
            ],
            'objek_pajak' => [
                'nop' => ['required', 'string', 'max:18', 'unique:objek_pajak,nop'],
                'nik_pemilik' => ['required', 'string', 'exists:subjek_pajak,NIK'],
                'letak_objek' => ['required', 'string'],
                'luas_bumi' => ['required', 'integer', 'min:1'],
                'luas_bangunan' => ['required', 'integer', 'min:0'],
                'status_aktif' => ['required', 'boolean'],
            ],
            'sppt' => [
                'nop' => ['required', 'string', 'exists:objek_pajak,nop'],
                'tahun' => ['required', 'integer', 'min:1900', 'max:' . now()->year],
                'njop_bumi' => ['required', 'numeric', 'min:0'],
                'njop_bangunan' => ['required', 'numeric', 'min:0'],
                'pajak_terhutang' => ['required', 'numeric', 'min:0'],
                'status_bayar' => ['required', 'in:piutang,proses_pengajuan,lunas,ditolak'],
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'unique' => ':attribute already exists in the system.',
            'exists' => ':attribute does not exist in the database.',
            'digits' => ':attribute must be exactly :digits digits.',
            'size' => ':attribute must be exactly :size characters.',
            'integer' => ':attribute must be an integer.',
            'numeric' => ':attribute must be a number.',
            'boolean' => ':attribute must be 0 or 1.',
            'in' => ':attribute has an invalid selection.',
            'min' => ':attribute must be at least :min.',
            'max' => ':attribute may not be greater than :max.',
        ];
    }

    protected function normalizeRow(array $row): array
    {
        return match ($this->module) {
            'subjek_pajak' => [
                'NIK' => $row['NIK'],
                'nama' => $row['nama'],
                'alamat' => $row['alamat'],
                'RT' => $row['RT'],
                'RW' => $row['RW'],
                'no_hp' => $row['no_hp'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'objek_pajak' => [
                'nop' => $row['nop'],
                'nik_pemilik' => $row['nik_pemilik'],
                'letak_objek' => $row['letak_objek'],
                'luas_bumi' => (int) $row['luas_bumi'],
                'luas_bangunan' => (int) $row['luas_bangunan'],
                'status_aktif' => filter_var($row['status_aktif'], FILTER_VALIDATE_BOOLEAN),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'sppt' => [
                'nop' => $row['nop'],
                'tahun' => (int) $row['tahun'],
                'njop_bumi' => $row['njop_bumi'],
                'njop_bangunan' => $row['njop_bangunan'],
                'pajak_terhutang' => $row['pajak_terhutang'],
                'status_bayar' => $row['status_bayar'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            default => $row,
        };
    }

    protected function insertChunk(array $chunk): void
    {
        if ($this->module === 'subjek_pajak') {
            SubjekPajak::insert($chunk);
            return;
        }

        if ($this->module === 'objek_pajak') {
            ObjekPajak::insert($chunk);
            return;
        }

        if ($this->module === 'sppt') {
            Sppt::insert($chunk);
            return;
        }

        throw new RuntimeException('Unknown module during insert.');
    }

    protected function updateProgress(int $processed, int $total, string $cacheKey, string $message): void
    {
        $percent = $total > 0 ? min(100, (int) floor(($processed / $total) * 100)) : 100;
        Cache::put($cacheKey, [
            'status' => 'processing',
            'progress' => $percent,
            'module' => $this->module,
            'message' => $message,
            'errors' => [],
        ], 3600);
    }
}
