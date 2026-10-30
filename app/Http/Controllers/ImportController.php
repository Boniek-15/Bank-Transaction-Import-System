<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidImportFileException;
use App\Http\Requests\StoreImportRequest;
use App\Models\Import;
use App\Models\ImportLog;
use App\Services\Import\ImportService;
use Illuminate\Database\Eloquent\Relations\HasMany;


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

}
