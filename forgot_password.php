<?php
session_start();
require 'config/db.php';

$message = '';
$messageType = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Email wajib diisi!";
        $messageType = "error";
    } else {
        // Cek apakah email terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate token unik
            $token = bin2hex(random_bytes(32));
            // Token kedaluwarsa dalam 1 jam
            $expireDate = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // Simpan token ke database
            $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE id = ?");
            if ($updateStmt->execute([$token, $expireDate, $user['id']])) {
                $message = "Permintaan reset password berhasil diproses!";
                $messageType = "success";
                
                // Buat link reset
                $resetUrl = "reset_password.php?token=" . urlencode($token);
                // Untuk simulasi lokal, kita tampilkan langsung link-nya
                $resetLink = "<div style='margin-top: 20px; padding: 15px; background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px; text-align: center;'>
                                <strong>Simulasi Email Reset:</strong><br>
                                <a href='{$resetUrl}' style='color: #2e7d32; font-weight: bold; text-decoration: underline;'>Klik di sini untuk mereset password kamu</a>
                              </div>";
            } else {
                $message = "Terjadi kesalahan sistem. Silakan coba lagi.";
                $messageType = "error";
            }
        } else {
            $message = "Email tidak ditemukan di sistem kami!";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - SkillNex</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "Poppins", Arial, sans-serif;
        background: #0f2239;
        display: flex; justify-content: center; align-items: center;
        height: 100vh; padding: 20px;
    }
    .container {
        width: 100%; max-width: 420px; background: #ffffff;
        padding: 40px 35px; border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }
    h2 { text-align: center; color: #333; margin-bottom: 10px; }
    .subtitle { text-align: center; color: #666; font-size: 14px; margin-bottom: 30px; }
    .input-group { margin-bottom: 20px; }
    .input-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #333; }
    .input-group input { width: 100%; padding: 14px 16px; border-radius: 10px; border: 2px solid #e0e0e0; outline: none; transition: 0.3s; }
    .input-group input:focus { border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
    
    .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; }
    .alert.error { background: #ffe5e5; color: #d32f2f; border: 1px solid #ffcdd2; }
    .alert.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }

    button {
        width: 100%; padding: 15px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; font-weight: 600; cursor: pointer; transition: 0.3s;
    }
    button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
    
    .back-link { text-align: center; margin-top: 20px; }
    .back-link a { color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px; }
    .back-link a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="container">
    <h2>Forgot Password</h2>
    <p class="subtitle">Enter your email to receive a reset link</p>

    <?php if ($message): ?>
        <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your registered email" required>
        </div>
        <button type="submit">Send Reset Link</button>
    </form>
    
    <?php if ($resetLink): ?>
        <?= $resetLink ?>
    <?php endif; ?>

    <div class="back-link">
        <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>
