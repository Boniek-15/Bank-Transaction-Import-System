<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\ImportChunk;
use App\Services\Import\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use Illuminate\Bus\Batchable;

class ProcessImportJob implements ShouldQueue
{
    use Batchable, Queueable;

    public $tries = 3;

    public $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $importId, public string $chunkPath, public int $chunkIndex) {}

    public function handle(ImportService $importService): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $chunkCompleted = false;

        DB::transaction(function () use ($importService, &$chunkCompleted) {
            $importChunk = ImportChunk::where('import_id', $this->importId)->where('chunk_index', $this->chunkIndex)->lockForUpdate()->firstOrFail();
            if ($importChunk->status === 'completed') {
                $chunkCompleted = true;

                return;
            }
            if (! Storage::disk('local')->exists($this->chunkPath)) {
                throw new RuntimeException('Chunk file not found');
            }
            $file = Storage::disk('local')->get($this->chunkPath);
            $file = json_decode($file, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($file)) {
                throw new RuntimeException('Chunk file is not valid');
            }

            $import = Import::findOrFail($this->importId);
            $result = $importService->processChunk($file, $import);

            $total = $result['successful'] + $result['failed'];

            $importChunk->processed_records = $total;
            $importChunk->successful_records = $result['successful'];
            $importChunk->failed_records = $result['failed'];
            $importChunk->status = 'completed';
            $importChunk->save();
            $chunkCompleted = true;
        },5);

        if ($chunkCompleted) {
            Storage::disk('local')->delete($this->chunkPath);
        }
    }

    public function failed(?Throwable $exception): void {}
}
