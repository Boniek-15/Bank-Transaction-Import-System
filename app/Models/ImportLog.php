<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['import_id','transaction_id', 'error_message'])]
class ImportLog extends Model
{
    public const UPDATED_AT = null;

}
