<?php

namespace App\Services\Parsers;
use App\Exceptions\InvalidImportFileException;
use JsonException;
use App\Services\Parsers\Parser;

class JsonParser implements Parser
{
    public function parse(string $filePath): array
    {
        try {
            $data = json_decode(file_get_contents($filePath),true);
        } catch (JsonException){
            throw new InvalidImportFileException('File contains invalid JSON');
        }
        if(!is_array($data))
        {
            throw new InvalidImportFileException('File contains invalid JSON');
        }

        return $data;
    }
}
