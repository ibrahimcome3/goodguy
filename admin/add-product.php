<?php
// Start session and include the database connection.
// This makes the $pdo object available to this script and any included files.
session_start();
require_once '../includes.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">

<head>
    <?php include "admin-include.php"; // Contains meta tags, CSS links etc. ?>
</head>

<body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <main class="main" id="top">
        <?php include "admin-nav-top.php"; ?>
        <div class="content">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#!">Page 1</a></li>
                    <li class="breadcrumb-item"><a href="#!">Page 2</a></li>
                    <li class="breadcrumb-item active">Default</li>
                </ol>
            </nav>
            <form class="mb-9" method="post" action="product_adder.php" enctype="multipart/form-data">
                <div class="row g-3 flex-between-end mb-5">
                    <div class="col-auto">
                        <h2 class="mb-2">Add a product</h2>
                        <h5 class="text-body-tertiary fw-semibold">Orders placed across your store</h5>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-phoenix-secondary me-2 mb-2 mb-sm-0" type="button">Discard</button>
                        <button class="btn btn-phoenix-primary me-2 mb-2 mb-sm-0" type="button">Save draft</button>
                        <button class="btn btn-primary mb-2 mb-sm-0" type="submit">Publish product</button>
                    </div>
                </div>
                <div class="row g-5">
                    <div class="col-12 col-xl-8">
                        <h4 class="mb-3">Product Title</h4>
                        <input class="form-control mb-5" type="text" name="product_name"
                            placeholder="Write title here..." required>
                        <div class="mb-6">
                            <h4 class="mb-3">Product Description</h4>
                            <textarea class="tinymce" name="product_information"
                                data-tinymce="{&quot;height&quot;:&quot;15rem&quot;,&quot;placeholder&quot;:&quot;Write a description here...&quot;}"
                                id="mce_0"></textarea>
                        </div>
                        <h4 class="mb-3">Display images</h4>
                        <div class="mb-3">
                            <input type="file" id="productImageInput" name="product_image[]" accept="image/*"
                                class="form-control mb-2" required multiple>
                            <img id="productImagePreview" src="#" alt="Image Preview"
                                style="display:none; max-width:200px; max-height:200px;" />
                        </div>
                        <script>
                            document.getElementById('productImageInput').addEventListener('change', function (event) {
                                const [file] = event.target.files;
                                if (file) {
                                    const preview = document.getElementById('productImagePreview');
                                    preview.src = URL.createObjectURL(file);
                                    preview.style.display = 'block';
                                }
                            });
                        </script>
                        <!-- Dropzone can remain if you want drag-and-drop as well -->
                        <div class="dropzone dropzone-multiple p-0 mb-5 dz-clickable" id="my-awesome-dropzone"
                            data-dropzone="data-dropzone">

                            <div class="dz-preview d-flex flex-wrap"></div>
                            <div class="dz-message text-body-tertiary text-opacity-85"
                                data-dz-message="data-dz-message">Drag your photo here<span
                                    class="text-body-secondary px-1">or</span>
                                <button class="btn btn-link p-0" type="button">Browse from device</button><br><img
                                    class="mt-3 me-2" src="phoenix-v1.20.1/public/assets/img/icons/image-icon.png"
                                    width="40" alt="">
                            </div>
                        </div>

                    </div>
                    <?php include "organise.php" ?>
                </div>
            </form>
            <?php
            // Fetch 5 most recent products added by the current user (replace with your user ID logic)
            $currentUserId = $_SESSION['admin_id'] ?? null; // Make sure user_id is set in session
            
            if ($currentUserId) {
                $stmt = $pdo->prepare("SELECT pi.productID, pi.product_name, pi.date_added, img.image
                    FROM productitem pi
                    LEFT JOIN product_images img ON img.product_id = pi.productID
                    WHERE pi.vendor_id = ?
                    GROUP BY pi.productID
                    ORDER BY pi.date_added DESC
                    LIMIT 5");
                $stmt->execute([$currentUserId]);
                $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $recentProducts = [];
            }
            ?>

            <div class="card mt-5">
                <div class="card-header">
                    <h5>Your Recently Added Products</h5>
                </div>
                <div class="card-body">
                    <?php if ($recentProducts): ?>
                        <div class="d-flex flex-row flex-wrap gap-3">
                            <?php foreach ($recentProducts as $prod): ?>
                                <div class="border rounded p-2 d-flex align-items-center" style="min-width:220px;">
                                    <?php if (!empty($prod['image'])): ?>
                                        <img src="<?= htmlspecialchars('../products/product-' . $prod['productID'] . '/product-' . $prod['productID'] . '-image/' . $prod['image']) ?>"
                                            alt="<?= htmlspecialchars($prod['product_name']) ?>"
                                            style="width:60px;height:60px;object-fit:cover;margin-right:10px;">
                                    <?php else: ?>
                                        <div
                                            style="width:60px;height:60px;background:#eee;display:flex;align-items:center;justify-content:center;margin-right:10px;">
                                            <span class="text-muted">No Image</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div><strong><?= htmlspecialchars($prod['product_name']) ?></strong></div>
                                        <div class="text-muted" style="font-size:12px;">
                                            <?= htmlspecialchars($prod['date_added']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No products found for your account.</p>
                    <?php endif; ?>
                </div>
            </div>
            <footer class="footer position-absolute">
                <div class="row g-0 justify-content-between align-items-center h-100">
                    <div class="col-12 col-sm-auto text-center">
                        <p class="mb-0 mt-2 mt-sm-0 text-body">Thank you for creating with Phoenix<span
                                class="d-none d-sm-inline-block"></span><span
                                class="d-none d-sm-inline-block mx-1">|</span><br class="d-sm-none">2024 ©<a
                                class="mx-1" href="https://themewagon.com">Themewagon</a></p>
                    </div>
                    <div class="col-12 col-sm-auto text-center">
                        <p class="mb-0 text-body-tertiary text-opacity-85">v1.20.1</p>
                    </div>
                </div>
            </footer>
        </div>
    </main>
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/lodash/lodash.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/feather-icons/feather.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/dayjs/dayjs.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/tinymce/tinymce.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/dropzone/dropzone-min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/choices/choices.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/flatpickr/flatpickr.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>

</body>

</html>