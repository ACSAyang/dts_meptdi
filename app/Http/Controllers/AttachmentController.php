<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /** Attachments are stored privately and served through here so every opening is recorded in the audit trail. */
    public function show(Request $request, Attachment $attachment)
    {
        $record = $attachment->attachable;
        abort_unless($record, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404, 'The file is missing from storage.');

        Audit::log('attachment.viewed', $record, 'Opened '.$attachment->original_name);

        return $request->boolean('download')
            ? Storage::disk('local')->download($attachment->path, $attachment->original_name)
            : Storage::disk('local')->response($attachment->path, $attachment->original_name);
    }
}
