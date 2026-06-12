<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil id post dari URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($post_id === 0) {
    header("Location: forum.php");
    exit;
}

// Ambil data post
$stmt = $pdo->prepare("
    SELECT fp.*, u.nama, u.foto_profil
    FROM forum_posts fp
    JOIN users u ON fp.user_id = u.id
    WHERE fp.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: forum.php");
    exit;
}

$success = '';
$error   = '';

// === KIRIM KOMENTAR ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komentar'])) {
    $komentar = trim($_POST['komentar']);
    if (empty($komentar)) {
        $error = "Komentar tidak boleh kosong!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO forum_comments (post_id, user_id, komentar) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $komentar]);
        $success = "Komentar berhasil dikirim!";
    }
}

// === HAPUS KOMENTAR (hanya pemilik) ===
if (isset($_GET['hapus_komentar'])) {
    $kom_id = (int)$_GET['hapus_komentar'];
    $cek = $pdo->prepare("SELECT user_id FROM forum_comments WHERE id = ?");
    $cek->execute([$kom_id]);
    $kom = $cek->fetch();

    if ($kom && $kom['user_id'] == $user_id) {
        $pdo->prepare("DELETE FROM forum_comments WHERE id = ?")->execute([$kom_id]);
    }
    header("Location: forum_detail.php?id=$post_id");
    exit;
}

// === AMBIL SEMUA KOMENTAR ===
$stmt = $pdo->prepare("
    SELECT fc.*, u.nama, u.foto_profil
    FROM forum_comments fc
    JOIN users u ON fc.user_id = u.id
    WHERE fc.post_id = ?
    ORDER BY fc.created_at ASC
");
$stmt->execute([$post_id]);
$komentar_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forum - <?= htmlspecialchars($post['judul']) ?></title>
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/forum.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <style>
    html,
    body,
    .container {
        background: #ffffff !important;
    }

    .container {
        background: #ffffff !important;
    }

    .section-title {
        color: #111827 !important;
    }
    .alert-success {
        background: #1a4731; color: #6fcf97;
        padding: 14px 18px; border-radius: 10px;
        margin-bottom: 20px; border-left: 4px solid #27ae60; font-size: 14px;
    }
    .alert-error {
        background: #4a1515; color: #eb5757;
        padding: 14px 18px; border-radius: 10px;
        margin-bottom: 20px; border-left: 4px solid #e53e3e; font-size: 14px;
    }
    .btn-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: #5b9cf5; text-decoration: none; font-size: 14px;
        font-weight: 600; margin-bottom: 20px; transition: all 0.3s;
    }
    .btn-back:hover { color: #fff; }
    .post-detail-card {
        background: #0f2239; border: 1px solid #2d5a8e;
        border-radius: 16px; padding: 28px; margin-bottom: 28px;
    }
    .post-detail-kategori {
        display: inline-block; background: #1e3a5f;
        color: #5b9cf5; padding: 4px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 600; margin-bottom: 12px;
        border: 1px solid #2d5a8e;
    }
    .post-detail-judul {
        font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 16px;
    }
    .post-detail-konten {
        font-size: 15px; color: #b8c5d6; line-height: 1.8; margin-bottom: 20px;
    }
    .post-detail-meta {
        display: flex; align-items: center; gap: 10px;
        padding-top: 16px; border-top: 1px solid #1e3a5f;
    }
    .post-detail-meta img {
        width: 40px; height: 40px; border-radius: 50%;
        object-fit: cover; border: 2px solid #2d5a8e;
    }
    .post-detail-meta-name { font-size: 14px; font-weight: 600; color: #fff; }
    .post-detail-meta-date { font-size: 12px; color: #7a8ea8; }

    .section-title {
        font-size: 18px; font-weight: 700; color: #fff;
        margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }

    /* Komentar */
    .komentar-card {
        background: #0f2239; border: 1px solid #1e3a5f;
        border-radius: 12px; padding: 18px; margin-bottom: 14px;
        transition: all 0.3s;
    }
    .komentar-card:hover { border-color: #2d5a8e; }
    .komentar-header {
        display: flex; align-items: center;
        justify-content: space-between; margin-bottom: 12px;
    }
    .komentar-user {
        display: flex; align-items: center; gap: 10px;
    }
    .komentar-user img {
        width: 36px; height: 36px; border-radius: 50%;
        object-fit: cover; border: 2px solid #2d5a8e;
    }
    .komentar-user-name { font-size: 14px; font-weight: 600; color: #fff; }
    .komentar-user-date { font-size: 12px; color: #7a8ea8; }
    .komentar-text {
        font-size: 14px; color: #b8c5d6; line-height: 1.7;
    }
    .btn-hapus-kom {
        background: none; border: 1px solid #e53e3e;
        color: #e53e3e; padding: 5px 12px; border-radius: 8px;
        font-size: 12px; cursor: pointer; font-family: inherit;
        transition: all 0.3s; white-space: nowrap;
    }
    .btn-hapus-kom:hover { background: #e53e3e; color: white; }

    /* Form komentar baru */
    .form-komentar {
        background: #0f2239; border: 1px solid #2d5a8e;
        border-radius: 16px; padding: 24px; margin-top: 24px;
    }
    .form-komentar h3 {
        font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 16px;
    }
    .form-textarea {
        width: 100%; padding: 14px 16px;
        border: 2px solid #2d5a8e; border-radius: 10px;
        font-size: 14px; font-family: inherit;
        background: #1e3a5f; color: #fff;
        resize: vertical; min-height: 100px; transition: all 0.3s;
    }
    .form-textarea:focus { outline: none; border-color: #5b9cf5; }
    .btn-kirim {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white; border: none; padding: 12px 28px;
        border-radius: 10px; font-size: 14px; font-weight: 600;
        cursor: pointer; font-family: inherit; margin-top: 12px;
        transition: all 0.3s;
    }
    .btn-kirim:hover { transform: translateY(-2px); opacity: 0.9; }
    .empty-komentar {
        text-align: center; padding: 40px 20px; color: #7a8ea8;
        background: #0f2239; border: 1px dashed #2d5a8e;
        border-radius: 12px; margin-bottom: 14px;
    }
    .my-comment { border-left: 3px solid #667eea; }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <img src="../assets/img/logoo.png" alt="SkillNex Logo">
      <span>SkillNex</span>
    </div>
    <div class="menu">
      <a href="dashboard.php">Dashboard</a>
      <a href="mycourse.php">My Course</a>
      <a href="message.php">Message</a>
      <a href="community.php">Community</a>
      <a href="forum.php" class="active">Forum</a>
      <a href="livechat.php">Live Chat</a>
      <a href="payment.php">Payment</a>
      <a href="profile.php">Profile</a>
      <a href="createcourse.php">➕ Buat Kursus</a>
    </div>
    <a href="../logout.php" class="logout-btn">🚪 Logout</a>
  </div>

  <div class="navbar">
    <a href="dashboard.php">Home</a>
    <a href="about.php">About</a>
    <div class="navbar-user">
            <img src="<?= !empty($user['foto_profil']) ? '../assets/' . htmlspecialchars($user['foto_profil']) : '../assets/img/default-profile.png' ?>"
            class="navbar-avatar"
            onerror="this.src='../assets/img/default-profile.png'">
            <strong><?= htmlspecialchars($user['nama']) ?></strong>
        </div>
  </div>

  <div class="container">

    <!-- Tombol kembali -->
    <a href="forum.php" class="btn-back">← Kembali ke Forum</a>

    <?php if (!empty($success)): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Detail Post -->
    <div class="post-detail-card">
        <span class="post-detail-kategori"><?= htmlspecialchars($post['kategori']) ?></span>
        <div class="post-detail-judul"><?= htmlspecialchars($post['judul']) ?></div>
        <div class="post-detail-konten"><?= nl2br(htmlspecialchars($post['konten'])) ?></div>

        <div class="post-detail-meta">
            <?php
            $foto_post = !empty($post['foto_profil']) && $post['foto_profil'] !== 'default.jpg'
            ? '../assets/' . $post['foto_profil']
            : '../assets/img/default-profile.png';
            ?>
            <img src="<?= htmlspecialchars($foto_post) ?>" alt="foto"
                 onerror="this.src='../assets/img/default-profile.png'">
            <div>
                <div class="post-detail-meta-name"><?= htmlspecialchars($post['nama']) ?></div>
                <div class="post-detail-meta-date">
                    <?= date('d M Y H:i', strtotime($post['created_at'])) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- List komentar -->
    <div class="section-title">
        💬 <?= count($komentar_list) ?> Komentar
    </div>

    <?php if (empty($komentar_list)): ?>
        <div class="empty-komentar">
            <p>Belum ada komentar. Jadilah yang pertama berkomentar!</p>
        </div>
    <?php else: ?>
        <?php foreach ($komentar_list as $kom): ?>
            <?php
            $foto_kom = !empty($kom['foto_profil']) && $kom['foto_profil'] !== 'default.jpg'
                ? '../assets/' . $kom['foto_profil']
                : '../assets/img/default-profile.png';
            $is_mine = $kom['user_id'] == $user_id;
            ?>
            <div class="komentar-card <?= $is_mine ? 'my-comment' : '' ?>">
                <div class="komentar-header">
                    <div class="komentar-user">
                        <img src="<?= htmlspecialchars($foto_kom) ?>" alt="foto"
                             onerror="this.src='../assets/img/default-profile.png'">
                        <div>
                            <div class="komentar-user-name">
                                <?= htmlspecialchars($kom['nama']) ?>
                                <?= $is_mine ? '<span style="color:#667eea;font-size:11px;"> (Kamu)</span>' : '' ?>
                            </div>
                            <div class="komentar-user-date">
                                <?= date('d M Y H:i', strtotime($kom['created_at'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol hapus hanya muncul untuk komentar milik sendiri -->
                    <?php if ($is_mine): ?>
                        <a href="forum_detail.php?id=<?= $post_id ?>&hapus_komentar=<?= $kom['id'] ?>"
                           class="btn-hapus-kom"
                           onclick="return confirm('Yakin hapus komentar ini?')">
                           🗑 Hapus
                        </a>
                    <?php endif; ?>
                </div>
                <div class="komentar-text">
                    <?= nl2br(htmlspecialchars($kom['komentar'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Form kirim komentar -->
    <div class="form-komentar">
        <h3>✍️ Tulis Komentar</h3>
        <form method="POST" action="">
            <textarea name="komentar" class="form-textarea"
                      placeholder="Tulis komentarmu di sini..." required></textarea>
            <br>
            <button type="submit" name="kirim_komentar" class="btn-kirim">
                📤 Kirim Komentar
            </button>
        </form>
    </div>

  </div>
</body>
</html>