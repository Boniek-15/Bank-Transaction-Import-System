<?php

namespace App\Services\Parsers;
use App\Exceptions\InvalidImportFileException;
use App\Services\Parsers\Parser;
use Illuminate\Support\Facades\Log;

class CsvParser implements Parser
{
    public function parse(string $filePath): \Generator
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) throw new InvalidImportFileException('Cannot open file.');

        try{
            $header = fgetcsv($handle,null,',','"','');
            if (!$header) throw new InvalidImportFileException('CSV is empty.');
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $header = array_map('trim', $header);
            while (($data = fgetcsv($handle,null,',','"','')) !== false) {
                if ($data === [null]) continue;
                yield array_combine($header, array_map(fn($v) => $v === null ? null : trim($v), $data));
            }
        }finally{
            fclose($handle);
        }
    }
}
