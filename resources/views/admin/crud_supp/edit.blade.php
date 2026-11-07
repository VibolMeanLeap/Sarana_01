@extends('layout.admin')
@section('content')
<div class="d-flex justify-content-center align-items-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="card-title">Update Supplier</div>
                <hr>
                <form action="{{ route('supplier.update' , $supplier->supplier_id) }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control form-control-rounded" id="name" name="name" value="{{ old('name', $supplier->name) }}">
                        <p class="invalid-feedback m-0"></p>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control form-control-rounded" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
                        <p class="invalid-feedback m-0"></p>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control form-control-rounded" id="email" name="email" value="{{ old('email', $supplier->email) }}">
                        <p class="invalid-feedback m-0"></p>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-12 col-form-label">Address</label>
                        @php
                        $provinces = [
                        "Banteay Meanchey", "Battambang", "Kampong Cham", "Kampong Chhnang",
                        "Kampong Speu", "Kampong Thom", "Kampot", "Kandal", "Koh Kong",
                        "Kratie", "Mondulkiri", "Oddar Meanchey", "Pailin", "Phnom Penh",
                        "Preah Vihear", "Preah Sihanouk", "Prey Veng", "Pursat", "Ratanakiri",
                        "Siem Reap", "Stung Treng", "Svay Rieng", "Takeo", "Tboung Khmum", "Kep"
                        ];
                        @endphp
                        
                        <div class="col-sm-12">
                            <select class="form-control" name="address" id="address">
                                <option value=""> Select Province </option>
                                @foreach($provinces as $province)
                                <option value="{{ $province }}" {{ (isset($supplier) && $supplier->address == $province) ? 'selected' : '' }}>
                                    {{ $province }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="">
                        <a href="{{ route('supplier.list') }}" class="btn btn-sm btn-light">Cancel</a>
                        <button type="submit" class="btn btn-sm btn-primary">Update</button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
