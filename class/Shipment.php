<?php
class Shipment
{
    private $pdo;
    private $shipping_area_table = "shipping_areas"; // Adjust table name if needed
    private $shipping_state_table = "shipping_state"; // Define state table name
    private $shipping_address_table = "shipping_address"; // Define shipping_address table

    private $allShipmentAreas = []; // Store fetched areas here
    private $allShipmentStates = []; // Store fetched states here

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Consider loading these on demand if they are large datasets
        $this->loadAllShipmentStates();
        $this->loadAllShipmentAreas(); // If you need all areas pre-loaded
    }

    public function get_shipping_area_by_state($stateId)
    {
        try {
            $sql = "SELECT area_id, area_name, area_cost
                    FROM shipping_areas
                    WHERE state_id = :state_id"; // Assuming you have a state_id column
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':state_id', $stateId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching shipping areas for state {$stateId}: " . $e->getMessage());
            return [];
        }
    }

    private function loadAllShipmentStates() // Renamed from set_shipment_area_state
    {
        try {
            $sql = "SELECT state_id, state_name FROM `{$this->shipping_state_table}` ORDER BY state_name ASC";
            $stmt = $this->pdo->query($sql); // Simple query, no user input
            $this->allShipmentStates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error loading all shipment states: " . $e->getMessage());
            $this->allShipmentStates = [];
        }
    }

    function get_shipment_state()
    {
        // Returns the pre-loaded array of states
        return $this->allShipmentStates;
    }

    private function loadAllShipmentAreas() // Renamed from set_shipment_area_cost
    {
        // This method might not be necessary if areas are always fetched by state.
        // If you do need all areas for some reason:
        try {
            // Assuming 'shipping_area' is the correct table name from $this->shipping_area_table
            $sql = "SELECT area_id, area_name, area_cost, state_id FROM `{$this->shipping_area_table}` ORDER BY area_name ASC";
            $stmt = $this->pdo->query($sql);
            $this->allShipmentAreas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error loading all shipment areas: " . $e->getMessage());
            $this->allShipmentAreas = [];
        }
    }

    function get_shipment_area()
    {
        // Returns the pre-loaded array of all areas
        // Note: This returns ALL areas, not filtered by state.
        // The `get_shipping_area_by_state` is usually more useful for dropdowns.
        return $this->allShipmentAreas;
    }

    /**
     * Gets the shipping cost associated with a specific shipping address.
     * This assumes the `ship_cost` column in `shipping_address` table stores the `area_id`.
     * @param int $shippingAddressNo The ID of the shipping address.
     * @return float|null The area cost or null if not found or an error occurs.
     */
    function get_shipping_cost($shippingAddressNo)
    {
        try {
            $sql = "SELECT sa.area_cost 
                    FROM `{$this->shipping_address_table}` sa_addr
                    JOIN `{$this->shipping_area_table}` sa ON sa_addr.ship_cost = sa.area_id 
                    WHERE sa_addr.shipping_address_no = :shipping_address_no";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':shipping_address_no', $shippingAddressNo, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (float) $row['area_cost'] : null;
        } catch (PDOException $e) {
            error_log("Error fetching shipping cost for address no {$shippingAddressNo}: " . $e->getMessage());
            return null;
        }
    }
}
?>