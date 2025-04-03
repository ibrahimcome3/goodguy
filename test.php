<!-- <?php

// Connects to the database
$conn = oci_connect('custom', 'custom321', '200.0.0.80:1521/hbldb');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$query = "SELECT * FROM FINFADM.SIGNCUSTINFO WHERE custid LIKE 'C0000161'";
$stid = oci_parse($conn, $query);
oci_execute($stid);

echo "<b>Query:</b> " . $query . "<br><br>"; // Display the query

echo "<table border='1'>\n";
echo "<tr>\n";
// Dynamically generate table headers based on the query results
$ncols = oci_num_fields($stid);
for ($i = 1; $i <= $ncols; $i++) {
    $column_name = oci_field_name($stid, $i);
    echo "<th>" . htmlentities($column_name, ENT_QUOTES) . "</th>";
}
echo "</tr>\n";


while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_LOBS)) {
    echo "<tr>\n";
    foreach ($row as $item) {
        if (is_object($item) && get_class($item) === 'OCI-Lob') {  // Actual BLOB
            $blob_data = $item->load(); // Use load() for CLOBs/BLOBs in this context
            $data_url = 'data:image/jpeg;base64,' . base64_encode($blob_data);
            echo "<td><img src='" . $data_url . "' alt='BLOB Image' /></td>\n";
        } elseif (is_string($item) && strlen($item) > 100) {  // Long string, treat as BLOB string
            $data_url = 'data:application/octet-stream;base64,' . base64_encode($item);  // Use a generic MIME type
            echo "<td><a href='" . $data_url . "' download='blob_data.bin'>Download BLOB</a></td>\n"; // Provide a download link
        } else {
            echo "<td>" . htmlentities($item, ENT_QUOTES) . "</td>\n"; // Regular data
        }
    }
    echo "</tr>\n";
}

echo "</table>\n";

oci_free_statement($stid);
oci_close($conn);
// ... (database connection code) ...

$query = "SELECT * FROM FINFADM.SIGNCUSTINFO WHERE custid LIKE 'C0000161'";
// ... (rest of your query execution code) ...
$stid = oci_parse($conn, $query);
oci_execute($stid);
while ($row = oci_fetch_array($stid, OCI_ASSOC)) {  // Don't use OCI_RETURN_LOBS here
    echo "<tr>\n";
    foreach ($row as $item) {
        // Assuming 'photo' is the column containing the string representation of the image
        if (isset($row['PHOTO'])) { // Check if mime type is available
            $mime_type = $row['PHOTO'];
        } else { // If not available, try to guess from the string (less reliable)
            $mime_type = guess_mime_type($item); //Implement or use a library
        }

        if ($mime_type && strpos($mime_type, 'image/') === 0) { // Check if it's likely an image
            $data_url = 'data:' . $mime_type . ';base64,' . $item; //String already base64 encoded?
            echo "<td><img src='" . $data_url . "' alt='Image'></td>\n";
        } else {
            echo "<td>" . htmlentities($item, ENT_QUOTES) . "</td>\n"; // Other data
        }
    }
    echo "</tr>\n";
}

// ... (close connection code) ...
oci_free_statement($stid);
oci_close($conn);


function guess_mime_type($data)
{
    // Implement logic to guess the MIME type if not stored in DB
    if (preg_match('/^iVBORw0KGgoAAAANSUhEUg/i', $data)) {
        return 'image/png';
    } elseif (preg_match('/^\/9j\/4/i', $data)) {
        return 'image/jpeg';
    } elseif (preg_match('/^GIF8/i', $data)) {
        return 'image/gif';
    } // Add other image type checks if needed
    return null; // Return null if no recognizable image format
}


// Close the connection


?> -->