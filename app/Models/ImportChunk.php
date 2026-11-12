<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['import_id','chunk_index', 'status'])]
class ImportChunk extends Model
{
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
