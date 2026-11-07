@extends('layout.admin')

@section('content')

<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Purchases</h5>

        <button type="button"
                class="btn btn-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#addPurchaseModal">
            <i class="fa fa-plus"></i> New Purchase
        </button>
    </div>

    <div class="card-body">

        {{-- Success Message --}}
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Error --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Search --}}
        <form method="GET" class="row g-2 mb-3">

            <div class="col-auto">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    class="form-control form-control-sm"
                    placeholder="Search product..."
                >
            </div>

            <div class="col-auto">
                <button class="btn btn-outline-secondary btn-sm">
                    Filter
                </button>
            </div>

        </form>


        {{-- Purchase Table --}}
        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Qty</th>
                        <th>Cost Price</th>
                        <th>Total</th>
                        <th>Purchased By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($purchases as $purchase)

                        <tr>

                            {{-- ID --}}
                            <td>
                                {{ $purchase->purchase_id }}
                            </td>


                            {{-- Product --}}
                            <td>
                                {{ $purchase->product->product_name ?? '-' }}
                            </td>


                            {{-- Supplier --}}
                            <td>
                                {{ $purchase->supplier->name ?? '-' }}
                            </td>


                            {{-- Quantity --}}
                            <td>
                                {{ $purchase->quantity }}
                            </td>


                            {{-- Cost Price --}}
                            <td>
                                ${{ number_format($purchase->cost_price, 2) }}
                            </td>


                            {{-- Total --}}
                            <td>
                                ${{ number_format($purchase->total_cost, 2) }}
                            </td>


                            {{-- Purchased By --}}
                            <td>
                                {{ $purchase->user->name ?? '-' }}
                            </td>


                            {{-- Purchase Date --}}
                            <td>
                                @if($purchase->purchase_date)
                                    {{ $purchase->purchase_date->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>


                            {{-- Action --}}
                            <td class="text-nowrap">

                                <a href="{{ route('purchase.edit', $purchase->purchase_id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>

                                <form
                                    action="{{ route('purchase.destroy', $purchase->purchase_id) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Delete this purchase?')">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="9"
                                class="text-center text-muted">
                                No purchases yet.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        {{ $purchases->links() }}

    </div>

</div>


{{-- ========================================================= --}}
{{-- ADD PURCHASE MODAL --}}
{{-- ========================================================= --}}

<div class="modal fade"
     id="addPurchaseModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog">

        <form
            action="{{ route('purchase.store') }}"
            method="POST"
            class="modal-content">

            @csrf


            {{-- Modal Header --}}
            <div class="modal-header">

                <h5 class="modal-title">
                    New Purchase
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            {{-- Modal Body --}}
            <div class="modal-body">


                {{-- Supplier --}}
                <div class="mb-3">

                    <label class="form-label">
                        Supplier
                    </label>

                    <select
                        name="supplier_id"
                        class="form-select"
                        required>

                        <option value="">
                            -- Select supplier --
                        </option>

                        @foreach($suppliers as $s)

                            <option
                                value="{{ $s->supplier_id }}"
                                {{ old('supplier_id') == $s->supplier_id ? 'selected' : '' }}>

                                {{ $s->name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Product --}}
                <div class="mb-3">

                    <label class="form-label">
                        Product
                    </label>

                    <select
                        name="product_id"
                        class="form-select"
                        required>

                        <option value="">
                            -- Select product --
                        </option>

                        @foreach($products as $p)

                            <option
                                value="{{ $p->product_id }}"
                                {{ old('product_id') == $p->product_id ? 'selected' : '' }}>

                                {{ $p->product_name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Quantity + Cost --}}
                <div class="row">

                    {{-- Quantity --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Quantity
                        </label>

                        <input
                            type="number"
                            name="quantity"
                            min="1"
                            value="{{ old('quantity') }}"
                            class="form-control"
                            required>

                    </div>


                    {{-- Cost Price --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Cost Price (per unit)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="cost_price"
                            value="{{ old('cost_price') }}"
                            class="form-control"
                            required>

                    </div>

                </div>


                {{-- Purchase Date --}}
                <div class="mb-3">

                    <label class="form-label">
                        Purchase Date
                    </label>

                    <input
                        type="date"
                        name="purchase_date"
                        value="{{ old('purchase_date', date('Y-m-d')) }}"
                        class="form-control"
                        required>

                </div>


            </div>


            {{-- Modal Footer --}}
            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">

                    Cancel

                </button>


                <button
                    type="submit"
                    class="btn btn-primary">

                    Save Purchase

                </button>

            </div>

        </form>

    </div>

</div>


@endsection