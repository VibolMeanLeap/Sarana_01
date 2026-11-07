@extends('layout.web')

@section('content')
<section class="hero-clean text-center">
    <div class="container">
        <span class="badge rounded-pill px-3 py-2 mb-3"
            style="background-color: var(--light-green-bg); color: var(--primary-green); font-weight: 600;">Fresh &
            Natural Everyday</span>
        <h1 class="display-4 fw-bold mb-3">Pure Flavors, Elevated.</h1>
        <p class="text-muted mx-auto fs-5 mb-0" style="max-width: 600px;">Explore our curated selection of organic
            dark coffees, handcrafted refreshers, pure mountain waters, and fresh pastries.</p>
    </div>
</section>

<section id="menu" class="py-5">
    <div class="container">

        <!-- Category Selection Tabs Integrated Formats -->
        <ul class="nav nav-pills category-pills justify-content-center mb-5" id="menuTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button"
                    role="tab" onclick="filterCategory('all')">All Items</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="coffee-tab" data-bs-toggle="pill" data-bs-target="#coffee" type="button"
                    role="tab" onclick="filterCategory('coffee')"><i class="bi bi-cup-hot me-1"></i>
                    Coffee</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="drinks-tab" data-bs-toggle="pill" data-bs-target="#drinks" type="button"
                    role="tab" onclick="filterCategory('drinks')"><i class="bi bi-cup-straw me-1"></i>
                    Specialty Drinks</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="water-tab" data-bs-toggle="pill" data-bs-target="#water" type="button"
                    role="tab" onclick="filterCategory('water')"><i class="bi bi-droplet me-1"></i> Pure
                    Water</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bakery-tab" data-bs-toggle="pill" data-bs-target="#bakery" type="button"
                    role="tab" onclick="filterCategory('bakery')"><i class="bi bi-egg-fried me-1"></i>
                    Bakery & Food</button>
            </li>
        </ul>

        <!-- Product Items Grid Container Target -->
        <div class="row g-4" id="menu-container">
            <!-- Rendered via JavaScript -->
        </div>

    </div>
</section>
<!-- Offcanvas Shopping Cart -->
<div class="offcanvas offcanvas-end offcanvas-clean" tabindex="-1" id="cartOffcanvas"
    aria-labelledby="cartOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold text-dark" id="cartOffcanvasLabel">
            <i class="bi bi-bag me-2" style="color: var(--primary-green);"></i>Your Order
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column">

        <!-- Dynamic Cart Items Content Grid Scroll Target -->
        <div id="cart-list" class="overflow-auto pe-1 flex-grow-1">
            <!-- Rendered dynamically straight out of internal arrays layout variables -->
        </div>

        <!-- Operational Fulfillment Rules Calculation Layout Form Panel Blocks -->
        <div class="border-top pt-3 mt-3">

            <!-- Radio Option Picker: Delivery vs Pick Up Selection Control Structure Nodes -->
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted mb-2 text-uppercase tracking-wider">Fulfillment
                    Method</label>
                <div class="btn-group w-100" role="group"
                    aria-label="Order fulfillment selection tab control framework">
                    <input type="radio" class="btn-check" name="fulfillmentMethod" id="method-delivery" checked
                        onclick="changeFulfillment('delivery')">
                    <label class="btn btn-outline-fulfillment w-50 py-2 fs-7 fw-semibold rounded-start-3"
                        for="method-delivery">
                        <i class="bi bi-truck me-2"></i>Delivery
                    </label>

                    <input type="radio" class="btn-check" name="fulfillmentMethod" id="method-pickup"
                        onclick="changeFulfillment('pickup')">
                    <label class="btn btn-outline-fulfillment w-50 py-2 fs-7 fw-semibold rounded-end-3"
                        for="method-pickup">
                        <i class="bi bi-shop me-2"></i>Pick Up
                    </label>
                </div>
            </div>

            <!-- Context Information blocks swapping flags programmatically -->
            <div id="delivery-info-block" class="bg-light p-3 rounded-3 mb-3 small text-muted border">
                <i class="bi bi-geo-alt-fill me-1 text-danger"></i> Dispatching order directly to your saved standard
                address.
            </div>
            <div id="pickup-info-block" class="bg-light p-3 rounded-3 mb-3 small text-muted border d-none">
                <i class="bi bi-building me-1 text-primary"></i> Ready for pickup within 15 mins at our <strong>Main
                    Street Branch</strong>.
            </div>

            <!-- Dynamic Math Data Breakdown Invoicing Row Container Stack layout grids -->
            <div class="d-flex justify-content-between text-muted small mb-2">
                <span>Subtotal</span>
                <span id="cart-subtotal">$0.00</span>
            </div>
            <div class="d-flex justify-content-between text-muted small mb-2">
                <span>Discount (10%)</span>
                <span id="cart-discount">-$0.00</span>
            </div>
            <!-- Dynamic conditional conditional row hook variable target items -->
            <div class="d-flex justify-content-between text-muted small mb-2" id="fee-row">
                <span>Delivery Fee</span>
                <span id="cart-fee">$5.00</span>
            </div>
            <div class="d-flex justify-content-between fs-5 fw-bold text-dark mb-3">
                <span>Total</span>
                <span id="cart-total" style="color: var(--primary-green);">$0.00</span>
            </div>

            <!-- Action Confirmation Form Execution Switch Button context nodes -->
            <button id="checkout-btn" class="btn btn-green w-100 py-3 rounded-3 fw-bold shadow-sm" disabled
                onclick="checkout()">
                Continue to checkout
            </button>
        </div>

    </div>
</div>
@endsection