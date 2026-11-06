<?php

namespace App\Services\Parsers;
use App\Exceptions\InvalidImportFileException;
use App\Services\Parsers\Parser;

class CsvParser implements Parser
{
    public function parse(string $filePath): \Generator
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) throw new InvalidImportFileException('Cannot open file.');

        try{
            $header = fgetcsv($handle);
            if (!$header) throw new InvalidImportFileException('CSV is empty.');
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $header = array_map('trim', $header);

            while (($data = fgetcsv($handle)) !== false) {
                if ($data === [null]) continue;
                $data = array_slice(array_pad($data, count($header), null), 0, count($header));
                yield array_combine($header, array_map(fn($v) => $v === null ? null : trim($v), $data));
            }
        }finally{
            fclose($handle);
        }
    }
}
