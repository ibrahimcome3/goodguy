<?php
session_start();
include "../conn.php";
require_once '../class/User.php';

$u = new User();

$customerId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : $_SESSION['uid']; // Get customer ID from URL or session

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_phone') {
        $phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
        $isDefault = isset($_POST['is_default']);
        //$u->resetDefaultPhoneNumber($mysqli, $customerId);

        if ($u->addPhoneNumber($mysqli, $customerId, $phoneNumber, $isDefault)) {
            header('Location: manage_phone_numbers.php?user_id=' . $customerId);
            exit;
        } else {
            echo "Error adding phone number.";
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'delete_phone') {
        $phoneId = filter_input(INPUT_POST, 'phone_id', FILTER_VALIDATE_INT);

        if ($u->deletePhoneNumber($mysqli, $phoneId)) {
            header('Location: manage_phone_numbers.php?user_id=' . $customerId);
            exit;
        } else {
            echo "Error deleting phone number.";
        }
    }
}

// Get phone numbers for the customer
$phoneNumbers = $u->getAllPhoneNumbers($mysqli, $customerId);

?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Phone Numbers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            color: #24292e;
        }

        .container {
            margin-top: 20px;
        }

        .github-form {
            background-color: #f6f8fa;
            border: 1px solid #e1e4e8;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .github-table {
            background-color: #fff;
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        .github-table th,
        .github-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .github-table th {
            background-color: #f6f8fa;
            font-weight: 600;
        }

        .github-table input[type="tel"],
        .github-table input[type="checkbox"] {
            margin-right: 5px;
        }

        .github-table button {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.875rem;
        }

        .github-table button.btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }

        .github-table button.btn-danger {
            background-color: #d73a4a;
            border-color: #d73a4a;
        }

        .github-table button:hover {
            opacity: 0.8;
        }

        .is-invalid {
            border-color: #dc3545;
        }
    </style>
</head>

<body>
    <?php include '../seller/navbar.php'; ?>
    <div class="container">
        <h1>Manage Phone Numbers</h1>
        <div class="github-form">
            <form method="post">
                <input type="hidden" name="action" value="add_phone">
                <div class="mb-3">
                    <label for="phone_number">Phone Number:</label>
                    <input type="tel" class="form-control phone-number-input" id="phone_number" name="phone_number"
                        required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                    <label class="form-check-label" for="is_default">Set as Default</label>
                </div>
                <button type="submit" class="btn btn-primary">Add Phone Number</button>
            </form>
        </div>

        <h2>Your Phone Numbers</h2>
        <form id="phoneNumbersForm">
            <table class="github-table">
                <thead>
                    <tr>
                        <th>Phone Number</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($phoneNumbers as $phoneNumber): ?>
                        <tr id="phone-row-<?= $phoneNumber['phone_id'] ?>">
                            <td>
                                <input type="tel" class="form-control phone-number-input" name="phone_number[]"
                                    value="<?= htmlspecialchars($phoneNumber['PhoneNumber']) ?>" required>
                                <input type="hidden" name="phone_id[]" value="<?= $phoneNumber['phone_id'] ?>">
                            </td>
                            <td>
                                <input type="checkbox" class="is_default-checkbox" name="is_default[]"
                                    value="<?= $phoneNumber['phone_id'] ?>" <?= $phoneNumber['default_'] ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm update-phone-button"
                                    data-phone-id="<?= $phoneNumber['phone_id'] ?>">Update</button>
                                <button type="button" class="btn btn-danger btn-sm delete-phone-button"
                                    data-phone-id="<?= $phoneNumber['phone_id'] ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Delete Phone Number (AJAX)
            $(".delete-phone-button").click(function (event) {
                event.preventDefault();
                let phoneId = $(this).data('phone-id');
                let deleteButton = $(this);
                $.ajax({
                    url: "process_manage_phone_numbers.php",
                    type: "POST",
                    data: {
                        action: "delete_phone",
                        phone_id: phoneId
                    },
                    dataType: "json",
                    beforeSend: function () {
                        deleteButton.prop('disabled', true).text('Deleting...');
                    },
                    success: function (response) {
                        if (response.success) {
                            $("#phone-row-" + phoneId).fadeOut('slow', function () {
                                $(this).remove();
                            });
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        alert("Error deleting phone number: " + error);
                    },
                    complete: function () {
                        deleteButton.prop('disabled', false).text('Delete');
                    }
                });
            });

            // Handle Update Phone Numbers via AJAX
            $('.update-phone-button').click(function (event) {
                event.preventDefault();
                let updateButton = $(this);
                let phoneRow = $(this).closest('tr');
                let phoneId = updateButton.data('phone-id');
                let phoneNumber = phoneRow.find('input[name="phone_number[]"]').val();
                // Collect all checked default phone IDs
                let isDefaultChecked = [];
                phoneRow.find('input[name="is_default[]"]:checked').each(function () {
                    isDefaultChecked.push($(this).val());
                });

                $.ajax({
                    url: 'process_manage_phone_numbers.php',
                    type: 'POST',
                    data: {
                        action: 'update_phone',
                        phone_id: phoneId,
                        phone_number: phoneNumber,
                        is_default: isDefaultChecked
                    },
                    dataType: "json",
                    beforeSend: function () {
                        updateButton.prop('disabled', true).text('Updating...');
                    },
                    success: function (response) {
                        if (response.success) {
                            alert("Phone number updated!");
                        } else {
                            alert("error updating");
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log("error:" + error);
                    },
                    complete: function () {
                        updateButton.prop('disabled', false).text('Update');
                    }
                });
            });

            // Enforce Single Default Checkbox
            $('.is_default-checkbox').change(function () {
                if ($(this).is(':checked')) {
                    $('.is_default-checkbox').not(this).prop('checked', false);
                }
            });

            //validate the input
            $('.phone-number-input').on('input', function () {
                if ($(this).val().trim() === '') {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
        });
    </script>
</body>

</html>