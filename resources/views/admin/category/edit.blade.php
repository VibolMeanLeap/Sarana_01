@extends('layout.admin')
@section('page_title', 'Edit category')
@section('topbar_title', 'Edit category')
@section('content')

<style>
.cc-form-page{--g:#176b4b;--gd:#104d38;--gs:#e9f4ee;background:linear-gradient(180deg,#edf5ef 0,#f5f8f5 100%);border-radius:22px;padding:clamp(16px,2.4vw,30px);min-height:calc(100vh - 140px)}
.cc-form-wrap{max-width:780px;margin:0 auto}.cc-form-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:20px}.cc-form-heading h1{font-size:27px;font-weight:800;letter-spacing:-.04em;color:#1d3529;margin:3px 0}.cc-form-heading p{margin:0;color:#748178;font-size:13px}.cc-form-card{background:#fff;border:1px solid #dfeae2;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px #204b3010}.cc-form-card-head{padding:20px 24px;background:linear-gradient(100deg,#e6f2e9,#f7fbf7);border-bottom:1px solid #dce9df;display:flex;align-items:center;gap:13px}.cc-form-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:14px;background:#cfe8d5;color:#176b4b;font-size:20px}.cc-form-card-head h2{font-size:16px;font-weight:800;color:#214b35;margin:0}.cc-form-card-head p{font-size:12px;color:#6d8575;margin:3px 0 0}.cc-form-body{padding:24px}.cc-form-page .form-label{font-size:12px;font-weight:800;color:#496553;margin-bottom:7px}.cc-form-page .form-control{border:1px solid #d7e4da;border-radius:11px;min-height:44px;font-size:13px;box-shadow:none}.cc-form-page textarea.form-control{min-height:120px}.cc-form-page .form-control:focus{border-color:#64a77b;box-shadow:0 0 0 3px #176b4b18}.cc-form-footer{display:flex;justify-content:flex-end;gap:9px;flex-wrap:wrap;padding:16px 24px;background:#f2f8f3;border-top:1px solid #e0ece3}.cc-form-save{border:0;border-radius:11px;padding:10px 18px;background:#176b4b;color:#fff;font-weight:800}.cc-form-save:hover{background:#104d38}.cc-form-cancel{border:1px solid #d5e2d8;border-radius:11px;padding:10px 18px;background:#fff;color:#476450;text-decoration:none;font-weight:700}.cc-form-error{background:#fff1f1;border:1px solid #f0cccc;border-radius:12px;padding:12px 15px;color:#a63f3f;font-size:13px;margin:15px 24px 0}
</style>

<div class="cc-form-page"><div class="cc-form-wrap">
    <div class="cc-form-heading"><div><div class="small text-success fw-bold text-uppercase" style="letter-spacing:.14em">Catalogue setup</div><h1>Edit category</h1><p>Update the category details while keeping linked products organised.</p></div><a href="{{ route('categories.index') }}" class="cc-form-cancel"><i class="bi bi-arrow-left me-1"></i> Back to categories</a></div>
    <section class="cc-form-card"><div class="cc-form-card-head"><span class="cc-form-icon"><i class="bi bi-pencil-square"></i></span><div><h2>{{ $category->category_name }}</h2><p>Category ID #{{ $category->category_id }}</p></div></div>
        @if($errors->any())<div class="cc-form-error"><strong>Please check the form:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form action="{{ route('categories.update', $category->category_id) }}" method="POST">@csrf @method('PUT')
            <div class="cc-form-body"><div class="mb-3"><label for="category_name" class="form-label">CATEGORY NAME <span class="text-danger">*</span></label><input id="category_name" type="text" name="category_name" class="form-control @error('category_name') is-invalid @enderror" value="{{ old('category_name', $category->category_name) }}" maxlength="255" required>@error('category_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div><label for="description" class="form-label">DESCRIPTION <span class="text-muted fw-normal">(OPTIONAL)</span></label><textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" maxlength="2000" placeholder="Describe this category..." data-optional>{{ old('description', $category->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
            <div class="cc-form-footer"><a href="{{ route('categories.index') }}" class="cc-form-cancel">Cancel</a><button type="submit" class="cc-form-save"><i class="bi bi-check2-circle me-1"></i> Save changes</button></div>
        </form>
    </section>
</div></div>
@endsection
