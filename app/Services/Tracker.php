<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\RecordEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Writes timeline events and stores attachments for a communication record,
 * always linked to the acting user and their duty period.
 */
class Tracker
{
    public static function event(Model $record, string $event, ?string $description = null, array $extra = []): RecordEvent
    {
        $user = auth()->user();

        return $record->events()->create(array_merge([
            'event' => $event,
            'description' => $description,
            'occurred_at' => now(),
            'user_id' => $user->id,
            'duty_period_id' => Duty::periodFor($user)?->id,
        ], $extra));
    }

    /** @param  array<int, UploadedFile>|UploadedFile|null  $files */
    public static function attach(Model $record, array|UploadedFile|null $files, string $category = 'document', ?string $description = null): void
    {
        $user = auth()->user();
        $files = array_filter(is_array($files) ? $files : [$files]);

        foreach ($files as $file) {
            $folder = 'attachments/'.strtolower(class_basename($record)).'/'.$record->getKey();
            $path = $file->store($folder, 'local');

            $attachment = $record->attachments()->create([
                'category' => $category,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'description' => $description,
                'uploaded_by' => $user->id,
                'duty_period_id' => Duty::periodFor($user)?->id,
            ]);

            self::event($record, 'attachment', $attachment->categoryLabel().': '.$attachment->original_name);
            Audit::log('attachment.uploaded', $record, 'Uploaded '.$attachment->original_name, null, ['category' => $category]);
        }
    }

    public static function exists(Attachment $attachment): bool
    {
        return Storage::disk('local')->exists($attachment->path);
    }
}
