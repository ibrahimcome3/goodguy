<div class="row g-0 border-top border-bottom">
    <div class="col-sm-4">
        <div class="nav flex-sm-column border-bottom border-bottom-sm-0 border-end-sm fs-9 vertical-tab h-100 justify-content-between"
            role="tablist" aria-orientation="vertical"><a
                class="nav-link border-end border-end-sm-0 border-bottom-sm text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center"
                id="restockTab" data-bs-toggle="tab" data-bs-target="#restockTabContent" role="tab"
                aria-controls="restockTabContent" aria-selected="false" tabindex="-1"> <svg
                    xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="feather feather-package me-sm-2 fs-4 nav-icons">
                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg><span class="d-none d-sm-inline">Restock</span></a><a
                class="nav-link border-end border-end-sm-0 border-bottom-sm text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center"
                id="shippingTab" data-bs-toggle="tab" data-bs-target="#shippingTabContent" role="tab"
                aria-controls="shippingTabContent" aria-selected="false" tabindex="-1"> <svg
                    xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="feather feather-truck me-sm-2 fs-4 nav-icons">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg><span class="d-none d-sm-inline">Shipping</span></a>
            <a class="nav-link border-end border-end-sm-0 border-bottom-sm text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center"
                id="attributesTab" data-bs-toggle="tab" data-bs-target="#attributesTabContent" role="tab"
                aria-controls="attributesTabContent" aria-selected="false" tabindex="-1"> <svg
                    xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="feather feather-sliders me-sm-2 fs-4 nav-icons">
                    <line x1="4" y1="21" x2="4" y2="14"></line>
                    <line x1="4" y1="10" x2="4" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12" y2="3"></line>
                    <line x1="20" y1="21" x2="20" y2="16"></line>
                    <line x1="20" y1="12" x2="20" y2="3"></line>
                    <line x1="1" y1="14" x2="7" y2="14"></line>
                    <line x1="9" y1="8" x2="15" y2="8"></line>
                    <line x1="17" y1="16" x2="23" y2="16"></line>
                </svg><span class="d-none d-sm-inline">Attributes</span></a>
            <a class="nav-link text-center text-sm-start cursor-pointer outline-none d-sm-flex align-items-sm-center"
                id="advancedTab" data-bs-toggle="tab" data-bs-target="#advancedTabContent" role="tab"
                aria-controls="advancedTabContent" aria-selected="false" tabindex="-1"> <svg
                    xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="feather feather-lock me-sm-2 fs-4 nav-icons">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg><span class="d-none d-sm-inline">Advanced</span></a>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="tab-content py-3 ps-sm-4 h-100">

            <div class="tab-pane fade h-100" id="restockTabContent" role="tabpanel" aria-labelledby="restockTab">
                <div class="d-flex flex-column h-100">
                    <h5 class="mb-3 text-body-highlight">Add to Stock</h5>
                    <div class="row g-3 flex-1 mb-4">
                        <div class="col-sm-7">
                            <input class="form-control" type="number" placeholder="Quantity">
                        </div>
                        <div class="col-sm">
                            <button class="btn btn-primary" type="button"><svg
                                    class="svg-inline--fa fa-check me-1 fs-10" aria-hidden="true" focusable="false"
                                    data-prefix="fas" data-icon="check" role="img" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 448 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z">
                                    </path>
                                </svg><!-- <span class="fa-solid fa-check me-1 fs-10"></span> Font Awesome fontawesome.com -->Confirm</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 200px;"></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-body-highlight fw-bold py-1">Product in stock
                                    now:</td>
                                <td class="text-body-tertiary fw-semibold py-1">$1,090
                                    <button class="btn p-0" type="button"><svg
                                            class="svg-inline--fa fa-rotate text-body ms-1"
                                            style="--phoenix-text-opacity: .6;" aria-hidden="true" focusable="false"
                                            data-prefix="fas" data-icon="rotate" role="img"
                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                                            <path fill="currentColor"
                                                d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z">
                                            </path>
                                        </svg><!-- <span class="fa-solid fa-rotate text-body ms-1" style="--phoenix-text-opacity: .6;"></span> Font Awesome fontawesome.com --></button>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-body-highlight fw-bold py-1">Product in transit:
                                </td>
                                <td class="text-body-tertiary fw-semibold py-1">5000</td>
                            </tr>
                            <tr>
                                <td class="text-body-highlight fw-bold py-1">Last time
                                    restocked:</td>
                                <td class="text-body-tertiary fw-semibold py-1">30th June, 2021
                                </td>
                            </tr>
                            <tr>
                                <td class="text-body-highlight fw-bold py-1">Total stock over
                                    lifetime:</td>
                                <td class="text-body-tertiary fw-semibold py-1">20,000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade h-100" id="shippingTabContent" role="tabpanel" aria-labelledby="shippingTab">
                <div class="d-flex flex-column h-100">
                    <h5 class="mb-3 text-body-highlight">Shipping Type</h5>
                    <div class="flex-1">
                        <div class="mb-4">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="shippingRadio"
                                    id="fullfilledBySeller">
                                <label class="form-check-label fs-8 text-body" for="fullfilledBySeller">Fullfilled by
                                    Seller</label>
                            </div>
                            <div class="ps-4">
                                <p class="text-body-secondary fs-9 mb-0">You’ll be responsible
                                    for product delivery. <br>Any damage or delay during
                                    shipping may cost you a Damage fee.</p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="shippingRadio"
                                    id="fullfilledByPhoenix" checked="checked">
                                <label class="form-check-label fs-8 text-body d-flex align-items-center"
                                    for="fullfilledByPhoenix">Fullfilled by Phoenix <span
                                        class="badge badge-phoenix badge-phoenix-warning fs-10 ms-2">Recommended</span></label>
                            </div>
                            <div class="ps-4">
                                <p class="text-body-secondary fs-9 mb-0">Your product, Our
                                    responsibility.<br>For a measly fee, we will handle the
                                    delivery process for you.</p>
                            </div>
                        </div>
                    </div>
                    <p class="fs-9 fw-semibold mb-0">See our <a class="fw-bold" href="#!">Delivery terms and conditions
                        </a>for details.</p>
                </div>
            </div>

            <div class="tab-pane fade" id="attributesTabContent" role="tabpanel" aria-labelledby="attributesTab">
                <h5 class="mb-3 text-body-highlight">Attributes</h5>
                <div class="form-check">
                    <input class="form-check-input" id="fragileCheck" type="checkbox">
                    <label class="form-check-label text-body fs-8" for="fragileCheck">Fragile
                        Product</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" id="biodegradableCheck" type="checkbox">
                    <label class="form-check-label text-body fs-8" for="biodegradableCheck">Biodegradable</label>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" id="frozenCheck" type="checkbox" checked="checked">
                        <label class="form-check-label text-body fs-8" for="frozenCheck">Frozen
                            Product</label>
                        <input class="form-control" type="text" placeholder="Max. allowed Temperature"
                            style="max-width: 350px;">
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" id="productCheck" type="checkbox" checked="checked">
                    <label class="form-check-label text-body fs-8" for="productCheck">Expiry
                        Date of Product</label>
                    <input class="form-control inventory-attributes datetimepicker flatpickr-input" id="inventory"
                        type="text" style="max-width: 350px;" placeholder="d/m/y"
                        data-options="{&quot;disableMobile&quot;:true}" readonly="readonly">
                </div>
            </div>
            <div class="tab-pane fade" id="advancedTabContent" role="tabpanel" aria-labelledby="advancedTab">
                <h5 class="mb-3 text-body-highlight">Advanced</h5>
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <h5 class="mb-2 text-body-highlight">Product ID Type</h5>
                        <select class="form-select" aria-label="form-select-lg example">
                            <option selected="selected">ISBN</option>
                            <option value="1">UPC</option>
                            <option value="2">EAN</option>
                            <option value="3">JAN</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6">
                        <h5 class="mb-2 text-body-highlight">Product ID</h5>
                        <input class="form-control" type="text" placeholder="ISBN Number">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>