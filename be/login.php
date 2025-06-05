<?php
session_start();

// Error reporting untuk debugging (hapus di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include koneksi database
include 'koneksi.php';

// Function untuk redirect dengan pesan
function redirectWithMessage($location, $message, $type = 'error') {
    $param = $type === 'error' ? 'error' : 'success';
    header("Location: $location?$param=" . urlencode($message));
    exit;
}

// Cek apakah request method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('../fe/login.html', 'Method tidak diizinkan', 'error');
}

// Ambil data dari form
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input tidak boleh kosong
if (empty($username) || empty($password)) {
    redirectWithMessage('../fe/login.html', 'Username dan password wajib diisi', 'error');
}

// Cek koneksi database
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    redirectWithMessage('../fe/login.html', 'Terjadi kesalahan sistem', 'error');
}

try {
    // Prepared statement untuk mencari user
    $stmt = $conn->prepare("SELECT id_mua, username, password, nama, foto FROM mua WHERE username = ? LIMIT 1");
    
    if (!$stmt) {
        error_log("Prepare statement failed: " . $conn->error);
        redirectWithMessage('../fe/login.html', 'Terjadi kesalahan sistem', 'error');
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Cek apakah user ditemukan
    if ($result->num_rows === 0) {
        $stmt->close();
        redirectWithMessage('../fe/login.html', 'Username tidak ditemukan', 'error');
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verifikasi password
    $isPasswordValid = false;
    
    // Cek apakah password di database sudah di-hash
    if (password_get_info($user['password'])['algo'] !== null) {
        // Password sudah di-hash, gunakan password_verify
        $isPasswordValid = password_verify($password, $user['password']);
    } else {
        // Password masih plain text, bandingkan langsung
        $isPasswordValid = ($password === $user['password']);
        
        // Optional: Update password ke format hash untuk keamanan
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE mua SET password = ? WHERE id_mua = ?");
        $updateStmt->bind_param("si", $hashedPassword, $user['id_mua']);
        $updateStmt->execute();
        $updateStmt->close();
    }

    if (!$isPasswordValid) {
        redirectWithMessage('../fe/login.html', 'Password salah', 'error');
    }

    // Login berhasil - Set session
    $_SESSION['id_mua'] = $user['id_mua'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['foto'] = $user['foto'] ?? '../assets/default.png';
    $_SESSION['login_time'] = time();

 
    $query = "SELECT foto FROM mua WHERE id_mua = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['id_mua']); // id_mua dari session
    $stmt->execute();
    $stmt->bind_result($foto);
    $stmt->fetch();
    $stmt->close();

// Simpen path foto ke session
$_SESSION['foto'] = $foto ?? 'assets/default.png'; // Kalau nggak ada foto, pake default


    // Regenerate session ID untuk keamanan
    session_regenerate_id(true);

    // Log login activity (optional)
    error_log("User login successful: " . $user['username'] . " (ID: " . $user['id_mua'] . ")");

    // Redirect ke halaman redirect yang akan set localStorage
    header("Location: ../be/mua-redirect.php");
    exit;

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    redirectWithMessage('../fe/login.html', 'Terjadi kesalahan sistem', 'error');
} finally {
    // Tutup koneksi database
    if (isset($conn)) {
        $conn->close();
    }
}
?>