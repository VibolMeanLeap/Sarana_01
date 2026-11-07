@extends('layout.web')
@section('web_title', 'Your cart')
@section('content')
<section class="py-5">
    <div class="container" style="max-width:960px">
        <div class="d-flex justify-content-between align-items-end gap-3 mb-4"><div><span class="text-success fw-bold small text-uppercase">Your selection</span><h1 class="fw-bold mb-1">Shopping cart</h1><p class="text-muted mb-0">Your active offers are already included in these prices.</p></div><a href="{{ route('web.shop') }}" class="btn btn-outline-secondary rounded-pill">Continue shopping</a></div>
        @include('admin.message.message')
        @if($items->isEmpty())
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center"><i class="bi bi-bag fs-1 text-success mb-3"></i><h2 class="h5 fw-bold">Your cart is empty</h2><p class="text-muted">Add some coffee or snacks to get started.</p><a href="{{ route('web.shop') }}" class="btn btn-success rounded-pill px-4 align-self-center">Browse menu</a></div>
        @else
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-3 p-md-4">
                @foreach($items as $item)
                    <div class="d-flex flex-wrap align-items-center gap-3 py-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-bold">{{ $item->product->product_name }}</div>
                            @if($item->offer)
                                <small class="text-muted"><del>${{ number_format($item->original_price, 2) }}</del> <span class="text-success fw-bold">${{ number_format($item->unit_price, 2) }} each</span> · {{ number_format((float)$item->offer->discount, 2) }}% offer</small>
                            @else
                                <small class="text-muted">${{ number_format($item->unit_price, 2) }} each</small>
                            @endif
                            <small class="text-muted d-block">{{ (int)$item->product->qty }} in stock</small>
                        </div>
                        <form method="POST" action="{{ route('web.cart.update', $item->product->product_id) }}" class="d-flex align-items-center gap-2">@csrf @method('PATCH')<input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ max(1, (int)$item->product->qty) }}" class="form-control form-control-sm" style="width:80px"><button class="btn btn-outline-success btn-sm" type="submit">Update</button></form>
                        <div class="text-end"><strong class="text-success">${{ number_format($item->line_total, 2) }}</strong>@if($item->line_saving > 0)<small class="d-block text-success">Saved ${{ number_format($item->line_saving, 2) }}</small>@endif</div>
                        <form method="POST" action="{{ route('web.cart.remove', $item->product->product_id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove item"><i class="bi bi-trash"></i></button></form>
                    </div>
                @endforeach
                <div class="d-flex justify-content-between align-items-center pt-4"><span class="fw-semibold">Subtotal</span><strong class="fs-4 text-success">${{ number_format($subtotal, 2) }}</strong></div>
                <div class="text-end mt-3"><a href="{{ route('web.checkout') }}" class="btn btn-success rounded-pill px-4">Continue to checkout <i class="bi bi-arrow-right ms-1"></i></a></div>
            </div></div>
        @endif
    </div>
</section>
@endsection
