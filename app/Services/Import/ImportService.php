<?php

namespace App\Services\Import;
use App\Exceptions\InvalidImportFileException;
use App\Models\Import;
use App\Rules\Iban;
use App\Services\Parsers\CsvParser;
use App\Services\Parsers\JsonParser;
use App\Services\Parsers\Parser;
use App\Services\Parsers\XmlParser;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportService
{
    function processChunk(array $chunk, Import $import,array &$duplicates): array
    {
        $chunkOk = 0;
        $chunkFailed = 0;
        $transactions = [];
        $errorLogs = [];
        DB::transaction(function () use (&$chunk, $import, &$chunkOk, &$chunkFailed,&$duplicates, &$transactions,&$errorLogs) {
            $transactionIds = array_column($chunk, 'transaction_id');
            $existingIds = DB::table('transactions')
                ->whereIn('transaction_id', $transactionIds)
                ->pluck('transaction_id')
                ->toArray();

            $existingIds = array_fill_keys($existingIds, true);

            foreach ($chunk as $row) {
                $row = is_array($row) ? Arr::only($row, [
                    'transaction_id', 'account_number', 'transaction_date', 'amount', 'currency',
                ]) : [];

                $validator = Validator::make($row, [
                    'transaction_id' => 'required|string|max:64',
                    'account_number' => ['required', 'string', new Iban()],
                    'transaction_date' => 'required|date_format:Y-m-d',
                    'amount' => 'required|numeric|gte:0.1',
                    'currency' => 'required|alpha:ascii|size:3',
                ]);

                $isInvalid = $validator->fails();

                if (!empty($row['transaction_id'])) {
                    if(isset($existingIds[$row['transaction_id']])) {
                        $isInvalid = true;
                        $validator->errors()->add('transaction_id', $row['transaction_id']);
                    } else {
                        if(!isset($duplicates[$row['transaction_id']])) {
                            $duplicates[$row['transaction_id']] = true;
                        }
                        else{
                            $isInvalid = true;
                            $validator->errors()->add('transaction_id', $row['transaction_id']);
                        }
                    }
                }

                if ($isInvalid) {

                    $errorLogs[] = [
                        'import_id' => $import->id,
                        'transaction_id' => isset($row['transaction_id'])
                            ? (string)$row['transaction_id']
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
                $chunkOk++;
            }
            if (!empty($transactions)) {
                DB::table('transactions')->insert($transactions);
            }

            if (!empty($errorLogs)) {
                DB::table('import_logs')->insert($errorLogs);
            }
        });

        return [
            'successful' => $chunkOk,
            'failed' => $chunkFailed,
        ];

    }

    function handle(Import $import): Import
    {
        $path = "private/imports/{$import->upload_id}_merged";
        $extension = strtolower(pathinfo($import->file_name, PATHINFO_EXTENSION));
        $rows = $this->parserFor($extension)->parse(Storage::path($path));

        $ok = 0;
        $failed = 0;
        $duplicates = [];
        $totalRecords = 0;
        $chunk = [];

        $import->update([
            'status'=>'running',
        ]);

            foreach ($rows as $row) {
                $totalRecords++;
                $chunk[] = $row;

                if (count($chunk) === 500) {
                    $result = $this->processChunk($chunk, $import, $duplicates);
                    $ok+=$result['successful'];
                    $failed+=$result['failed'];
                    $chunk=[];
                }
            }

            if(!empty($chunk)){
                $result = $this->processChunk($chunk, $import, $duplicates);
                $ok+=$result['successful'];
                $failed+=$result['failed'];
            }

            $import->update([
                'total_records' => $totalRecords,
                'successful_records' => $ok,
                'failed_records' => $failed,
                'status' => match (true) {
                    $failed === 0 => 'success',
                    $ok === 0 => 'failed',
                    default => 'partial',
                }
            ]);

        return $import;

    }

    private function parserFor(string $extension): Parser
    {
        return match (strtolower($extension)) {
            'csv' => new CsvParser(),
            'json' => new JsonParser(),
            'xml' => new XmlParser(),
            default => throw new InvalidImportFileException('Unsupported file type.'),
        };
    }
}
