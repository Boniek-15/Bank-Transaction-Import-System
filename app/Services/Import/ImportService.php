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
use Illuminate\Support\Facades\Validator;

class ImportService
{
    function handle(UploadedFile $file): Import
    {
        $rows = $this->parserFor($file)->parse($file->getRealPath());

        return DB::transaction(function () use ($file, $rows) {
            $import = Import::create([
                'file_name' => $file->getClientOriginalName(),
                'total_records' => count($rows),
                'status' => 'failed',
            ]);

            $ok = 0;
            $failed = 0;

            foreach ($rows as $row) {
                $row = is_array($row) ? Arr::only($row, [
                    'transaction_id', 'account_number', 'transaction_date', 'amount', 'currency',
                ]) : [];

                $validator = Validator::make($row, [
                    'transaction_id' => ['required', 'string', 'max:64', 'unique:transactions,transaction_id'],
                    'account_number' => ['required', 'string', new Iban(false)],
                    'transaction_date' => ['required', 'date_format:Y-m-d'],
                    'amount' => ['required', 'numeric', 'gt:0'],
                    'currency' => ['required', 'alpha:ascii', 'size:3'],
                ]);

                if ($validator->fails()) {
                    $import->logs()->create([
                        'transaction_id' => isset($row['transaction_id']) ? (string)$row['transaction_id'] : null,
                        'error_message' => implode('; ', $validator->errors()->all()),
                    ]);
                    $failed++;
                    continue;
                }

                $data = $validator->validated();
                $data['currency'] = strtoupper($data['currency']);
                Transaction::create($data);
                $ok++;
            }

            $import->update([
                'successful_records' => $ok,
                'failed_records' => $failed,
                'status' => match (true) {
                    $failed === 0 => 'success',
                    $ok === 0 => 'failed',
                    default => 'partial',
                },
            ]);

            return $import;
        });
    }

    private function parserFor(UploadedFile $file): Parser
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'csv' => new CsvParser(),
            'json' => new JsonParser(),
            'xml' => new XmlParser(),
            default => throw new InvalidImportFileException('Unsupported file type.'),
        };
    }
}
