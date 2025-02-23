<?php

$conn = oci_connect('custom', 'custom321', '200.0.0.80:1521/hbldb');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$query = "SELECT PHOTO, PHOTOTHUMB FROM FINFADM.SIGNCUSTINFO WHERE custid LIKE 'C0000161'";
$stid = oci_parse($conn, $query);
oci_execute($stid);

// while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_LOBS)) {
//     // Photo
//     $photoData = isset($row['PHOTO']) ? $row['PHOTO'] : null; // Initialize
//     if ($photoData) {                              // Check for null photo data
//         $photoMimeType = 'image/jpg'; // Get or determine MIME type

//         if (is_object($photoData) && get_class($photoData) === 'OCI-Lob') {
//             $photoData = $row['PHOTO']->read($row['PHOTO']->size()); // Use read() if it is a LOB
//         } elseif (is_string($photoData)) {
//             // Handle string data directly  (no further action needed in this case)
//         } else {
//             echo "Unexpected data type for PHOTO<br>";
//             continue; // skip to next row

//         }

//         // Now use the $photoData string to construct Data URL
//         echo "<img src='data:" . $photoMimeType . ";base64," . base64_encode($photoData) . "' alt='Photo'><br>";

//     } else {
//         echo "No Photo<br>";
//     }
// }

while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_LOBS)) {
    $photoData = "iVBORw0KGgoAAAANSUhEUgAAAM0AAAD
 NCAMAAAAsYgRbAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5c
 cllPAAAABJQTFRF3NSmzMewPxIG//ncJEJsldTou1jHgAAAARBJREFUeNrs2EEK
 gCAQBVDLuv+V20dENbMY831wKz4Y/VHb/5RGQ0NDQ0NDQ0NDQ0NDQ0NDQ
 0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0PzMWtyaGhoaGhoaGhoaGhoaGhoxtb0QGho
 aGhoaGhoaGhoaGhoaMbRLEvv50VTQ9OTQ5OpyZ01GpM2g0bfmDQaL7S+ofFC6x
 v3ZpxJiywakzbvd9r3RWPS9I2+MWk0+kbf0Hih9Y17U0nTHibrDDQ0NDQ0NDQ0
 NDQ0NDQ0NTXbRSL/AK72o6GhoaGhoRlL8951vwsNDQ0NDQ1NDc0WyHtDTEhD
 Q0NDQ0NTS5MdGhoaGhoaGhoaGhoaGhoaGhoaGhoaGposzSHAAErMwwQ2HwRQ
 AAAAAElFTkSuQmCC";
    if ($photoData) {

        $photoData = $photoData->read($photoData->size());  // Correct way to read BLOB data

        // Debugging - Check raw and base64 encoded data
        // var_dump($photoData);                 // Check raw BLOB data (comment out after debugging)
        // var_dump(base64_encode($photoData)); // Check base64 encoded data (comment out after debugging)

        $photoMimeType = 'image/png'; // ***REPLACE with actual MIME type if not JPEG***
        $dataUrl = 'data:' . $photoMimeType . ';base64,' . $photoData;
        echo "<img src='" . $dataUrl . "' alt='Photo'><br>";


    } else {
        echo "No Photo<br>";
    }
}

oci_free_statement($stid);
oci_close($conn);

echo '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAM0AAAD
 NCAMAAAAsYgRbAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5c
 cllPAAAABJQTFRF3NSmzMewPxIG//ncJEJsldTou1jHgAAAARBJREFUeNrs2EEK
 gCAQBVDLuv+V20dENbMY831wKz4Y/VHb/5RGQ0NDQ0NDQ0NDQ0NDQ0NDQ
 0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0PzMWtyaGhoaGhoaGhoaGhoaGhoxtb0QGho
 aGhoaGhoaGhoaGhoaMbRLEvv50VTQ9OTQ5OpyZ01GpM2g0bfmDQaL7S+ofFC6x
 v3ZpxJiywakzbvd9r3RWPS9I2+MWk0+kbf0Hih9Y17U0nTHibrDDQ0NDQ0NDQ0
 NDQ0NDQ0NTXbRSL/AK72o6GhoaGhoRlL8951vwsNDQ0NDQ1NDc0WyHtDTEhD
 Q0NDQ0NTS5MdGhoaGhoaGhoaGhoaGhoaGhoaGhoaGposzSHAAErMwwQ2HwRQ
 AAAAAElFTkSuQmCC" alt="beastie.png" /> ';
?>