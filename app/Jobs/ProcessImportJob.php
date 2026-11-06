<?php

namespace App\Jobs;

use App\Models\Import;
use App\Services\Import\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $importId)
    {
    }

    public function handle(ImportService $importService): void
    {
        $import = Import::findOrFail($this->importId);
        DB::table('transactions')->where(['import_id'=>$this->importId])->delete();
        $import->update(['status' => 'running']);
        $importService->handle($import);
        Storage::delete("private/imports/{$import->upload_id}_merged");
    }

    public function failed(?Throwable $exception): void
    {
        $import = Import::find($this->importId);
        if ($import) {
            $import->update(['status' => 'failed']);

            Storage::delete(
                "private/imports/{$import->upload_id}_merged"
            );
        }
    }
}
