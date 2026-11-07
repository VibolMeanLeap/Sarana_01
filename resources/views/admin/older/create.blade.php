@extends('layout.admin')

@section('content')

<style>
    .ord-page-bg { background: #f3f4f6; min-height: 100vh; padding: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .ord-form-wrap { width: 100%; max-width: 800px; }
    .ord-form-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
    .ord-form-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0; }
    .btn-back-list { background: #fff; border: 1px solid #e5e7eb; color: #374151; padding: 9px 16px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background .15s ease; }
    .btn-back-list:hover { background: #f9fafb; color: #111827; }
    .ord-card { background: #fff; border-radius: 14px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 24px; }
    .ord-form-alert { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: 14px 16px; font-size: 13.5px; margin-bottom: 20px; }
    .ord-form-alert ul { margin: 0; padding-left: 18px; }
    .ord-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .ord-form-group { margin-bottom: 18px; }
    .ord-form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .ord-form-control { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #111827; background: #fff; }
    .ord-form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .ord-checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; }
    .ord-form-actions { display: flex; gap: 10px; margin-top: 24px; justify-content: center; }
    .btn-cancel { background: #fff; border: 1px solid #e5e7eb; color: #374151; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; }
    .btn-cancel:hover { background: #f9fafb; color: #111827; }
    .btn-save { background: #2563eb; border: none; color: #fff; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
    .btn-save:hover { background: #1d4ed8; }
    @media (max-width: 640px) { .ord-form-row { grid-template-columns: 1fr; } }
</style>

<div class="ord-page-bg">
    <div class="ord-form-wrap">

        <div class="ord-form-topbar">
            <h4 class="ord-form-title">Create New Order</h4>
            <a href="{{ route('admin.orders.index') }}" class="btn-back-list">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="ord-card">

            @if ($errors->any())
                <div class="ord-form-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.orders.store') }}" method="POST">
                @csrf

                <div class="ord-form-row">
                    <div class="ord-form-group">
                        <label for="customer_id" class="ord-form-label">Customer</label>
                        <select name="customer_id" id="customer_id" class="ord-form-control">
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->customer_id }}" @selected(old('customer_id') == $customer->customer_id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ord-form-group">
                        <label for="cashier_id" class="ord-form-label">Cashier</label>
                        <select name="cashier_id" id="cashier_id" class="ord-form-control">
                            <option value="">-- Select Cashier --</option>
                            @foreach($cashiers as $cashier)
                                <option value="{{ $cashier->user_id }}" @selected(old('cashier_id') == $cashier->user_id)>{{ $cashier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ord-form-row">
                    <div class="ord-form-group">
                        <label for="order_type_id" class="ord-form-label">Order Type</label>
                        <select name="order_type_id" id="order_type_id" class="ord-form-control">
                            <option value="">-- Select Order Type --</option>
                            @foreach($orderTypes as $type)
                                <option value="{{ $type->order_type_id }}" @selected(old('order_type_id') == $type->order_type_id)>{{ $type->order_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ord-form-group">
                        <label for="payment_method_id" class="ord-form-label">Payment Method</label>
                        <select name="payment_method_id" id="payment_method_id" class="ord-form-control">
                            <option value="">-- Select Payment Method --</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->payment_method_id }}" @selected(old('payment_method_id') == $method->payment_method_id)>{{ $method->payment_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ord-form-row">
                    <div class="ord-form-group">
                        <label for="waiting_num" class="ord-form-label">Waiting Number</label>
                        <input type="number" min="0" name="waiting_num" id="waiting_num" class="ord-form-control" value="{{ old('waiting_num') }}">
                    </div>

                    <div class="ord-form-group">
                        <label for="exchange_rate" class="ord-form-label">Exchange Rate</label>
                        <input type="number" step="0.01" name="exchange_rate" id="exchange_rate" class="ord-form-control" value="{{ old('exchange_rate') }}">
                    </div>
                </div>

                <div class="ord-form-row">
                    <div class="ord-form-group">
                        <label for="total_amount" class="ord-form-label">Total Amount</label>
                        <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" class="ord-form-control" value="{{ old('total_amount') }}">
                    </div>

                    <div class="ord-form-group">
                        <label for="discount" class="ord-form-label">Discount</label>
                        <input type="number" step="0.01" min="0" name="discount" id="discount" class="ord-form-control" value="{{ old('discount') }}">
                    </div>
                </div>

                <div class="ord-form-row">
                    <div class="ord-form-group">
                        <label for="final_price" class="ord-form-label">Final Price</label>
                        <input type="number" step="0.01" min="0" name="final_price" id="final_price" class="ord-form-control" value="{{ old('final_price') }}">
                    </div>

                    <div class="ord-form-group">
                        <label for="payment_status" class="ord-form-label">Payment Status</label>
                        <input type="text" name="payment_status" id="payment_status" class="ord-form-control" placeholder="e.g. Pending" value="{{ old('payment_status') }}">
                    </div>
                </div>

                <div class="ord-checkbox-row">
                    <input type="checkbox" name="is_paid" id="is_paid" value="1" @checked(old('is_paid'))>
                    <label for="is_paid" class="ord-form-label" style="margin:0;">Is Paid</label>
                </div>

                <div class="ord-form-actions">
                    <a href="{{ route('admin.orders.index') }}" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Save Order
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection