<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\Cv;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ContactController extends Controller
{
    /**
     * Store a newly created contact message.
     */
    public function store(ContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'ip_address' => $request->ip(),
        ]);

        $recipient = config('mail.contact_to');

        if ($recipient) {
            try {
                Mail::to($recipient)->send(new ContactMessageReceived($message));
            } catch (Throwable $exception) {
                Log::warning('Contact notification email could not be sent.', [
                    'message_id' => $message->id,
                    'error'      => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your message! I will get back to you soon.',
        ]);
    }

    /**
     * Download CV.
     */
    public function activeCv(): JsonResponse
    {
        $cv = Cv::active()->first();

        if (! $cv) {
            return response()->json(['message' => 'No active CV found.'], 404);
        }

        return response()->json([
            'title'        => $cv->title,
            'download_url' => route('cv.download.active'),
        ]);
    }

    /**
     * Download the active public CV.
     */
    public function downloadActiveCv()
    {
        $cv = Cv::active()->firstOrFail();

        $cv->incrementDownloads();

        return Storage::disk('public')->download($cv->file_path, $cv->file_name);
    }
}
