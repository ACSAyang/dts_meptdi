<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        'category', 'original_name', 'path', 'mime_type', 'size', 'description', 'uploaded_by', 'duty_period_id',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function categoryLabel(): string
    {
        return config('dts.attachment_categories')[$this->category] ?? $this->category;
    }

    public function humanSize(): string
    {
        $size = $this->size;
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($size < 1024) {
                return round($size, 1).' '.$unit;
            }
            $size /= 1024;
        }

        return round($size, 1).' TB';
    }
}
