<?php
require_once 'koneksi.php';

$query = "SELECT 
            l.tanggal_layanan, 
            l.status, 
            m.nama AS nama_mua, 
            m.keahlian 
          FROM layanan l
          JOIN mua m ON l.id_mua = m.id_mua
          WHERE l.status IN ('Menunggu Approve', 'Disetujui', 'Sedang Dilakukan', 'Selesai')
          ORDER BY l.tanggal_layanan ASC";

$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>

