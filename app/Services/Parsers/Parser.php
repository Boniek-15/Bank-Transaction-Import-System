<?php

namespace App\Services\Parsers;
interface Parser
{
    public function parse(string $file_path): array;
}
