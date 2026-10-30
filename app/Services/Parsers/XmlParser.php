<?php

namespace App\Services\Parsers;
use App\Exceptions\InvalidImportFileException;
use App\Services\Parsers\Parser;
use function PHPUnit\Framework\isList;

class XmlParser implements Parser
{
    public function parse(string $filePath): array
    {
        $xml = simplexml_load_file($filePath);
        if($xml === false)
        {
            throw new InvalidImportFileException('Invalid XML file.');
        }
        $rows = [];
        foreach ($xml->transaction as $node) {
            $row = [];
            foreach ($node->children() as $name => $value) {
                $row[$name] = trim((string) $value);
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
