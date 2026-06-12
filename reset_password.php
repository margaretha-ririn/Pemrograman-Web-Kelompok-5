<?php
session_start();
require 'config/db.php';

$message = '';
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token tidak valid.");
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("Token tidak valid atau sudah kedaluwarsa.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Password tidak sama!";
    } elseif (strlen($password) < 6) {
        $message = "Password minimal 6 karakter!";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE id = ?");
        $update->execute([$hash, $user['id']]);

        header("Location: login.php?reset=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - SkillNex</title>
</head>
<body style="font-family:Arial;background:#0f2239;display:flex;justify-content:center;align-items:center;height:100vh;">
    <div style="background:white;padding:35px;border-radius:15px;width:360px;">
        <h2>Reset Password</h2>

        <?php if ($message): ?>
            <p style="color:red;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Password Baru</label>
            <input type="password" name="password" required style="width:100%;padding:12px;margin:8px 0;">

            <label>Konfirmasi Password</label>
            <input type="password" name="confirm_password" required style="width:100%;padding:12px;margin:8px 0;">

            <button type="submit" style="width:100%;padding:12px;background:#667eea;color:white;border:none;border-radius:8px;">
                Simpan Password
            </button>
        </form>
    </div>
</body>
</html>