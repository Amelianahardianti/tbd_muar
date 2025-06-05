<?php
include('koneksi.php');

// Cek koneksi database
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Query untuk mengambil data layanan beserta nama pelanggan dan MUA
$sql = "SELECT 
            L.id_layanan, 
            P.nama AS pelanggan, 
            M.nama AS mua, 
            L.tanggal_layanan, 
            L.status
        FROM layanan L
        JOIN pelanggan P ON L.id_pelanggan = P.id_pelanggan
        JOIN mua M ON L.id_mua = M.id_mua
        ORDER BY L.tanggal_layanan DESC";

$result = $conn->query($sql);

$services = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Jika ingin format tanggal lebih mudah dibaca, bisa ubah di sini (opsional)
        $row['tanggal_layanan'] = date('Y-m-d', strtotime($row['tanggal_layanan']));
        $services[] = $row;
    }
}

// Kirim data JSON ke frontend
header('Content-Type: application/json');
echo json_encode($services);

// Tutup koneksi
$conn->close();
?>
