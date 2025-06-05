<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = $_POST['nama']     ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $keahlian = $_POST['keahlian'] ?? '';
    $tarif    = $_POST['tarif']    ?? 0;
    $lokasi   = $_POST['lokasi']   ?? '';

    // Validasi wajib isi
    if (!$nama || !$username || !$password || !$keahlian || !$tarif || !$lokasi) {
        echo "Data tidak lengkap";
        exit;
    }

    // Cek apakah username sudah dipakai
    $stmt = $conn->prepare("SELECT id_mua FROM mua WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "Username sudah digunakan";
        exit;
    }
    $stmt->close();

    // PROSES UPLOAD FOTO
$foto_nama = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
    $tmp_name   = $_FILES['foto']['tmp_name'];
    $orig_name  = $_FILES['foto']['name'];
    $ext        = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    $allowed    = ['jpg', 'jpeg', 'png', 'gif'];

    // Cek apakah format foto didukung
    if (!in_array($ext, $allowed)) {
        echo "Format foto tidak didukung. Gunakan jpg, jpeg, png, atau gif.";
        exit;
    }

    // Buat nama file foto yang unik
    $foto_nama = uniqid('mua_') . '.' . $ext;
    $upload_dir = __DIR__ . '/../assets/'; // Pastikan path menuju folder assets benar
    $target_path = $upload_dir . $foto_nama;

    // Pastikan folder upload ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Pindahkan file dari tmp_name ke folder assets
    if (!move_uploaded_file($tmp_name, $target_path)) {
        echo "Gagal upload foto.";
        exit;
    }

    // Kirim URL foto ke frontend setelah berhasil upload
    $foto_url = "../assets/" . $foto_nama;  // Path foto yang bisa diakses di frontend

    // Kirim response sukses ke frontend
    echo json_encode([
        'status' => 'success',
        'foto_url' => $foto_url,
        'message' => 'Registrasi berhasil!'
    ]);
}

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Simpan ke DB
    $stmt = $conn->prepare("INSERT INTO mua 
        (nama, username, password, keahlian, tarif, lokasi, foto) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", 
        $nama, $username, $password_hash, $keahlian, $tarif, $lokasi, $foto_nama);

    if ($stmt->execute()) {
        // Redirect ke login setelah registrasi sukses
        header("Location: ../fe/login.html");
        exit; // Pastikan script berhenti setelah redirect
    } else {
        echo "Gagal registrasi: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Method tidak diizinkan";
}
?>
