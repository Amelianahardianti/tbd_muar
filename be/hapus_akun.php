<?php
session_start();

// Set header untuk JSON response
header('Content-Type: application/json');

// Pastikan user sudah login sebagai MUA
if (!isset($_SESSION['id_mua'])) {
    echo json_encode(['success' => false, 'message' => 'User belum login.']);
    exit;
}

// Ambil dan decode input JSON
$input = json_decode(file_get_contents('php://input'), true);

// Cek action dan konfirmasi
if (isset($input['action']) && $input['action'] === 'delete_account' && 
    isset($input['confirm']) && $input['confirm'] === 'HAPUS') {
    
    // Ambil ID MUA dari session
    $mua_id = $_SESSION['id_mua'];

    // Koneksi ke database (sesuaikan dengan file koneksi yang sudah ada)
    require_once 'koneksi.php';

    try {
        // Mulai transaksi database
        $conn->begin_transaction();

        // 1. Dapatkan foto profil MUA sebelum menghapus
        $sqlFoto = "SELECT foto FROM mua WHERE id_mua = ?";
        $stmtFoto = $conn->prepare($sqlFoto);
        $stmtFoto->bind_param("i", $mua_id);
        $stmtFoto->execute();
        $resultFoto = $stmtFoto->get_result();
        $foto = null;
        
        if ($resultFoto->num_rows > 0) {
            $row = $resultFoto->fetch_assoc();
            $foto = $row['foto'];
        }
        $stmtFoto->close();

        // 2. Hapus semua layanan yang terkait dengan MUA ini
        // (Foreign key constraint CASCADE akan otomatis menghapus,
        // tapi kita eksplisit untuk memastikan)
        $sqlLayanan = "DELETE FROM layanan WHERE id_mua = ?";
        $stmtLayanan = $conn->prepare($sqlLayanan);
        $stmtLayanan->bind_param("i", $mua_id);
        $stmtLayanan->execute();
        $stmtLayanan->close();

        // 3. Hapus data MUA
        $sqlMua = "DELETE FROM mua WHERE id_mua = ?";
        $stmtMua = $conn->prepare($sqlMua);
        $stmtMua->bind_param("i", $mua_id);
        $stmtMua->execute();

        // Cek apakah penghapusan berhasil
        if ($stmtMua->affected_rows > 0) {
            // Commit transaksi
            $conn->commit();
            
            // Hapus foto profil dari server jika ada dan bukan default
            if ($foto && $foto !== 'default.png' && $foto !== '../assets/default.png') {
                // Sesuaikan path foto
                $fotoPath = str_replace('../assets/', '', $foto);
                $fullPath = "../assets/" . $fotoPath;
                
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            // Hapus session
            session_destroy();
            
            echo json_encode(['success' => true, 'message' => 'Akun berhasil dihapus!']);
        } else {
            // Rollback jika gagal
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus akun. Data tidak ditemukan.']);
        }
        
        $stmtMua->close();

    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        error_log("Delete Account Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
    } finally {
        // Tutup koneksi
        if (isset($conn)) {
            $conn->close();
        }
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi atau konfirmasi tidak valid.']);
}
?>