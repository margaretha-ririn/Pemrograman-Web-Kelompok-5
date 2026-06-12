<?php
session_start();
require 'config/db.php';

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirm  = $_POST['konfirmasi'];

    if (empty($nama) || empty($email) || empty($password) || empty($konfirm)) {
        $error = "Semua field wajib diisi!";
    } elseif ($password !== $konfirm) {
        $error = "Password dan konfirmasi tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);

        if ($cek->rowCount() > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $email, $hash]);
            $success = "Akun berhasil dibuat! Silakan login.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - SkillNex</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Poppins", Arial, sans-serif;
        background: #0f2239;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        width: 100%;
        max-width: 420px;
        background: #ffffff;
        padding: 40px 35px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }

    .logo-section {
        text-align: center;
        margin-bottom: 20px;
    }

    .logo-section img {
        width: 60px;
        height: 60px;
        margin-bottom: 10px;
        object-fit: contain;
    }

    h2 {
        text-align: center;
        font-size: 28px;
        color: #333;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .subtitle {
        text-align: center;
        color: #666;
        font-size: 14px;
        margin-bottom: 24px;
    }

    .input-group {
        margin-bottom: 18px;
    }

    .input-group label {
        font-size: 14px;
        color: #333;
        font-weight: 600;
        display: block;
        margin-bottom: 8px;
    }

    .input-group input {
        width: 100%;
        padding: 14px 16px;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        font-size: 15px;
        outline: none;
        background: #f9f9f9;
        transition: all 0.3s;
    }

    .input-group input:focus {
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .password-row {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-row input {
        width: 100%;
        padding-right: 45px;
    }

    .password-icon {
        position: absolute;
        right: 15px;
        font-size: 20px;
        cursor: pointer;
        color: #666;
        transition: all 0.2s;
        user-select: none;
        z-index: 10;
    }

    .password-icon:hover {
        color: #667eea;
        transform: scale(1.1);
    }

    .error-message {
        background: #ffe5e5;
        color: #d32f2f;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
        border-left: 4px solid #d32f2f;
        animation: shake 0.4s;
    }

    .success-message {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
        border-left: 4px solid #2e7d32;
    }

    .success-message a {
        color: #2e7d32;
        font-weight: 600;
        text-decoration: underline;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }

    button {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        margin-top: 6px;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    button:active {
        transform: translateY(0);
    }

    .login-link {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: #666;
    }

    .login-link a {
        color: #667eea;
        font-weight: 600;
        text-decoration: none;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    @media (max-width: 480px) {
        .container {
            padding: 30px 25px;
        }
        h2 {
            font-size: 24px;
        }
    }
    /* Hilangkan icon mata bawaan browser */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
    display: none;
}

input[type="password"]::-webkit-credentials-auto-fill-button,
input[type="password"]::-webkit-strong-password-auto-fill-button {
    display: none !important;
}
</style>
</head>
<body>

<div class="container">
    <div class="logo-section">
        <img src="assets/img/logoo.png" alt="SkillNex Logo" onerror="this.style.display='none'">
    </div>

    <h2>Buat Akun</h2>
    <p class="subtitle">Bergabung dengan komunitas SkillNex 🚀</p>

    <!-- Pesan error dari PHP -->
    <?php if (!empty($error)): ?>
        <div class="error-message">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Pesan sukses dari PHP -->
    <?php if (!empty($success)): ?>
        <div class="success-message">
            ✅ <?= $success ?>
            <br><br>
            <a href="login.php">→ Klik di sini untuk login</a>
        </div>
    <?php endif; ?>

    <!-- Form hanya tampil kalau belum sukses -->
    <?php if (empty($success)): ?>
    <form method="POST" action="">

        <div class="input-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" placeholder="Masukkan nama lengkap" required
                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Masukkan email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="input-group">
            <label>Password</label>
            <div class="password-row">
                <input type="password" name="password" id="pass1"
                       placeholder="Minimal 6 karakter" required>
                <span class="password-icon" id="togglePass1">👁️</span>
            </div>
        </div>

        <div class="input-group">
            <label>Konfirmasi Password</label>
            <div class="password-row">
                <input type="password" name="konfirmasi" id="pass2"
                       placeholder="Ulangi password" required>
                <span class="password-icon" id="togglePass2">👁️</span>
            </div>
        </div>

        <button type="submit">Daftar Sekarang</button>
    </form>
    <?php endif; ?>

    <div class="login-link">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
</div>

<script>
// Toggle password 1
document.getElementById("togglePass1").addEventListener("click", function () {
    const input = document.getElementById("pass1");
    if (input.type === "password") {
        input.type = "text";
        this.textContent = "🙈";
    } else {
        input.type = "password";
        this.textContent = "👁️";
    }
});

// Toggle password 2
document.getElementById("togglePass2").addEventListener("click", function () {
    const input = document.getElementById("pass2");
    if (input.type === "password") {
        input.type = "text";
        this.textContent = "🙈";
    } else {
        input.type = "password";
        this.textContent = "👁️";
    }
});
</script>

</body>
</html>