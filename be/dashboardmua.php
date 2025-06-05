<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['id_mua'])) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    header("Location: login.php");
    exit;
}

// Koneksi ke database
require_once 'koneksi.php';

$mua_id = $_SESSION['id_mua'];

try {
    // Ambil data profil MUA
    $queryProfile = "SELECT nama, username, foto FROM mua WHERE id_mua = ?";
    $stmt = $conn->prepare($queryProfile);
    $stmt->bind_param("i", $mua_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("MUA data not found");
    }
    
    $muaProfile = $result->fetch_assoc();
    $stmt->close();

 // Fix path foto untuk struktur folder be/fe/assets
    $foto = $muaProfile['foto'] ?? 'default.png';
    
    // Jika foto bukan URL lengkap, sesuaikan path
    if (!empty($foto) && !str_starts_with($foto, 'http')) {
        // Hapus prefix 'assets/' jika ada di database
        $foto = str_replace('assets/', '', $foto);
        
        // Buat path relatif dari fe/ ke assets/
        $foto = '../assets/' . $foto;
    }

    // Ambil data reservasi MUA
    $queryRes = "SELECT p.nama, p.kontak, l.tanggal_layanan, l.status, l.id_layanan
                FROM layanan l
                JOIN pelanggan p ON l.id_pelanggan = p.id_pelanggan
                WHERE l.id_mua = ?
                ORDER BY l.tanggal_layanan DESC";
    $stmtRes = $conn->prepare($queryRes);
    $stmtRes->bind_param("i", $mua_id);
    $stmtRes->execute();
    $resultRes = $stmtRes->get_result();


    // Convert result to array untuk JavaScript
    $reservations = [];
    while ($row = $resultRes->fetch_assoc()) {
        $reservations[] = [
            'id' => $row['id_layanan'],
            'nama_pemesan' => $row['nama'],
            'kontak' => $row['kontak'],
            'tanggal_layanan' => $row['tanggal_layanan'],
            'status' => $row['status']
        ];
    }
    $stmtRes->close();

    // Prepare data untuk frontend
    $muaData = [
        'id' => $mua_id,
        'nama' => $muaProfile['nama'],
        'username' => $muaProfile['username'],
        'foto' => $foto,
        'reservations' => $reservations
    ];

    // Output as JSON untuk AJAX request
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode($muaData);
        exit;
    }

} catch (Exception $e) {
    error_log("Dashboard MUA Error: " . $e->getMessage());
    
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    // Redirect ke login jika ada error
    header("Location: login.php");
    exit;
} finally {
    // Tutup koneksi
    if (isset($conn)) {
        $conn->close();
    }
}
?>