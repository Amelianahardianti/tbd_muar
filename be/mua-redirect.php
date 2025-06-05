<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['id_mua']) || !isset($_SESSION['nama'])) {
    header('Location: login.php');
    exit;
}

// Ambil data dari session
$nama = $_SESSION['nama'];
$foto = $_SESSION['foto'] ?? 'assets/default.png';
$username = $_SESSION['username'] ?? '';

if (!empty($foto) && !str_starts_with($foto, 'http')) {
    if (!str_starts_with($foto, 'assets/')) {
        $foto = './assets/' . $foto;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading Dashboard MUA...</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #faf8f8 0%, #f0e6ea 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .loading-container {
            text-align: center;
            color: #b79ea6;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #b79ea6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            color: #b79ea6;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        p {
            color: #6e5a53;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <h2>Loading Dashboard...</h2>
        <p>Selamat datang, <?= htmlspecialchars($nama) ?>!</p>
    </div>

    <script>
        // Set data MUA ke localStorage
        try {
            localStorage.setItem("muaName", <?= json_encode($nama, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
            localStorage.setItem("muaPhoto", <?= json_encode($foto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
            localStorage.setItem("muaUsername", <?= json_encode($username, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
            localStorage.setItem("muaId", <?= json_encode($_SESSION['id_mua']) ?>);
            
            // Debug log (bisa dihapus di production)
            console.log("Data MUA berhasil disimpan ke localStorage:");
            console.log("Nama:", localStorage.getItem("muaName"));
            console.log("Foto:", localStorage.getItem("muaPhoto"));
            console.log("Username:", localStorage.getItem("muaUsername"));
            
            // Redirect setelah 2 detik
            setTimeout(function() {
                window.location.href = "../fe/dashboardmua.html";
            }, 2000);
            
        } catch (error) {
            console.error("Error menyimpan data ke localStorage:", error);
            alert("Terjadi kesalahan. Silakan login ulang.");
            window.location.href = "../fe/login.html";
        }
    </script>
</body>
</html>