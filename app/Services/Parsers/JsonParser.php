<?php

namespace App\Services\Parsers;
use App\Exceptions\InvalidImportFileException;
use App\Services\Parsers\Parser;
use \JsonMachine\Items;
use \JsonMachine\JsonDecoder\ExtJsonDecoder;

class JsonParser implements Parser
{
    public function parse(string $filePath): \Generator
    {
        $decoder = new ExtJsonDecoder(true);
        $json = Items::fromFile($filePath,['decoder'=>$decoder]);
        $columns = ['transaction_id','account_number','transaction_date','amount','currency'];

        try{
            foreach($json as $row){
                if(is_array($row)){
                    foreach($columns as $column){
                        if(!array_key_exists($column,$row)){
                            throw new InvalidImportFileException('File contains invalid JSON structure');
                        }
                    }
                    yield $row;
                }
                else{
                    throw new InvalidImportFileException('File contains invalid JSON structure');
                }
            }
        }
        catch (InvalidImportFileException $e){
            throw $e;
        }
        catch(\Exception $e){
            throw new InvalidImportFileException('...', 0, $e);
        }
    }
}
