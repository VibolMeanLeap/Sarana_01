<?php

namespace App\Http\Controllers;

use App\Models\StoreContactMessage;
use App\Models\StoreContactReply;
use App\Services\AdminActionCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCustomerServiceController extends Controller
{
    public function index(Request $request): View
    {
        $messages = $this->conversationQuery($request)
            ->paginate(25)
            ->appends($request->only(['q', 'status']));

        $messages->setPath(route('admin.customer-service.index'));

        $selectedId = (int) $request->query('conversation', 0);
        $contactMessage = $selectedId > 0
            ? StoreContactMessage::query()->with(['customer', 'replies.sender'])->findOrFail($selectedId)
            : $messages->first();

        if ($contactMessage) {
            $this->markConversationRead($contactMessage);
            $contactMessage->setAttribute('unread_customer_replies_count', 0);
            $contactMessage->loadMissing(['customer', 'replies.sender']);
        }

        $counts = $this->counts();
        $lastReplyId = $contactMessage
            ? (int) ($contactMessage->replies()->max('id') ?? 0)
            : 0;

        return view('admin.customer-service.index', compact(
            'messages',
            'contactMessage',
            'counts',
            'lastReplyId'
        ));
    }

    /** Return the latest conversation list and unread totals for the Messenger-style inbox. */
    public function updates(Request $request, AdminActionCountService $actionCounts): JsonResponse
    {
        $messages = $this->conversationQuery($request)
            ->paginate(25)
            ->appends($request->only(['q', 'status']));

        $messages->setPath(route('admin.customer-service.index'));

        $selectedId = (int) $request->query('conversation', 0);
        $counts = $this->counts();

        return response()->json([
            'success' => true,
            'list_html' => view('admin.customer-service.partials.conversation-list', compact('messages', 'selectedId'))->render(),
            'counts' => $counts,
            'unread_count' => $counts['unread'],
            'admin_counts' => $actionCounts->get(),
            'checked_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /** Open a conversation inside the inbox without navigating to a separate detail page. */
    public function thread(StoreContactMessage $contactMessage, AdminActionCountService $actionCounts): JsonResponse
    {
        $this->markConversationRead($contactMessage);
        $contactMessage->load(['customer', 'replies.sender']);

        $lastReplyId = (int) ($contactMessage->replies()->max('id') ?? 0);
        $counts = $this->counts();

        return response()->json([
            'success' => true,
            'html' => view('admin.customer-service.partials.thread-panel', compact('contactMessage', 'lastReplyId'))->render(),
            'conversation_id' => (int) $contactMessage->id,
            'last_reply_id' => $lastReplyId,
            'status' => $contactMessage->status,
            'unread_count' => $counts['unread'],
            'counts' => $counts,
            'admin_counts' => $actionCounts->get(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /** Return only messages added after the browser's last seen reply ID. */
    public function threadUpdates(Request $request, StoreContactMessage $contactMessage, AdminActionCountService $actionCounts): JsonResponse
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $afterId = (int) ($validated['after_id'] ?? 0);
        $replies = $contactMessage->replies()
            ->with('sender')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get();

        $customerReplyIds = $replies
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->pluck('id');

        if ($customerReplyIds->isNotEmpty()) {
            $contactMessage->replies()
                ->whereIn('id', $customerReplyIds)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $lastReturnedId = (int) ($replies->last()?->id ?? $afterId);
        $counts = $this->counts();

        return response()->json([
            'success' => true,
            'html' => view('admin.customer-service.partials.reply-items', [
                'replies' => $replies,
                'customerName' => $contactMessage->name ?: 'Customer',
            ])->render(),
            'last_reply_id' => max($afterId, $lastReturnedId),
            'status' => $contactMessage->status,
            'unread_count' => $counts['unread'],
            'counts' => $counts,
            'admin_counts' => $actionCounts->get(),
            'checked_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /** Legacy detail links now open the selected conversation in the unified inbox. */
    public function show(StoreContactMessage $contactMessage): RedirectResponse
    {
        return redirect()->route('admin.customer-service.index', ['conversation' => $contactMessage->id]);
    }

    public function reply(Request $request, StoreContactMessage $contactMessage): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        $reply = StoreContactReply::create([
            'contact_message_id' => $contactMessage->id,
            'sender_type' => 'admin',
            'sender_user_id' => auth()->id(),
            'body' => trim($validated['body']),
        ]);

        // A staff response reopens a resolved conversation for customer follow-up.
        $contactMessage->status = 'in_progress';
        $contactMessage->handled_by_user_id = auth()->id();
        $contactMessage->handled_at = null;
        $contactMessage->save();

        $reply->load('sender');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your reply has been sent to the customer inbox.',
                'reply_html' => view('admin.customer-service.partials.reply-items', [
                    'replies' => collect([$reply]),
                    'customerName' => $contactMessage->name ?: 'Customer',
                ])->render(),
                'last_reply_id' => (int) $reply->id,
                'status' => $contactMessage->status,
                'counts' => $this->counts(),
            ], 201)->header('Cache-Control', 'no-store');
        }

        return redirect()->route('admin.customer-service.show', $contactMessage)
            ->with('success', 'Your reply has been sent to the customer inbox.');
    }

    public function update(Request $request, StoreContactMessage $contactMessage): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,in_progress,resolved'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $contactMessage->status = $validated['status'];
        $contactMessage->admin_note = isset($validated['admin_note']) ? trim($validated['admin_note']) : null;
        $contactMessage->handled_by_user_id = auth()->id();
        $contactMessage->handled_at = $validated['status'] === 'resolved' ? now() : null;
        $contactMessage->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Conversation details updated.',
                'status' => $contactMessage->status,
                'counts' => $this->counts(),
            ])->header('Cache-Control', 'no-store');
        }

        return redirect()->route('admin.customer-service.show', $contactMessage)
            ->with('success', 'Customer enquiry updated successfully.');
    }

    private function conversationQuery(Request $request)
    {
        $query = StoreContactMessage::query()
            ->with(['latestReply', 'latestCustomerReply'])
            ->withCount([
                'replies',
                'replies as unread_customer_replies_count' => function ($replyQuery) {
                    $replyQuery->where('sender_type', 'customer')->whereNull('read_at');
                },
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($request->filled('status') && in_array($request->input('status'), ['new', 'in_progress', 'resolved'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%')
                    ->orWhere('order_reference', 'like', '%'.$term.'%')
                    ->orWhere('message', 'like', '%'.$term.'%')
                    ->orWhereHas('replies', function ($replyQuery) use ($term) {
                        $replyQuery->where('body', 'like', '%'.$term.'%');
                    });
            });
        }

        return $query;
    }

    private function counts(): array
    {
        $counts = [
            'total' => StoreContactMessage::query()->count(),
            'new' => StoreContactMessage::query()->where('status', 'new')->count(),
            'in_progress' => StoreContactMessage::query()->where('status', 'in_progress')->count(),
            'resolved' => StoreContactMessage::query()->where('status', 'resolved')->count(),
            'unread' => StoreContactMessage::query()
                ->where('status', '<>', 'resolved')
                ->where(function ($query) {
                    $query->whereNull('read_at')->orWhereHas('replies', function ($replyQuery) {
                        $replyQuery->where('sender_type', 'customer')->whereNull('read_at');
                    });
                })
                ->count(),
        ];

        return $counts;
    }

    private function markConversationRead(StoreContactMessage $contactMessage): void
    {
        if ($contactMessage->read_at === null) {
            $contactMessage->forceFill(['read_at' => now()])->save();
        }

        $contactMessage->replies()
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
