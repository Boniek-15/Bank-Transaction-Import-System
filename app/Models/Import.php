<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['file_name', 'total_records', 'successful_records', 'failed_records', 'status','upload_id'])]
class Import extends Model
{
    public const UPDATED_AT = null;
    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }
}
