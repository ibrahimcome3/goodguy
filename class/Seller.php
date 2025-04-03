<?php
// class/seller.php
class Seller
{
    public function insertSeller($mysqli, $sellerData): mixed
    {
        if ($this->getSellerByUserId($mysqli, $sellerData['user_id'])) {
            return "duplicated";
        }


        $sql = "INSERT INTO seller (user_id, seller_name, seller_email, seller_phone, seller_address, seller_business_name, seller_description)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("issssss", $sellerData['user_id'], $sellerData['seller_name'], $sellerData['seller_email'], $sellerData['seller_phone'], $sellerData['seller_address'], $sellerData['seller_business_name'], $sellerData['seller_description']);
        if ($stmt->execute()) {
            return $mysqli->insert_id; // Return the ID of the inserted seller
        } else {
            return false; // Indicate failure
        }
    }


    // ... (Your other seller class methods) ...
    public function getSellerByUserId($mysqli, $userId)
    {
        $sql = "SELECT * FROM seller WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            // Handle prepare failure. This is crucial!
            error_log("Error in prepare: " . $mysqli->error);
            return false; // Or throw an exception
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    public function updateCustomer($mysqli, $customerdataData)
    {
        $sql = "UPDATE customer SET customer_name = ?, customer_phone = ?, customer_address1 = ?, customer_description = ? WHERE customer_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssi", $customerdataData['seller_name'], $customerdataData['seller_phone'], $customerdataData['seller_address'], $customerdataData['seller_description'], $customerdataData['user_id']);
        if ($stmt->execute()) {
            return true;
        } else {
            return false; // Indicate failure
        }
    }


    public function validatePost($key, $type, $required, $min, $max)
    {
        $value = isset($_POST[$key]) ? trim($_POST[$key]) : null;
        $result = ['key' => $key, 'value' => $value];

        if ($required && $value === null) {
            $result['error'] = ucfirst($key) . ' is required.';
            return $result;
        }

        if ($value !== null) {
            switch ($type) {
                case 'string':
                    if (!is_string($value) || strlen($value) < $min || strlen($value) > $max) {
                        $result['error'] = ucfirst($key) . ' must be a string between ' . $min . ' and ' . $max . ' characters long.';
                    }
                    break;
                case 'float':
                    if (!is_numeric($value) || (float) $value < $min || (float) $value > $max) {
                        $result['error'] = ucfirst($key) . ' must be a number between ' . $min . ' and ' . $max . '.';
                    } else {
                        $result['value'] = (float) $value;
                    }
                    break;
            }
        }

        return $result;
    }

    public function updateSeller($mysqli, $userId, $businessName, $description)
    {
        echo $businessName;
        echo $description;
        echo $userId;
        $sql = "UPDATE seller SET seller_business_name = ?, seller_description = ? WHERE seller_description = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssi", $businessName, $description, $userId);
        return $stmt->execute();
    }


}
?>