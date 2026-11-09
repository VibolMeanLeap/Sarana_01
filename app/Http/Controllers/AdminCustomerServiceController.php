<?php

namespace App\Http\Controllers;

use App\Models\StoreContactMessage;
use App\Models\StoreContactReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCustomerServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = StoreContactMessage::query()->latest('created_at');

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
                    ->orWhere('message', 'like', '%'.$term.'%');
            });
        }

        $messages = $query->paginate(15)->withQueryString();
        $counts = [
            'new' => StoreContactMessage::where('status', 'new')->count(),
            'in_progress' => StoreContactMessage::where('status', 'in_progress')->count(),
            'resolved' => StoreContactMessage::where('status', 'resolved')->count(),
            'total' => StoreContactMessage::count(),
        ];

        return view('admin.customer-service.index', compact('messages', 'counts'));
    }

    public function show(StoreContactMessage $contactMessage): View
    {
        if ($contactMessage->read_at === null) {
            $contactMessage->forceFill(['read_at' => now()])->save();
        }

        $contactMessage->replies()
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $contactMessage->load(['replies.sender']);

        return view('admin.customer-service.show', compact('contactMessage'));
    }

    public function reply(Request $request, StoreContactMessage $contactMessage): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        StoreContactReply::create([
            'contact_message_id' => $contactMessage->id,
            'sender_type' => 'admin',
            'sender_user_id' => auth()->id(),
            'body' => trim($validated['body']),
        ]);

        // A new staff response reopens a previously resolved conversation.
        $contactMessage->status = 'in_progress';
        $contactMessage->handled_by_user_id = auth()->id();
        $contactMessage->handled_at = null;
        $contactMessage->save();

        return redirect()->route('admin.customer-service.show', $contactMessage)
            ->with('success', 'Your reply has been sent to the customer inbox.');
    }

    public function update(Request $request, StoreContactMessage $contactMessage): RedirectResponse
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

        return redirect()->route('admin.customer-service.show', $contactMessage)
            ->with('success', 'Customer enquiry updated successfully.');
    }
}
