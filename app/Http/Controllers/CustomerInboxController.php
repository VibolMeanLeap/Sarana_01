<?php

namespace App\Http\Controllers;

use App\Models\StoreContactMessage;
use App\Models\StoreContactReply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerInboxController extends Controller
{
    public function index(): View
    {
        $messages = $this->customerMessages();
        $unreadCount = $this->unreadReplyCount();

        return view('website.inbox.index', compact('messages', 'unreadCount'));
    }

    /** Return an updated conversation list without reloading the customer page. */
    public function updates(Request $request): JsonResponse
    {
        $messages = $this->customerMessages();
        $unreadCount = $this->unreadReplyCount();

        return response()->json([
            'html' => view('website.inbox.partials.message-list', compact('messages'))->render(),
            'unread_count' => $unreadCount,
            'updated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function show(int $contactMessageId): View
    {
        $contactMessage = $this->ownedConversation($contactMessageId);

        $contactMessage->replies()
            ->whereIn('sender_type', ['admin', 'system'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $contactMessage->load(['replies.sender']);

        return view('website.inbox.show', compact('contactMessage'));
    }

    /** Return only new replies for this customer's own conversation. */
    public function threadUpdates(Request $request, int $contactMessageId): JsonResponse
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $contactMessage = $this->ownedConversation($contactMessageId);
        $afterId = (int) ($validated['after_id'] ?? 0);

        $newReplies = $contactMessage->replies()
            ->with('sender')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get();

        if ($newReplies->isNotEmpty()) {
            $contactMessage->replies()
                ->whereIn('id', $newReplies->pluck('id'))
                ->whereIn('sender_type', ['admin', 'system'])
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $lastReplyId = (int) ($contactMessage->replies()->max('id') ?? $afterId);
        $unreadCount = $this->unreadReplyCount();

        return response()->json([
            'html' => view('website.inbox.partials.reply-items', ['replies' => $newReplies])->render(),
            'last_reply_id' => max($afterId, $lastReplyId),
            'unread_count' => $unreadCount,
            'status' => $contactMessage->status,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function reply(Request $request, int $contactMessageId): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        $contactMessage = $this->ownedConversation($contactMessageId);

        $reply = StoreContactReply::create([
            'contact_message_id' => $contactMessage->id,
            'sender_type' => 'customer',
            'sender_user_id' => auth()->id(),
            'body' => trim($validated['body']),
        ]);

        // A customer follow-up reopens a completed conversation for staff review.
        $contactMessage->status = 'in_progress';
        $contactMessage->handled_at = null;
        $contactMessage->save();

        if ($request->expectsJson()) {
            $reply->load('sender');
            return response()->json([
                'message' => 'Your message has been sent to the shop.',
                'reply_html' => view('website.inbox.partials.reply-items', ['replies' => collect([$reply])])->render(),
                'last_reply_id' => (int) ($contactMessage->replies()->max('id') ?? $reply->id),
                'unread_count' => $this->unreadReplyCount(),
                'status' => $contactMessage->status,
            ], 201)->header('Cache-Control', 'no-store');
        }

        return redirect()->route('web.inbox.show', $contactMessage->id)
            ->with('success', 'Your message has been sent to the shop.');
    }

    private function customerMessages(): LengthAwarePaginator
    {
        $messages = StoreContactMessage::query()
            ->where('user_id', auth()->id())
            ->withCount(['replies as unread_replies_count' => function ($query) {
                $query->whereIn('sender_type', ['admin', 'system'])->whereNull('read_at');
            }])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10);

        // The update endpoint also renders this partial; keep its pagination
        // links pointed at the HTML inbox page rather than the JSON endpoint.
        $messages->setPath(route('web.inbox'));

        return $messages->withQueryString();
    }

    private function ownedConversation(int $contactMessageId): StoreContactMessage
    {
        return StoreContactMessage::query()
            ->where('user_id', auth()->id())
            ->whereKey($contactMessageId)
            ->firstOrFail();
    }

    private function unreadReplyCount(): int
    {
        return StoreContactReply::query()
            ->whereIn('sender_type', ['admin', 'system'])
            ->whereNull('read_at')
            ->whereHas('contactMessage', fn ($query) => $query->where('user_id', auth()->id()))
            ->count();
    }
}
