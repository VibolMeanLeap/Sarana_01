<?php

namespace App\Http\Controllers;

use App\Models\StoreContactMessage;
use App\Models\StoreContactReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerInboxController extends Controller
{
    public function index(): View
    {
        $messages = StoreContactMessage::query()
            ->where('user_id', auth()->id())
            ->withCount(['replies as unread_replies_count' => function ($query) {
                $query->whereIn('sender_type', ['admin', 'system'])->whereNull('read_at');
            }])
            ->orderByDesc('updated_at')
            ->paginate(10);

        $unreadCount = (int) $messages->getCollection()->sum('unread_replies_count');

        return view('website.inbox.index', compact('messages', 'unreadCount'));
    }

    public function show(int $contactMessageId): View
    {
        $contactMessage = StoreContactMessage::query()
            ->where('user_id', auth()->id())
            ->whereKey($contactMessageId)
            ->firstOrFail();

        $contactMessage->replies()
            ->whereIn('sender_type', ['admin', 'system'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $contactMessage->load(['replies.sender']);

        return view('website.inbox.show', compact('contactMessage'));
    }

    public function reply(Request $request, int $contactMessageId): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        $contactMessage = StoreContactMessage::query()
            ->where('user_id', auth()->id())
            ->whereKey($contactMessageId)
            ->firstOrFail();

        StoreContactReply::create([
            'contact_message_id' => $contactMessage->id,
            'sender_type' => 'customer',
            'sender_user_id' => auth()->id(),
            'body' => trim($validated['body']),
        ]);

        // Customer follow-up reopens a completed conversation for staff review.
        $contactMessage->status = 'in_progress';
        $contactMessage->handled_at = null;
        $contactMessage->save();

        return redirect()->route('web.inbox.show', $contactMessage->id)
            ->with('success', 'Your message has been sent to the shop.');
    }
}
