@extends('layout.admin')
@section('content')
<div class="d-flex justify-content-center align-items-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="card-title">Add Supplier</div>
                <hr>
                <form id="fromCreateSuppier" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control form-control-rounded" id="name" name="name"
                            placeholder="Enter Your Name">
                        <p class="invalid-feedback m-0"></p>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control form-control-rounded" id="phone" name="phone"
                            placeholder="Enter Your Mobile Number">
                        <p class="invalid-feedback m-0"></p>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control form-control-rounded" id="email" name="email"
                            placeholder="Enter Your Email">
                        <p class="invalid-feedback m-0"></p>
                    </div>
                        <div class="form-group row">
                            <label class="col-sm-12 col-form-label">Address</label>
                            <div class="col-sm-12">
                               <select class="form-control" name="address" id="address">
                                <option value=""> Select Province </option>
                                <option value="Banteay Meanchey">Banteay Meanchey</option>
                                <option value="Battambang">Battambang</option>
                                <option value="Kampong Cham">Kampong Cham</option>
                                <option value="Kampong Chhnang">Kampong Chhnang</option>
                                <option value="Kampong Speu">Kampong Speu</option>
                                <option value="Kampong Thom">Kampong Thom</option>
                                <option value="Kampot">Kampot</option>
                                <option value="Kandal">Kandal</option>
                                <option value="Koh Kong">Koh Kong</option>
                                <option value="Kratie">Kratie</option>
                                <option value="Mondulkiri">Mondulkiri</option>
                                <option value="Oddar Meanchey">Oddar Meanchey</option>
                                <option value="Pailin">Pailin</option>
                                <option value="Phnom Penh">Phnom Penh</option>
                                <option value="Preah Vihear">Preah Vihear</option>
                                <option value="Preah Sihanouk">Preah Sihanouk</option>
                                <option value="Prey Veng">Prey Veng</option>
                                <option value="Pursat">Pursat</option>
                                <option value="Ratanakiri">Ratanakiri</option>
                                <option value="Siem Reap">Siem Reap</option>
                                <option value="sStung Trengt">Stung Treng</option>
                                <option value="Svay Rieng">Svay Rieng</option>
                                <option value="Takeo">Takeo</option>
                                <option value="Tboung Khmum">Tboung Khmum</option>
                                <option value="Kep">Kep</option>
                            </select>
                            </div>
                        </div>
                    <div class="">
                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); StoreSuppier(this)" id="submituser"
                            class="btn btn-primary btn-round px-5">
                            Create
                        </button>
                        <a href="{{ route('supplier.list') }}" class="btn btn-sm btn-light">Cancel</a>
                       
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const StoreSuppier = (buttonElement) => {
        let $form = $(buttonElement).closest('form');
        let payloads = new FormData($form[0]); 
        $(buttonElement).prop('disabled', true).text('Creating...');

        $.ajax({
            type: "POST",
            url: "{{ route('supplier.store') }}",
            data: payloads,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function (response) {
                $(buttonElement).prop('disabled', false).text('Create');
                $form.find('.form-control').removeClass('is-invalid');
                $form.find('.invalid-feedback').removeClass('text-danger').text('');
                if (response.status == 201 || response.status == 200) {
                    $form.trigger('reset');
                    window.location.href = "{{ route('supplier.list') }}";
                }
            },
            error: function (xhr) {
                $(buttonElement).prop('disabled', false).text('Create');
                $form.find('.form-control').removeClass('is-invalid');
                $form.find('.invalid-feedback').removeClass('text-danger').text('');

                if (xhr.status === 422 || (xhr.responseJSON && xhr.responseJSON.errors)) {
                    let errors = xhr.responseJSON.errors;
                    
                    $.each(errors, function (key, value) {
                        let inputField = $form.find(`[name="${key}"]`);
                        inputField.addClass('is-invalid');
                        inputField.siblings('.invalid-feedback').addClass('text-danger').text(value[0] || value);
                    });
                } else {
                   
                    alert('Server error (500). Please check your storage/logs/laravel.log file.');
                    console.error(xhr.responseText);
                }
            }
        });
    }
</script>
@endsection