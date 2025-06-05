<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Cek method HTTP
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Ambil data MUA berdasarkan id_mua
    if (!isset($_GET['id_mua'])) {
        echo json_encode(['error' => 'Parameter id_mua tidak ditemukan']);
        exit;
    }

    $id_mua = intval($_GET['id_mua']);
    $sql = "SELECT id_mua, nama, keahlian, tarif, lokasi, foto FROM mua WHERE id_mua = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mua);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'MUA tidak ditemukan']);
        exit;
    }

    $mua = $result->fetch_assoc();
    echo json_encode($mua);

    $stmt->close();
    $conn->close();
    exit;
}

if ($method === 'POST') {
    // Terima data reservasi
    $id_mua = $_POST['id_mua'] ?? null;
    $nama = $_POST['nama'] ?? null;
    $kontak = $_POST['kontak'] ?? null;
    $alamat = $_POST['alamat'] ?? null;
    $tanggal_layanan = $_POST['tanggal_layanan'] ?? null;

    if (!$id_mua || !$nama || !$kontak || !$alamat || !$tanggal_layanan) {
        echo json_encode(['error' => 'Data reservasi tidak lengkap']);
        exit;
    }

    // Cek apakah pelanggan sudah ada berdasarkan nama dan kontak (nomor hp)
    $sqlCek = "SELECT id_pelanggan FROM pelanggan WHERE nama = ? AND kontak = ?";
    $stmtCek = $conn->prepare($sqlCek);
    $stmtCek->bind_param("ss", $nama, $kontak);
    $stmtCek->execute();
    $result = $stmtCek->get_result();

    if($result->num_rows > 0) {
        // Pelanggan sudah ada, ambil id_pelanggan
        $row = $result->fetch_assoc();
        $id_pelanggan = $row['id_pelanggan'];
    } else {
        // Insert data pelanggan baru
        $sql_pelanggan = "INSERT INTO pelanggan (nama, kontak, alamat) VALUES (?, ?, ?)";
        $stmt1 = $conn->prepare($sql_pelanggan);
        $stmt1->bind_param("sss", $nama, $kontak, $alamat);
        $stmt1->execute();

        if($stmt1->affected_rows > 0) {
            $id_pelanggan = $stmt1->insert_id;
        } else {
            echo json_encode(['error' => 'Gagal menyimpan data pelanggan']);
            exit;
        }
        $stmt1->close();
    }
    $stmtCek->close();

    // Insert data layanan (reservasi)
    $status_default = 'Menunggu Approve';
    $sql_layanan = "INSERT INTO layanan (id_mua, id_pelanggan, tanggal_layanan, status) VALUES (?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql_layanan);
    $stmt2->bind_param("iiss", $id_mua, $id_pelanggan, $tanggal_layanan, $status_default);
    $stmt2->execute();

if ($stmt2->affected_rows > 0) {
    echo "Reservasi berhasil, silakan tunggu MUA menghubungi Anda";
} else {
    echo "Reservasi gagal, silakan coba lagi";
}

    $stmt2->close();
    $conn->close();
    exit;
}

// Jika method selain GET dan POST
echo json_encode(['error' => 'Method tidak diizinkan']);
exit;
