<?php
session_start();

// Cek apakah user sudah login sebagai MUA
if (!isset($_SESSION['id_mua'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Koneksi ke database
require_once 'koneksi.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);

// Validasi input
if (!isset($input['order_id']) || !isset($input['new_status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$order_id = (int)$input['order_id'];
$new_status = $input['new_status'];
$mua_id = $_SESSION['id_mua'];

// Daftar status yang valid sesuai dengan enum di database
$valid_statuses = ['Menunggu Approve', 'Disetujui', 'Sedang Dilakukan', 'Selesai', 'Dibatalkan', 'Ditolak'];

// Validasi status
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Cek apakah layanan ini milik MUA yang sedang login
    $checkQuery = "SELECT id_layanan FROM layanan WHERE id_layanan = ? AND id_mua = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $order_id, $mua_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized or order not found']);
        exit;
    }
    $checkStmt->close();
    
    // Update status
    $updateQuery = "UPDATE layanan SET status = ? WHERE id_layanan = ? AND id_mua = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sii", $new_status, $order_id, $mua_id);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Status berhasil diupdate',
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
    $updateStmt->close();
    
} catch (Exception $e) {
    error_log("Update Status Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>