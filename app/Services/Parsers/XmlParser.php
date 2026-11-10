<?php

namespace App\Services\Parsers;
use App\Exceptions\InvalidImportFileException;

class XmlParser implements Parser
{
    public function parse(string $filePath): \Generator
    {
        $xmlReader = new \XMLReader();
        if(!$xmlReader->open($filePath))
        {
            throw new InvalidImportFileException('Invalid XML file.');
        }
        $row = [];
        try {
            while($xmlReader->read()) {
                if ($xmlReader->nodeType == $xmlReader::ELEMENT) {
                    if ($xmlReader->localName == 'transaction') {
                        $row = [];
                        continue;
                    }
                    $name = $xmlReader->localName;
                    $value = $xmlReader->readString();
                    $row[$name] = $value;
                }
                if ($xmlReader->nodeType == $xmlReader::END_ELEMENT) {
                    if ($xmlReader->localName == 'transaction') {
                        yield $row;
                    }
                }
            }
        }
        catch (Throwable $throwable) {
            throw new RuntimeException($throwable->getMessage());
        }
        finally{
            $xmlReader->close();
        }
    }
}
