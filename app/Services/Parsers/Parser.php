<?php

namespace App\Services\Parsers;
interface Parser
{
    public function parse(string $filePath): iterable;
}
