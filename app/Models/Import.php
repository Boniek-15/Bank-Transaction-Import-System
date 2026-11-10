<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['file_name', 'total_records', 'successful_records', 'failed_records', 'status','upload_id','processed_records','batch_id','total_jobs'])]
class Import extends Model
{
    public const UPDATED_AT = null;

    protected $casts = ['total_records' => 'integer','successful_records'=>'integer','failed_records'=>'integer','processed_records'=>'integer','total_jobs'=>'integer'];
    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ImportChunk::class);
    }
}
