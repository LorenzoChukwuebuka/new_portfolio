<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * Display a listing of contact messages.
     */
    public function index(Request $request)
    {
        $query = ContactMessage::query();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('subject', 'like', '%' . $request->search . '%')
                    ->orWhere('message', 'like', '%' . $request->search . '%');
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($messages);
    }

    /**
     * Display the specified contact message.
     */
    public function show(ContactMessage $contactMessage)
    {
        // Auto-mark as read when viewed
        if ($contactMessage->status === 'unread') {
            $contactMessage->markAsRead();
        }

        return response()->json($contactMessage);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(ContactMessage $contactMessage)
    {
        $contactMessage->markAsRead();

        return response()->json([
            'message' => 'Message marked as read',
            'contact_message' => $contactMessage->fresh(),
        ]);
    }

    /**
     * Mark message as replied.
     */
    public function markAsReplied(ContactMessage $contactMessage)
    {
        $contactMessage->markAsReplied();

        return response()->json([
            'message' => 'Message marked as replied',
            'contact_message' => $contactMessage->fresh(),
        ]);
    }

    /**
     * Mark multiple messages as read.
     */
    public function bulkMarkAsRead(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contact_messages,id',
        ]);

        ContactMessage::whereIn('id', $request->ids)
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'Messages marked as read',
        ]);
    }

    /**
     * Remove the specified contact message.
     */
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->json([
            'message' => 'Message deleted successfully',
        ]);
    }

    /**
     * Bulk delete messages.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:contact_messages,id',
        ]);

        ContactMessage::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => 'Messages deleted successfully',
        ]);
    }

    /**
     * Get unread count.
     */
    public function unreadCount()
    {
        $count = ContactMessage::unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get stats.
     */
    public function stats()
    {
        $stats = [
            'total' => ContactMessage::count(),
            'unread' => ContactMessage::unread()->count(),
            'read' => ContactMessage::read()->count(),
            'replied' => ContactMessage::where('status', 'replied')->count(),
            'today' => ContactMessage::whereDate('created_at', today())->count(),
            'this_week' => ContactMessage::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
        ];

        return response()->json($stats);
    }
}