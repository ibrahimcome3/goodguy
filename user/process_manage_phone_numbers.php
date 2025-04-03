<?php
session_start();
include "../conn.php";
require_once '../class/User.php';
$u = new User();

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_phone') {
        $phoneId = filter_input(INPUT_POST, 'phone_id', FILTER_VALIDATE_INT);
        $customerId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;

        if ($customerId === null) {
            echo json_encode(['success' => false, 'message' => 'Customer ID not found.']);
            exit;
        }

        // Check if the deleted number was the default
        if ($u->wasDefaultPhoneNumber($mysqli, $phoneId)) {
            // Reset all default flags for this customer.
            $u->resetDefaultPhoneNumber($mysqli, $customerId);

            // Then try to set a new default if there are other phone numbers
            if ($u->setNextPhoneNumberAsDefault($mysqli, $customerId)) {
                if ($u->deletePhoneNumber($mysqli, $phoneId)) {
                    echo json_encode(['success' => true, 'message' => 'Phone number deleted and next phone set as default.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Phone number deleted but not the next.']);
                }
            } else {
                if ($u->deletePhoneNumber($mysqli, $phoneId)) {
                    echo json_encode(['success' => true, 'message' => 'Phone number deleted. No other phone numbers to set as default.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting phone number.']);
                }
            }
        } else {
            if ($u->deletePhoneNumber($mysqli, $phoneId)) {
                echo json_encode(['success' => true, 'message' => 'Phone number deleted.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting phone number.']);
            }
        }
        exit;
    }
    if ($_POST['action'] === 'update_phone') {
        $phoneId = filter_input(INPUT_POST, 'phone_id', FILTER_VALIDATE_INT);
        $phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
        $customerId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;

        if ($customerId === null) {
            echo json_encode(['success' => false, 'message' => 'Customer ID not found.']);
            exit;
        }

        // Check if the current phone number is in the is_default array.
        $isDefault = isset($_POST['is_default']) && in_array($phoneId, $_POST['is_default']);

        // Get the current default status of the phone number.
        $wasDefault = $u->wasDefaultPhoneNumber($mysqli, $phoneId);

        if ($wasDefault && !$isDefault) {
            // If the number was default and is no longer set as default, reset all defaults and try to set a new one.
            $u->resetDefaultPhoneNumber($mysqli, $customerId);
            $u->setNextPhoneNumberAsDefault($mysqli, $customerId);
        } else if (!$wasDefault && $isDefault) {
            $u->resetDefaultPhoneNumber($mysqli, $customerId);
        }

        // Update the phone number.
        $isDefaultInt = $isDefault ? 1 : 0;

        if (!$u->updatePhoneNumber($mysqli, $phoneId, $phoneNumber, $isDefaultInt)) {
            echo json_encode(['success' => false, 'message' => 'Error updating phone number.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Phone number updated.']);
        exit;
    }
}
?>