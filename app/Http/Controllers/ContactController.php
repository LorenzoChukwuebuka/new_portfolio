<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        // You can also send email notification here
        // Mail::to('your@email.com')->send(new ContactMessageReceived($message));

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your message! I will get back to you soon.',
        ]);
    }

    /**
     * Download CV.
     */
    public function downloadCv(int $id)
    {
        $cv = \App\Models\Cv::findOrFail($id);
        
        $cv->incrementDownloads();
        
        return response()->download(storage_path('app/' . $cv->file_path), $cv->file_name);
    }
}