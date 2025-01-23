<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Document</title>
</head>

<body>
    <div class="border-y border-translucent" id="productWishlistTable"
        data-list="{&quot;valueNames&quot;:[&quot;products&quot;,&quot;color&quot;,&quot;size&quot;,&quot;price&quot;,&quot;quantity&quot;,&quot;total&quot;],&quot;page&quot;:5,&quot;pagination&quot;:true}">
        <div class="table-responsive scrollbar">
            <table class="table fs-9 mb-0 table-sm">
                <thead>
                    <tr>
                        <th class="sort" scope="col" style="width:7%;"></th>
                        <th class="sort" scope="col" style="width:30%; min-width:250px;" data-sort="products">PRODUCTS
                        </th>
                        <th class="sort" scope="col" data-sort="color" style="width:16%;">COLOR</th>
                        <th class="sort" scope="col" data-sort="size" style="width:10%;">SIZE</th>
                        <th class="sort" scope="col" data-sort="price" style="width:10%;">PRICE
                        </th>
                        <th class="sort align-middle text-end pe-0" scope="col" style="width:35%;"> </th>
                    </tr>
                </thead>
                <tbody class="list" id="profile-wishlist-table-body">
                    <tr class="hover-actions-trigger btn-reveal-trigger position-static">
                        <td class=""><a class="border border-translucent rounded-2 d-inline-block"
                                href="../../../apps/e-commerce/landing/product-details.html"><img
                                    src="../../../assets/img//products/1.png" alt="" width="53"></a></td>
                        <td class="products"><a class="fw-semibold mb-0 line-clamp-1"
                                href="../../../apps/e-commerce/landing/product-details.html">Fitbit Sense Advanced
                                Smartwatch with Tools for Heart Health, Stress Management &amp; Skin Temperature Trends,
                                Carbon/Graphite, One Size (S &amp; L Bands)</a></td>
                        <td class="color">Pure matte black</td>
                        <td class="size">42</td>
                        <td class="price">$57</td>
                        <td class="total">
                            <button class="btn btn-sm text-body-quaternary text-body-tertiary-hover me-2"><svg
                                    class="svg-inline--fa fa-trash" aria-hidden="true" focusable="false"
                                    data-prefix="fas" data-icon="trash" role="img" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 448 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z">
                                    </path>
                                </svg><!-- <span class="fas fa-trash"></span> Font Awesome fontawesome.com --></button>
                            <button class="btn btn-primary fs-10"><svg
                                    class="svg-inline--fa fa-cart-shopping me-1 fs-10" aria-hidden="true"
                                    focusable="false" data-prefix="fas" data-icon="cart-shopping" role="img"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" data-fa-i2svg="">
                                    <path fill="currentColor"
                                        d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z">
                                    </path>
                                </svg><!-- <span class="fas fa-shopping-cart me-1 fs-10"></span> Font Awesome fontawesome.com -->Add
                                to cart</button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
            <div class="col-auto d-flex">
                <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body" data-list-info="data-list-info">1 to 5
                    <span class="text-body-tertiary"> Items of </span>9
                </p><a class="fw-semibold" href="#!" data-list-view="*">View all<svg
                        class="svg-inline--fa fa-angle-right ms-1" data-fa-transform="down-1" aria-hidden="true"
                        focusable="false" data-prefix="fas" data-icon="angle-right" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" data-fa-i2svg=""
                        style="transform-origin: 0.3125em 0.5625em;">
                        <g transform="translate(160 256)">
                            <g transform="translate(0, 32)  scale(1, 1)  rotate(0 0 0)">
                                <path fill="currentColor"
                                    d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"
                                    transform="translate(-160 -256)"></path>
                            </g>
                        </g>
                    </svg><!-- <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span> Font Awesome fontawesome.com --></a><a
                    class="fw-semibold d-none" href="#!" data-list-view="less">View Less<svg
                        class="svg-inline--fa fa-angle-right ms-1" data-fa-transform="down-1" aria-hidden="true"
                        focusable="false" data-prefix="fas" data-icon="angle-right" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" data-fa-i2svg=""
                        style="transform-origin: 0.3125em 0.5625em;">
                        <g transform="translate(160 256)">
                            <g transform="translate(0, 32)  scale(1, 1)  rotate(0 0 0)">
                                <path fill="currentColor"
                                    d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"
                                    transform="translate(-160 -256)"></path>
                            </g>
                        </g>
                    </svg><!-- <span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span> Font Awesome fontawesome.com --></a>
            </div>
            <div class="col-auto d-flex">
                <button class="page-link disabled" data-list-pagination="prev" disabled=""><svg
                        class="svg-inline--fa fa-chevron-left" aria-hidden="true" focusable="false" data-prefix="fas"
                        data-icon="chevron-left" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"
                        data-fa-i2svg="">
                        <path fill="currentColor"
                            d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z">
                        </path>
                    </svg><!-- <span class="fas fa-chevron-left"></span> Font Awesome fontawesome.com --></button>
                <ul class="mb-0 pagination">
                    <li class="active"><button class="page" type="button" data-i="1" data-page="5">1</button></li>
                    <li><button class="page" type="button" data-i="2" data-page="5">2</button></li>
                </ul>
                <button class="page-link pe-0" data-list-pagination="next"><svg class="svg-inline--fa fa-chevron-right"
                        aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-right" role="img"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" data-fa-i2svg="">
                        <path fill="currentColor"
                            d="M310.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L242.7 256 73.4 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z">
                        </path>
                    </svg><!-- <span class="fas fa-chevron-right"></span> Font Awesome fontawesome.com --></button>
            </div>
        </div>
    </div>
</body>

</html>