@extends('layout.admin')

@section('content')

<style>
    .cat-page-bg {
        background: #f3f4f6;
        min-height: 100vh;
        padding: 24px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .cat-form-wrap {
        width: 100%;
        max-width: 720px;
    }

    .cat-form-topbar {
        margin-bottom: 20px;
    }

    .cat-form-title {
        font-size: 22px;
        font-weight: 700;
        color: #111827;
        text-align: center;
    }

    .cat-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
        padding: 25px;
    }

    .cat-form-alert {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .cat-form-alert ul {
        margin: 0;
        padding-left: 20px;
    }

    .cat-form-group {
        margin-bottom: 20px;
    }

    .cat-form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }

    .cat-form-control {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        transition: .2s;
    }

    .cat-form-control:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,.15);
    }

    textarea.cat-form-control {
        resize: vertical;
        min-height: 100px;
    }

    .cat-form-actions {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin-top: 25px;
    }

    .btn-cancel {
        background: #fff;
        border: 1px solid #d1d5db;
        color: #374151;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: .2s;
    }

    .btn-cancel:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .btn-update {
        background: #2563eb;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: .2s;
    }

    .btn-update:hover {
        background: #1d4ed8;
    }
</style>

<div class="cat-page-bg">

    <div class="cat-form-wrap">

        <div class="cat-form-topbar">
            <h4 class="cat-form-title">
                Edit Category
            </h4>
        </div>

        <div class="cat-card">

            @if ($errors->any())
                <div class="cat-form-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('categories.update', $category->category_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="cat-form-group">
                    <label class="cat-form-label">Category Name</label>
                    <input
                        type="text"
                        name="category_name"
                        class="cat-form-control"
                        value="{{ old('category_name', $category->category_name) }}"
                        required>
                </div>

                <div class="cat-form-group">
                    <label class="cat-form-label">Description</label>
                    <textarea
                        name="description"
                        class="cat-form-control"
                        rows="4">{{ old('description', $category->description) }}</textarea>
                </div>

                <div class="cat-form-actions">
                    <a href="{{ route('categories.index') }}" class="btn-cancel">
                        Cancel
                    </a>

                    <button type="submit" class="btn-update">
                        <i class="fa fa-save"></i> Update Category
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

@endsection