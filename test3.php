<?php
// $conn = oci_connect('custom', 'custom321', '200.0.0.80:1521/hbldb');
// if (!$conn) {
//     $e = oci_error();
//     trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
// }


// $sql = "SELECT PHOTO FROM FINFADM.SIGNCUSTINFO WHERE custid LIKE 'C0000161'";
// $stid = oci_parse($conn, $sql);
// oci_execute($stid);
// $row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);
// if (!$row) {
//     header('Status: 404 Not Found');
// } else {
//     $photoData = "787E4CCFB84D8F5078300CF846A31A576E714F680DB9A483198ECE83E0967C9F4E08A417069CAF19
// 657FDE24AC7D8CE8BE7CD9FD8E9EFA7F29A556CA5693C8CD0D7B1D9B4EB20BB1D823A6A14A260700";

//     //$photoData = $photoData->read($photoData->size());
//     $img = base64_decode($photoData);
//     header("Content-type: image/png");
//     print $img;
//     header("Content-type: image/jpeg");
//     print $img;
// }




// Connects to the database
// $conn = oci_connect('custom', 'custom321', '200.0.0.80:1521/hbldb');
// if (!$conn) {
//     $e = oci_error();
//     trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
// }

// // SQL query to retrieve table names (replace 'YOUR_SCHEMA' with your actual schema name if not 'custom')
// $stid = oci_parse($conn, "SELECT table_name FROM user_tables"); // For tables in your schema
// //$stid = oci_parse($conn, "SELECT table_name FROM all_tables WHERE owner = 'YOUR_SCHEMA'"); // More specific

// oci_execute($stid);

// echo "<table border='1'>\n";
// echo "<tr><th>Table Name</th></tr>\n"; // Header row

// while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
//     echo "<tr>\n";
//     echo "    <td>" . htmlentities($row['TABLE_NAME'], ENT_QUOTES) . "</td>\n";
//     echo "</tr>\n";
// }
// echo "</table>\n";

// // Close the connection (good practice)
// oci_free_statement($stid);
// oci_close($conn);


$conn = oci_connect('custom', 'custom321', '200.0.0.80:1521/hbldb');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Query to get all tables in the user's schema
$tableQuery = "SELECT table_name FROM user_tables";
$tableStid = oci_parse($conn, $tableQuery);
oci_execute($tableStid);

echo "<h2>Tables with BLOB columns:</h2>";

while ($tableRow = oci_fetch_array($tableStid, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $tableName = $tableRow['TABLE_NAME'];

    // Query to get column information for the current table
    $columnQuery = "SELECT column_name, data_type
                    FROM user_tab_columns
                    WHERE table_name = :table_name
                    AND data_type LIKE '%blob%'"; // Filter for BLOB columns


    $columnStid = oci_parse($conn, $columnQuery);
    oci_bind_by_name($columnStid, ":table_name", $tableName);
    oci_execute($columnStid);


    $hasBlob = false;
    $blobColumns = [];
    while ($columnRow = oci_fetch_array($columnStid, OCI_ASSOC + OCI_RETURN_NULLS)) {
        $hasBlob = true;
        $blobColumns[] = $columnRow['COLUMN_NAME'];
    }


    oci_free_statement($columnStid);


    if ($hasBlob) {
        echo "<b>Table:</b> " . $tableName . "<br>";
        echo "<b>BLOB Columns:</b> " . implode(", ", $blobColumns) . "<br><br>";
    }
}

oci_free_statement($tableStid);
oci_close($conn);

?>