<?php
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/Brand.php';

// Initialize Brand class
$b = new Brand();

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination functionality
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$perPage = 15; // Number of brands per page
$offset = ($page - 1) * $perPage;

//Get the total number of brands
$sql = "SELECT COUNT(*) AS totalBrands FROM brand";
$params = [];
$types = "";
if (!empty($searchTerm)) {
    $sql .= " WHERE Name LIKE ?";
    $params[] = "%{$searchTerm}%";
    $types .= "s";
}

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalBrands = $row['totalBrands'];
$totalPages = ceil($totalBrands / $perPage);

//Get the brands for the current page

$sql = "SELECT * FROM brand";
$params = [];
$types = "";
if (!empty($searchTerm)) {
    $sql .= " WHERE Name LIKE ?";
    $params[] = "%{$searchTerm}%";
    $types .= "s";
}
$sql .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$brands = [];

if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row;
    }
}

// Calculate the range of page numbers to display dynamically
$maxLinks = 5; // Maximum number of page links to display
$startPage = max(1, $page - floor($maxLinks / 2));
$endPage = min($totalPages, $page + floor($maxLinks / 2));

// Adjust start and end pages if they are near the edges
if ($endPage - $startPage + 1 < $maxLinks) {
    if ($startPage === 1) {
        $endPage = min($totalPages, $maxLinks);
    } else {
        $startPage = max(1, $totalPages - $maxLinks + 1);
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Brands</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .truncated {
            display: inline-block;
            max-width: 200px;
            /* Adjust as needed */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>
    <?php include "navbar.php"; ?>
    <div class="modal fade" id="brandDescriptionModal" tabindex="-1" aria-labelledby="brandDescriptionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="brandDescriptionModalLabel">Brand Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="brandDescriptionModalBody">
                    <!-- Full description will go here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-5">
        <h1>Manage Brands</h1>
        <div class="mb-3">
            <form method="GET" action="manage_brands.php">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" id="search" placeholder="Search Brands..."
                        value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>
        </div>
        <a href="add-brand.php" class="btn btn-primary mb-3">Add New Brand</a>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Brand Name</th>
                        <th>Brand description</th>
                        <th>Brand Logo</th>
                        <th>Web address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td><?= htmlspecialchars($brand['Name']) ?></td>
                            <td>
                                <span class="truncated" data-bs-toggle="modal" data-bs-target="#brandDescriptionModal"
                                    data-full-description="<?= htmlspecialchars($brand['brand_description']) ?>">
                                    <?= htmlspecialchars($brand['brand_description']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($brand['brand_logo'])): ?>
                                    <img src="../brand/<?= htmlspecialchars($brand['brand_logo']) ?>"
                                        alt="<?= htmlspecialchars($brand['Name']) ?> Logo"
                                        style="max-width: 100px; max-height: 50px;">
                                <?php endif; ?>
                            </td>
                            <td><span class="truncated"><?= htmlspecialchars($brand['websiteURL']) ?></span></td>
                            <td>
                                <a href="edit_brand.php?brand_id=<?= $brand['brandID'] ?>"
                                    class="btn btn-warning btn-sm">Edit</a>
                                <form method="post" action="process_delete_brand.php" style="display: inline;">
                                    <input type="hidden" name="brand_id" value="<?= $brand['brandID'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this brand?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <?php if ($totalBrands > $perPage): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>">Previous</a>
                    </li>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('brandDescriptionModal');
            modal.addEventListener('show.bs.modal', function (event) {
                var descriptionSpan = event.relatedTarget; // The .truncated element that was clicked
                var fullDescription = descriptionSpan.getAttribute('data-full-description');
                var modalBody = modal.querySelector('#brandDescriptionModalBody');
                modalBody.textContent = fullDescription;
            });
        });
    </script>
</body>

</html>