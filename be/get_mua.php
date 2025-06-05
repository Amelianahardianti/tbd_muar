<?php
include('koneksi.php');

// Cek koneksi database
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query untuk mengambil data MUA (termasuk tarif, lokasi, dan foto)
$sql = "SELECT id_mua, nama, keahlian, tarif, lokasi, foto FROM mua";
$result = $conn->query($sql);

$muaData = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $muaData[] = $row;
    }
}

// Kirimkan data dalam format JSON ke front-end
header('Content-Type: application/json');
echo json_encode($muaData);



// Menutup koneksi
$conn->close();
?>
