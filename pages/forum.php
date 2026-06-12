<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$foto = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? '../assets/' . $user['foto_profil']
    : '../assets/img/default-profile.png';

$nama = htmlspecialchars($user['nama']);

$success = '';
$error   = '';

// === BUAT POST BARU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_post'])) {
    $judul    = trim($_POST['judul']);
    $konten   = trim($_POST['konten']);
    $kategori = trim($_POST['kategori']);

    if (empty($judul) || empty($konten)) {
        $error = "Judul dan isi diskusi tidak boleh kosong!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO forum_posts (user_id, judul, konten, kategori) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $judul, $konten, $kategori]);
        $success = "Diskusi berhasil dibuat!";
    }
}

// === HAPUS POST (hanya pemilik) ===
if (isset($_GET['hapus_post'])) {
    $post_id = (int)$_GET['hapus_post'];
    $cek = $pdo->prepare("SELECT user_id FROM forum_posts WHERE id = ?");
    $cek->execute([$post_id]);
    $post = $cek->fetch();

    if ($post && $post['user_id'] == $user_id) {
        $pdo->prepare("DELETE FROM forum_posts WHERE id = ?")->execute([$post_id]);
    }
    header("Location: forum.php");
    exit;
}

// === AMBIL SEMUA POST + info user + jumlah komentar ===
$posts = $pdo->query("
    SELECT 
        fp.id,
        fp.judul,
        fp.konten,
        fp.kategori,
        fp.created_at,
        fp.user_id,
        u.nama,
        u.foto_profil,
        COUNT(fc.id) as jumlah_komentar
    FROM forum_posts fp
    JOIN users u ON fp.user_id = u.id
    LEFT JOIN forum_comments fc ON fc.post_id = fp.id
    GROUP BY fp.id
    ORDER BY fp.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forum - SkillNex</title>
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
    .btn-buat {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white; border: none; padding: 12px 24px;
        border-radius: 10px; font-size: 14px; font-weight: 600;
        cursor: pointer; font-family: inherit; transition: all 0.3s;
        margin-bottom: 24px;
    }
    .btn-buat:hover { transform: translateY(-2px); opacity: 0.9; }
    .modal-overlay {
        display: none; position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.7); z-index: 2000;
        justify-content: center; align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #0f2239; border: 1px solid #2d5a8e;
        border-radius: 16px; padding: 30px;
        width: 90%; max-width: 520px; color: white;
    }
    .modal-box h3 { margin-bottom: 20px; font-size: 18px; }
    .modal-close {
        float: right; background: none; border: none;
        color: #7a8ea8; font-size: 22px; cursor: pointer; margin-top: -5px;
    }
    .form-group { margin-bottom: 16px; }
    .form-label {
        font-size: 12px; font-weight: 600; color: #7a8ea8;
        text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;
    }
    .form-input {
        width: 100%; padding: 12px 16px;
        border: 2px solid #2d5a8e; border-radius: 10px;
        font-size: 14px; font-family: inherit;
        background: #1e3a5f; color: #fff; transition: all 0.3s;
    }
    .form-input:focus { outline: none; border-color: #5b9cf5; }
    .btn-submit {
        width: 100%; padding: 13px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white; font-weight: 600; font-size: 15px;
        cursor: pointer; font-family: inherit; margin-top: 8px;
    }
    .post-meta {
        display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
    }
    .post-meta img {
        width: 36px; height: 36px; border-radius: 50%; object-fit: cover;
        border: 2px solid #2d5a8e;
    }
    .post-meta-info { flex: 1; }
    .post-meta-name { font-size: 14px; font-weight: 600; color: #fff; }
    .post-meta-date { font-size: 12px; color: #7a8ea8; }
    .post-kategori {
        display: inline-block; background: #1e3a5f;
        color: #5b9cf5; padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600; margin-bottom: 8px;
        border: 1px solid #2d5a8e;
    }
    .post-judul {
        font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 6px;
    }
    .post-preview {
        font-size: 13px; color: #9eafc4; line-height: 1.6;
        display: -webkit-box; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden;
    }
    .post-footer {
        display: flex; justify-content: space-between;
        align-items: center; margin-top: 14px;
        padding-top: 12px; border-top: 1px solid #1e3a5f;
    }
    .btn-lihat {
        background: #2d5a8e; color: white;
        padding: 8px 18px; border-radius: 8px;
        text-decoration: none; font-size: 13px; font-weight: 600;
        transition: all 0.3s;
    }
    .btn-lihat:hover { background: #5b9cf5; }
    .btn-hapus-post {
        background: none; border: 1px solid #e53e3e;
        color: #e53e3e; padding: 7px 14px; border-radius: 8px;
        font-size: 12px; cursor: pointer; font-family: inherit;
        transition: all 0.3s;
    }
    .btn-hapus-post:hover { background: #e53e3e; color: white; }
    .komentar-count { font-size: 13px; color: #7a8ea8; }
    .empty-state {
        text-align: center; padding: 60px 20px; color: #7a8ea8;
    }
    .empty-state .empty-icon { font-size: 48px; margin-bottom: 16px; }  
    .post-card {
        background: #0f2239; border: 1px solid #1e3a5f;
        border-radius: 14px; padding: 20px; margin-bottom: 16px;
        transition: all 0.3s;
    }
    .post-card:hover { border-color: #2d5a8e; transform: translateY(-2px); }
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
    <a href="dashboard.php" class="active">Home</a>
    <a href="about.php">About</a>
    <div class="navbar-user">
            <img src="<?= !empty($user['foto_profil']) ? '../assets/' . htmlspecialchars($user['foto_profil']) : '../assets/img/default-profile.png' ?>"
            class="navbar-avatar"
            onerror="this.src='../assets/img/default-profile.png'">
            <strong><?= htmlspecialchars($user['nama']) ?></strong>
        </div>
  </div>

  <div class="container">
    <div class="title-box">
      <h2>💬 Forum Diskusi</h2>
      <p>Tempat berbagi ilmu dan berdiskusi bersama komunitas SkillNex</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tombol buat diskusi baru -->
    <button class="btn-buat" onclick="document.getElementById('modalBuat').classList.add('active')">
        ✏️ Buat Diskusi Baru
    </button>

    <!-- List semua post -->
    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <div class="empty-icon">💬</div>
            <p>Belum ada diskusi. Jadilah yang pertama berdiskusi!</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-meta">
                    <?php
                    $foto = !empty($post['foto_profil']) && $post['foto_profil'] !== 'default.jpg'
                        ? '../assets/' . $post['foto_profil']
                        : '../assets/img/logoo.png';
                    ?>
                    <img src="<?= htmlspecialchars($foto) ?>" alt="foto"
                         onerror="this.src='../assets/img/logoo.png'">
                    <div class="post-meta-info">
                        <div class="post-meta-name"><?= htmlspecialchars($post['nama']) ?></div>
                        <div class="post-meta-date">
                            <?= date('d M Y H:i', strtotime($post['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <span class="post-kategori"><?= htmlspecialchars($post['kategori']) ?></span>
                <div class="post-judul"><?= htmlspecialchars($post['judul']) ?></div>
                <div class="post-preview"><?= htmlspecialchars($post['konten']) ?></div>

                <div class="post-footer">
                    <span class="komentar-count">
                        💬 <?= $post['jumlah_komentar'] ?> komentar
                    </span>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <!-- Tombol hapus hanya muncul kalau pemilik post -->
                        <?php if ($post['user_id'] == $user_id): ?>
                            <a href="forum.php?hapus_post=<?= $post['id'] ?>"
                               class="btn-hapus-post"
                               onclick="return confirm('Yakin hapus diskusi ini?')">
                               🗑 Hapus
                            </a>
                        <?php endif; ?>
                        <a href="forum_detail.php?id=<?= $post['id'] ?>" class="btn-lihat">
                            Lihat Diskusi →
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Modal buat post baru -->
  <div class="modal-overlay" id="modalBuat">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('modalBuat').classList.remove('active')">✕</button>
        <h3>✏️ Buat Diskusi Baru</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Judul Diskusi</label>
                <input type="text" name="judul" class="form-input"
                       placeholder="Contoh: Font terbaik untuk web design?" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="kategori" class="form-input">
                    <option value="Umum">Umum</option>
                    <option value="Designer">Designer</option>
                    <option value="Programming">Programming</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Bisnis">Bisnis</option>
                    <option value="Musik">Musik</option>
                    <option value="Bahasa">Bahasa</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Isi Diskusi</label>
                <textarea name="konten" class="form-input" rows="5"
                          placeholder="Tulis pertanyaan atau topik diskusimu di sini..." required></textarea>
            </div>
            <button type="submit" name="buat_post" class="btn-submit">🚀 Posting Diskusi</button>
        </form>
    </div>
  </div>

</body>
</html>