@extends('layout.web')
@section('web_title', 'Your cart')
@section('content')
<style>
    .store-page{background:#f7faf7;min-height:65vh;padding:42px 0 70px}.store-heading{font-weight:850;letter-spacing:-.045em;color:#183326}.cart-panel{border:1px solid #e7eee8;border-radius:24px;background:#fff;box-shadow:0 16px 44px rgba(24,56,38,.055)}.cart-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:18px;padding:20px 0;border-bottom:1px solid #edf2ee}.cart-row:last-child{border-bottom:0}.cart-qty{width:92px;text-align:center;border-radius:99px}.cart-total{font-size:1.65rem;font-weight:900;color:#167549;letter-spacing:-.04em}.cart-checkout{border-radius:99px;padding:13px 22px;font-weight:800;background:#1d7548;border:0}.cart-empty{border:1px dashed #cbd9cd;border-radius:24px;background:#fff;padding:60px 20px;text-align:center}@media(max-width:767px){.cart-row{grid-template-columns:1fr auto;gap:12px}.cart-row .cart-actions{grid-column:1/-1;justify-content:space-between}.cart-total{font-size:1.4rem}}
</style>
<section class="store-page">
    <div class="container" style="max-width:1040px">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.14em">Your selection</div><h1 class="display-6 store-heading mb-1">Shopping cart</h1><p class="text-muted mb-0">Review your items and active offers before checkout.</p></div>
            <a href="{{ route('web.shop') }}" class="btn btn-outline-success rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i>Continue shopping</a>
        </div>
        @include('admin.message.message')
        @if($items->isEmpty())
            <div class="cart-empty"><div class="display-4 text-success mb-3"><i class="bi bi-bag"></i></div><h2 class="h4 fw-bold">Your cart is waiting for something good.</h2><p class="text-muted mb-4">Explore our menu and add your coffee and snack favourites.</p><a href="{{ route('web.shop') }}" class="btn btn-success rounded-pill px-4 py-2">Browse the menu <i class="bi bi-arrow-right ms-1"></i></a></div>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="cart-panel p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 fw-bold mb-0">Your items</h2><span class="badge text-bg-light rounded-pill">{{ $items->sum('quantity') }} item(s)</span></div>
                        @foreach($items as $item)
                            <div class="cart-row">
                                <div>
                                    <a href="{{ route('web.product.show', $item->product->product_id) }}" class="fw-bold text-decoration-none text-dark">{{ $item->product->product_name }}</a>
                                    <div class="small text-muted mt-1">{{ $item->product->category->category_name ?? 'Coffee Corner' }} · ${{ number_format($item->unit_price, 2) }} each</div>
                                    @if($item->offer)<div class="small text-success fw-bold mt-1">{{ number_format((float)$item->offer->discount, 2) }}% offer · saved ${{ number_format($item->line_saving, 2) }}</div>@endif
                                    @if($item->product->isStockTracked())
                                        <div class="small {{ $item->stock_issue ? 'text-danger fw-bold' : 'text-muted' }} mt-1">{{ $item->available_quantity }} available</div>
                                    @else
                                        <div class="small text-success mt-1">Made to order · no stock limit</div>
                                    @endif
                                    @if($item->stock_issue)<div class="small text-danger fw-semibold mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Reduce quantity to continue checkout.</div>@endif
                                </div>
                                <div class="text-end"><div class="fw-bold text-success">${{ number_format($item->line_total, 2) }}</div></div>
                                <div class="cart-actions d-flex align-items-center gap-2">
                                    <form method="POST" action="{{ route('web.cart.update', $item->product->product_id) }}" class="d-flex align-items-center gap-2">@csrf @method('PATCH')<input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->isStockTracked() ? max(1, (int)$item->product->qty) : 1000 }}" class="form-control form-control-sm cart-qty" aria-label="Quantity for {{ $item->product->product_name }}"><button class="btn btn-outline-success btn-sm rounded-pill" type="submit">Update</button></form>
                                    <form method="POST" action="{{ route('web.cart.remove', $item->product->product_id) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm rounded-circle" type="submit" aria-label="Remove {{ $item->product->product_name }}"><i class="bi bi-trash"></i></button></form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-4">
                    <aside class="cart-panel p-4 position-sticky" style="top:100px">
                        <h2 class="h5 fw-bold mb-3">Order summary</h2>
                        <div class="d-flex justify-content-between py-2"><span class="text-muted">Subtotal</span><strong>${{ number_format($subtotal, 2) }}</strong></div>
                        <div class="d-flex justify-content-between py-2 border-top"><span class="text-muted">Delivery</span><span class="small text-muted">Calculated at checkout</span></div>
                        <div class="d-flex justify-content-between pt-3 mt-2 border-top"><span class="fw-bold">Estimated total</span><span class="cart-total">${{ number_format($subtotal, 2) }}</span></div>
                        <p class="small text-muted mt-3"><i class="bi bi-shield-lock text-success me-1"></i>Secure payment through ABA KHQR. Your final delivery total is shown before payment.</p>
                        @if($items->contains(fn($item) => $item->stock_issue))
                            <button class="btn btn-secondary rounded-pill w-100 py-3" disabled>Review stock quantities</button>
                        @else
                            <a href="{{ route('web.checkout') }}" class="btn btn-success cart-checkout w-100">Continue to checkout <i class="bi bi-arrow-right ms-1"></i></a>
                        @endif
                    </aside>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
