<?php

namespace App\Http\Controllers;

use App\Models\StoreContactMessage;
use App\Models\StoreContactReply;
use App\Services\TelegramBotService;
use App\Support\StoreDeliveryFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class StorefrontPageController extends Controller
{
    public function about(): View
    {
        return $this->infoPage(
            'about',
            'A little about us',
            'Good coffee. Good moments.',
            'A welcoming place for your everyday favourites. Explore the menu, order online, and let our team take care of the details.',
            'bi-cup-hot-fill',
            [
                ['icon' => 'bi-stars', 'title' => 'Made with care', 'body' => 'We aim to make every order feel considered, from choosing your favourites to preparing them for collection or delivery.'],
                ['icon' => 'bi-bag-heart', 'title' => 'Easy to order', 'body' => 'Browse the menu, add items to your cart, and choose the fulfilment option that works best for you.'],
                ['icon' => 'bi-chat-heart', 'title' => 'Here to help', 'body' => 'Have a question about a product or order? Our customer-service page gives you a direct way to reach the shop.'],
            ]
        );
    }

    public function shipping(): View
    {
        $deliveryFee = StoreDeliveryFee::current();

        return view('website.pages.info', [
            'pageKey' => 'shipping',
            'eyebrow' => 'Delivery & collection',
            'pageTitle' => 'Shipping information',
            'pageDescription' => 'Everything you need to know before choosing delivery or collecting your order.',
            'heroIcon' => 'bi-truck',
            'notice' => $deliveryFee > 0
                ? 'The currently configured website delivery fee is $'.number_format($deliveryFee, 2).' USD. The final fee is shown during checkout before you place your order.'
                : 'Website delivery is currently configured as free. Any delivery charge, if applicable to a special arrangement, will be confirmed by the shop before the order is accepted.',
            'cards' => [
                ['icon' => 'bi-geo-alt', 'title' => 'Enter a complete address', 'body' => 'For delivery, provide your house or building number, street, area and a nearby landmark. A reachable phone number helps the team confirm delivery details.'],
                ['icon' => 'bi-receipt', 'title' => 'Review the fee at checkout', 'body' => 'Your delivery fee and order total are displayed before you confirm checkout. No delivery fee is added to dine-in or takeaway orders.'],
                ['icon' => 'bi-signpost-split', 'title' => 'Follow your order', 'body' => 'Sign in and open My Orders to see the latest confirmed order and delivery progress. The shop updates the progress as the order is prepared and dispatched.'],
            ],
            'bottomTitle' => 'Need help with a delivery?',
            'bottomText' => 'Contact the shop and include your order number so the team can find the correct order quickly.',
        ]);
    }

    public function returns(): View
    {
        return view('website.pages.info', [
            'pageKey' => 'returns',
            'eyebrow' => 'Order care',
            'pageTitle' => 'Returns & order issues',
            'pageDescription' => 'If something is not right with your order, tell us. We will review the details and help find a fair resolution.',
            'heroIcon' => 'bi-shield-check',
            'notice' => 'Freshly prepared food and drinks are perishable. Please check your order when it is handed over and contact the shop promptly if an item is missing, incorrect or damaged.',
            'cards' => [
                ['icon' => 'bi-camera', 'title' => 'Share the details', 'body' => 'Contact us with your order number, a short description of the issue and a photo when it helps explain the problem.'],
                ['icon' => 'bi-chat-square-text', 'title' => 'We review each case', 'body' => 'Our team will review the order and contact you using the details you provide. A replacement, adjustment or other resolution depends on the circumstances.'],
                ['icon' => 'bi-credit-card', 'title' => 'Payment adjustments', 'body' => 'Where a refund is approved, the shop will confirm the amount and the available refund method. Refund timing depends on the payment provider or bank.'],
            ],
            'bottomTitle' => 'Something wrong with an order?',
            'bottomText' => 'Send the team a message and include your order number. We will use it to locate the transaction and review what happened.',
        ]);
    }

    public function contact(): View
    {
        return view('website.pages.contact');
    }

    public function submitContact(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        // A small honeypot catches simple bots without exposing a challenge to customers.
        if ($request->filled('website')) {
            return redirect()->route('web.contact')->with('contact_success', 'Thank you. Your message has been received.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'order_reference' => ['nullable', 'string', 'max:40'],
            'topic' => ['required', 'in:order,delivery,payment,product,feedback,other'],
            'message' => ['required', 'string', 'min:15', 'max:3000'],
            'website' => ['nullable', 'string', 'max:0'],
        ], [
            'message.min' => 'Please provide a little more detail so our team can help.',
            'topic.in' => 'Please select a valid enquiry topic.',
        ]);

        $customerUserId = null;
        if (auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer') {
            $customerUserId = auth()->id();
        }

        $message = StoreContactMessage::create([
            'user_id' => $customerUserId,
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => isset($validated['phone']) ? trim($validated['phone']) : null,
            'order_reference' => isset($validated['order_reference']) ? trim($validated['order_reference']) : null,
            'topic' => $validated['topic'],
            'message' => trim($validated['message']),
            'status' => 'new',
        ]);

        if ($customerUserId !== null) {
            $shopName = (string) (app('shop.settings')->shop_name ?: 'the shop team');
            StoreContactReply::create([
                'contact_message_id' => $message->id,
                'sender_type' => 'system',
                'sender_user_id' => null,
                'body' => 'Thank you for contacting '.$shopName.'. We have received your enquiry (Reference #'.$message->id.'). Our team will review it and reply in this inbox. You can return to My Account → Inbox to follow this conversation.',
            ]);
        }

        // The enquiry is saved first. Telegram is a notification channel, not the source of truth.
        if ($telegram->isConfigured()) {
            try {
                $shopName = (string) (app('shop.settings')->shop_name ?: 'Your store');
                $text = "NEW CUSTOMER ENQUIRY\n"
                    .'Store: '.$shopName."\n"
                    .'Reference: #'.$message->id."\n"
                    .'Topic: '.ucfirst(str_replace('_', ' ', $message->topic))."\n"
                    .'Name: '.$message->name."\n"
                    .'Email: '.$message->email."\n"
                    .'Phone: '.($message->phone ?: 'Not provided')."\n"
                    .'Order: '.($message->order_reference ?: 'Not provided')."\n\n"
                    .$message->message."\n\n"
                    .'Open admin inbox: '.route('admin.customer-service.show', $message->id);

                $telegram->sendMessage((string) config('services.telegram.group_chat_id'), $text);
            } catch (\Throwable $exception) {
                Log::warning('Customer enquiry was saved but its Telegram notification failed.', [
                    'contact_message_id' => $message->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $redirect = redirect()->route('web.contact')
            ->with('contact_success', 'Thank you. Your message has been sent to our customer-service team.');
        if ($customerUserId !== null) {
            $redirect->with('contact_message_id', $message->id);
        }

        return $redirect;
    }

    private function infoPage(
        string $pageKey,
        string $eyebrow,
        string $pageTitle,
        string $pageDescription,
        string $heroIcon,
        array $cards
    ): View {
        return view('website.pages.info', compact(
            'pageKey', 'eyebrow', 'pageTitle', 'pageDescription', 'heroIcon', 'cards'
        ));
    }
}
