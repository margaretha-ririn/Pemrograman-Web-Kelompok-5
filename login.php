<?php
session_start();
require 'config/db.php';

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Semua field wajib diisi!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_foto'] = $user['foto_profil'];
            header("Location: pages/dashboard.php");
            exit;
        } else {
            $error = "Email atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SkillNex</title>
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
        height: 100vh;
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
        margin-bottom: 30px;
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
        margin-bottom: 30px;
    }

    .input-group {
        margin-bottom: 20px;
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

    .password-row input::-ms-reveal,
    .password-row input::-ms-clear {
        display: none !important;
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
        display: none;
        background: #ffe5e5;
        color: #d32f2f;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
        border-left: 4px solid #d32f2f;
    }

    .error-message.show {
        display: block;
        animation: shake 0.4s;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }

    .options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .remember {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .forgot {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .forgot:hover {
        text-decoration: underline;
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
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    button:active {
        transform: translateY(0);
    }

    .signup-link {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: #666;
    }

    .signup-link a {
        color: #667eea;
        font-weight: 600;
        text-decoration: none;
    }

    .signup-link a:hover {
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
</style>
</head>
<body>

<div class="container">
    <div class="logo-section">
        <img src="assets/img/logoo.png" alt="SkillNex Logo" onerror="this.style.display='none'">
    </div>

    <h2>Welcome Back!</h2>
    <p class="subtitle">Login to continue to SkillNex</p>

    <!-- Pesan error dari PHP -->
    <?php if (!empty($error)): ?>
        <div class="error-message show">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- PERUBAHAN: form sekarang pakai method POST, diproses PHP -->
    <form method="POST" action="">

        <div class="input-group">
            <label>Email</label>
            <!-- PERUBAHAN: type="email", tambah name="email" -->
            <input type="email" name="email" placeholder="Enter your email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="input-group">
            <label>Password</label>
            <div class="password-row">
                <!-- PERUBAHAN: tambah name="password", tambah id untuk toggle JS -->
                <input type="password" name="password" id="password"
                       placeholder="Enter your password" required>
                <span class="password-icon" id="togglePass">👁️</span>
            </div>
        </div>

        <div class="options">
            <label class="remember">
                <input type="checkbox" name="remember">
                <span>Remember me</span>
            </label>
            <a href="forgot_password.php" class="forgot">Forgot Password?</a>
        </div>

        <button type="submit">Login</button>
    </form>

    <div class="signup-link">
        Don't have an account? <a href="register.php">Sign Up</a>
    </div>
</div>

<!-- Toggle show/hide password (tetap pakai JS, tidak masalah) -->
<script>
const eye = document.getElementById("togglePass");
const passInput = document.getElementById("password");

eye.addEventListener("click", () => {
    if (passInput.type === "password") {
        passInput.type = "text";
        eye.textContent = "🙈";
    } else {
        passInput.type = "password";
        eye.textContent = "👁️";
    }
});
</script>

</body>
</html>