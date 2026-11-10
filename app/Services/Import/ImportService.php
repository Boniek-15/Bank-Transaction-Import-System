<?php

namespace App\Services\Import;

use App\Exceptions\InvalidImportFileException;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Rules\Iban;
use App\Services\Parsers\CsvParser;
use App\Services\Parsers\JsonParser;
use App\Services\Parsers\Parser;
use App\Services\Parsers\XmlParser;
use Illuminate\Bus\Batch;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;

class ImportService
{
    public function processChunk(array $chunk, Import $import): array
    {
        $chunkFailed = 0;
        $transactions = [];
        $errorLogs = [];

        $insertResult = [
            'successful' => 0,
            'failed' => 0,
        ];

        foreach ($chunk as $row) {
            $row = is_array($row) ? Arr::only($row, [
                'transaction_id', 'account_number', 'transaction_date', 'amount', 'currency',
            ]) : [];

            $validator = Validator::make($row, [
                'transaction_id' => 'required|string|max:64',
                'account_number' => ['required', 'string', new Iban],
                'transaction_date' => 'required|date_format:Y-m-d',
                'amount' => 'required|numeric|gte:0.1',
                'currency' => 'required|alpha:ascii|size:3',
            ]);

            $isInvalid = $validator->fails();

            if ($isInvalid) {
                $errorLogs[] = [
                    'import_id' => $import->id,
                    'transaction_id' => isset($row['transaction_id'])
                        ? (string) $row['transaction_id']
                        : null,
                    'error_message' => implode('; ', $validator->errors()->all()),
                    'created_at' => now(),
                ];

                $chunkFailed++;

                continue;
            }

            $data = $validator->validated();
            $data['currency'] = strtoupper($data['currency']);
            $data['created_at'] = now();
            $data['import_id'] = $import->id;
            $transactions[] = $data;
        }
        $transactionIds = array_column($transactions, 'transaction_id');
        $existingIds = [];
        if (! empty($transactionIds)) {
            $existingIds = DB::table('transactions')
                ->whereIn('transaction_id', $transactionIds)
                ->pluck('transaction_id')
                ->toArray();
        }
        $existingIds = array_fill_keys($existingIds, true);
        $transactionIdsCount = array_count_values($transactionIds);
        $validTransactions = [];
        foreach ($transactions as $transaction) {
            $isInvalid = false;
            if (isset($existingIds[$transaction['transaction_id']]) || $transactionIdsCount[$transaction['transaction_id']] > 1) {
                $isInvalid = true;
            }

            if ($isInvalid) {
                $errorLogs[] = [
                    'import_id' => $import->id,
                    'transaction_id' => isset($transaction['transaction_id'])
                        ? (string) $transaction['transaction_id']
                        : null,
                    'error_message' => 'Transaction ID already exists or is duplicated in this import.',
                    'created_at' => now(),
                ];
                $chunkFailed++;
            } else {
                $validTransactions[] = $transaction;
            }
        }

        if (! empty($validTransactions)) {
            $insertResult = $this->insertWithDuplicateHandling(
                $validTransactions,
                $import
            );
        }

        if (! empty($errorLogs)) {
            DB::table('import_logs')->insert($errorLogs);
        }

        return [
            'successful' => $insertResult['successful'],
            'failed' => $chunkFailed + $insertResult['failed'],
        ];
    }

    public function handle(Import $import): Import
    {
        $start = hrtime(true);
        $path = "private/imports/{$import->upload_id}_merged";
        $extension = strtolower(pathinfo($import->file_name, PATHINFO_EXTENSION));
        $rows = $this->parserFor($extension)->parse(Storage::path($path));

        $jobs = [];
        $chunkIndex = 0;
        $totalRecords = 0;
        $chunk = [];

        try {
            foreach ($rows as $row) {
                $totalRecords++;
                $chunk[] = $row;

                if (count($chunk) === 2000) {
                    $json = json_encode($chunk, JSON_THROW_ON_ERROR);
                    $jsonPath = "private/chunks/{$import->id}/chunk_{$chunkIndex}.json";
                    if (! Storage::put($jsonPath, $json)) {
                        throw new Exception('Unable to write chunk file.');
                    }
                    $jobs[] = new ProcessImportJob(
                        $import->id,
                        $jsonPath,
                        $chunkIndex
                    );
                    $chunk = [];
                    $chunkIndex++;
                }
            }
            if (! empty($chunk)) {
                $json = json_encode($chunk, JSON_THROW_ON_ERROR);
                $jsonPath = "private/chunks/{$import->id}/chunk_{$chunkIndex}.json";
                if (! Storage::put($jsonPath, $json)) {
                    throw new Exception('Unable to write chunk file.');
                }
                $jobs[] = new ProcessImportJob(
                    $import->id,
                    $jsonPath,
                    $chunkIndex
                );
                $chunk = [];
                $chunkIndex++;
            }
            if ($totalRecords === 0) {
                throw new InvalidImportFileException('File does not contain any records');
            }
        } catch (\Throwable $e) {
            Storage::deleteDirectory("private/chunks/{$import->id}");
            throw $e;
        }

        DB::transaction(function () use ($jobs, $import, $totalRecords) {
            $chunkData = [];
            foreach ($jobs as $job) {
                $chunkData[] = [
                    'chunk_index' => $job->chunkIndex,
                    'status' => 'pending',
                ];
            }
            $import->chunks()->createMany($chunkData);

            $import->update([
                'total_records' => $totalRecords,
                'total_jobs' => count($jobs),
                'status' => 'running',
            ]);
        });

        $result = Bus::batch($jobs)->before(function (Batch $batch) use ($import) {
            $import->update([
                'batch_id' => $batch->id,
            ]);
        })->then(function () use ($import) {
            $importChunks = DB::table('import_chunks')->where('import_id', $import->id);
            $successfulRecords = $importChunks->sum('successful_records');
            $failedRecords = $importChunks->sum('failed_records');
            $processedRecords = $importChunks->sum('processed_records');

            $import->update([
                'successful_records' => $successfulRecords,
                'failed_records' => $failedRecords,
                'processed_records' => $processedRecords,
            ]);

            if ($import->processed_records === $import->total_records) {
                if ($import->successful_records === $import->total_records) {
                    $import->update([
                        'status' => 'success',
                    ]);
                } elseif ($import->successful_records > 0 && $import->failed_records > 0) {
                    $import->update([
                        'status' => 'partial',
                    ]);
                } else {
                    $import->update([
                        'status' => 'failed',
                    ]);
                }
            } else {
                $import->update([
                    'status' => 'failed',
                ]);
            }
        })->catch(function (Batch $batch, \Throwable $e) use ($import) {
            $import->refresh();
            $import->update([
                'status' => 'failed',
            ]);
        })->finally(function () use ($import,$start) {
            $result = (hrtime(true)-$start)/1000000000;
            Log::info("Import finished {$result} seconds");
            Storage::deleteDirectory("private/chunks/{$import->id}");
        })->onQueue('imports')->dispatch();

        return $import;
    }

    private function insertWithDuplicateHandling(array $transactions, Import $import): array
    {
        if (empty($transactions)) {
            return [
                'successful' => 0,
                'failed' => 0,
            ];
        }

        try {
            DB::table('transactions')->insert($transactions);
            return [
                'successful' => count($transactions),
                'failed' => 0,
            ];
        } catch (UniqueConstraintViolationException $e) {

            if (count($transactions) === 1) {
                $transaction = $transactions[0];

                DB::table('import_logs')->insert([
                    'import_id' => $import->id,
                    'transaction_id' => $transaction['transaction_id'],
                    'error_message' => 'Transaction ID already exists or is duplicated in this import.',
                    'created_at' => now(),
                ]);

                return [
                    'successful' => 0,
                    'failed' => 1,
                ];
            }

            $middle = (int) ceil(count($transactions) / 2);

            $firstHalf = array_slice($transactions, 0, $middle);
            $secondHalf = array_slice($transactions, $middle);

            $firstResult = $this->insertWithDuplicateHandling($firstHalf, $import);
            $secondResult = $this->insertWithDuplicateHandling($secondHalf, $import);

            return [
                'successful' => $firstResult['successful'] + $secondResult['successful'],
                'failed' => $firstResult['failed'] + $secondResult['failed'],
            ];
        }
    }

    private function parserFor(string $extension): Parser
    {
        return match (strtolower($extension)) {
            'csv' => new CsvParser,
            'json' => new JsonParser,
            'xml' => new XmlParser,
            default => throw new InvalidImportFileException('Unsupported file type.'),
        };
    }
}
