<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidImportFileException;
use App\Http\Requests\StoreImportRequest;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Services\Import\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;


class ImportController extends Controller
{
    public function index()
    {
        return Import::latest('id')->get();
    }

    public function store(StoreImportRequest $request, ImportService $service)
    {
        try {
            $import = $service->handle($request->file('file'));
        } catch (InvalidImportFileException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($import, 201);
    }

    public function show(Import $import)
    {
        return $import->load('logs');
    }

    public function init(Request $request)
    {
        $validated = $request->validate([
            'file_name' => 'required|string',
            'file_size' => 'required|integer|min:1',
            'total_chunks' => 'required|integer|min:1',
        ]);
        $uploadId = Str::uuid();
        Import::create([
            'file_name' => $validated['file_name'],
            'upload_id' => $uploadId,
            'status' => 'pending',
        ]);
        Storage::makeDirectory("private/uploads/$uploadId");
        return response()->json([
            'upload_id' => (string) $uploadId
        ]);
    }

    public function chunk(Request $request)
    {
        $validated = $request->validate([
            'upload_id' => 'required|uuid',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'file' => 'required|file',
        ]);

        $path = $request->file('file')->storeAs(
            "private/uploads/{$validated['upload_id']}",
            "chunk_{$validated['chunk_index']}"
        );

        if (!$path) {
            return response()->json([
                'message' => 'Chunk uploads failed'
            ], 500);
        }

        return response()->json([
            'message' => 'Chunk uploaded successfully',
            'chunk_index' => $validated['chunk_index'],
        ]);
    }

    public function complete(Request $request, ImportService $service)
    {
        set_time_limit(900);
        $validated = $request->validate([
            'file_name' => 'required|string',
            'upload_id' => 'required|uuid',
            'total_chunks' => 'required|integer|min:1',
        ]);

        $directory = "private/uploads/{$validated['upload_id']}";
        $outputPath = "private/imports/{$validated['upload_id']}_merged";

        for ($i = 0; $i < $validated['total_chunks']; $i++) {
            $chunkPath = "{$directory}/chunk_{$i}";

            if (!Storage::exists($chunkPath)) {
                return response()->json([
                    'message' => 'Missing chunks'
                ], 422);
            }
        }

        Storage::makeDirectory('private/imports');

        $output = fopen(Storage::path($outputPath), 'w');

        if ($output === false) {
            return response()->json([
                'message' => 'Failed to create merged file'
            ], 500);
        }

        for ($i = 0; $i < $validated['total_chunks']; $i++) {
            $chunkPath = Storage::path("{$directory}/chunk_{$i}");
            $chunk = fopen($chunkPath, 'r');

            if ($chunk === false) {
                fclose($output);

                return response()->json([
                    'message' => 'Failed to open chunk'
                ], 500);
            }

            $copied = stream_copy_to_stream($chunk, $output);

            fclose($chunk);

            if ($copied === false) {
                fclose($output);

                return response()->json([
                    'message' => 'Failed to merge chunks'
                ], 500);
            }
        }

        fclose($output);

        $import = Import::where('upload_id', $validated['upload_id'])->firstOrFail();
        $service->handle($import);

        Storage::deleteDirectory($directory);

        return response()->json([
            'import' => $import,
            'message' => 'Import queued successfully',
        ], 200);
    }
}
