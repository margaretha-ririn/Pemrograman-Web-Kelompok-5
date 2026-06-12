<?php
session_start();
require '../config/db.php';

// Proteksi: kalau belum login, tendang ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$success = '';
$error   = '';

// Proses UPDATE profil kalau form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // === UPDATE BIO & NAMA ===
    if (isset($_POST['update_profil'])) {
        $nama  = trim($_POST['nama']);
        $bio   = trim($_POST['bio']);
        $phone = trim($_POST['phone']);
        $kota  = trim($_POST['kota']);

        if (empty($nama)) {
            $error = "Nama tidak boleh kosong!";
        } else {
            $upd = $pdo->prepare("UPDATE users SET nama = ?, bio = ?, phone = ?, kota = ? WHERE id = ?");
            $upd->execute([$nama, $bio, $phone, $kota, $user_id]);

            $_SESSION['user_nama'] = $nama;
            $success = "Profil berhasil diperbarui!";

            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    }

    // === UPDATE PASSWORD ===
    if (isset($_POST['update_password'])) {
        $pass_lama = $_POST['password_lama'];
        $pass_baru = $_POST['password_baru'];
        $konfirm   = $_POST['konfirmasi_baru'];

        if (!password_verify($pass_lama, $user['password'])) {
            $error = "Password lama salah!";
        } elseif ($pass_baru !== $konfirm) {
            $error = "Password baru dan konfirmasi tidak cocok!";
        } elseif (strlen($pass_baru) < 6) {
            $error = "Password baru minimal 6 karakter!";
        } else {
            $hash = password_hash($pass_baru, PASSWORD_BCRYPT);
            $upd  = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$hash, $user_id]);
            $success = "Password berhasil diperbarui!";
        }
    }

    // === UPLOAD FOTO PROFIL ===
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext_allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $ext_allowed)) {
            $error = "Format foto harus JPG, JPEG, PNG, atau WEBP!";
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error = "Ukuran foto maksimal 2MB!";
        } else {
            $nama_file = 'foto_' . $user_id . '_' . time() . '.' . $ext;
            $tujuan    = '../assets/' . $nama_file;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $tujuan)) {
                $upd = $pdo->prepare("UPDATE users SET foto_profil = ? WHERE id = ?");
                $upd->execute([$nama_file, $user_id]);

                $_SESSION['user_foto'] = $nama_file;
                $success = "Foto profil berhasil diperbarui!";

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = "Gagal upload foto. Pastikan folder assets bisa ditulis.";
            }
        }
    }
}

// === DATA STATISTIK PROFIL ===

// Jumlah kursus dan rata-rata progress
$stat = $pdo->prepare("
    SELECT COUNT(*) AS total_kursus, COALESCE(ROUND(AVG(progress)), 0) AS avg_progress
    FROM enrollments
    WHERE user_id = ?
");
$stat->execute([$user_id]);
$statData = $stat->fetch();

$total_kursus = (int)($statData['total_kursus'] ?? 0);
$completion   = (int)($statData['avg_progress'] ?? 0);

// Jumlah komunitas
$qCommunity = $pdo->prepare("SELECT COUNT(*) AS total FROM community_members WHERE user_id = ?");
$qCommunity->execute([$user_id]);
$total_community = (int)$qCommunity->fetch()['total'];

// Jumlah forum post, aman kalau tabel belum ada
$total_forum = 0;
try {
    $qForum = $pdo->prepare("SELECT COUNT(*) AS total FROM forum_posts WHERE user_id = ?");
    $qForum->execute([$user_id]);
    $total_forum = (int)$qForum->fetch()['total'];
} catch (Exception $e) {
    $total_forum = 0;
}

// Ambil kursus yang diikuti user
$qCourses = $pdo->prepare("
    SELECT c.judul, c.author, e.progress, e.created_at
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ?
    ORDER BY e.created_at DESC
");
$qCourses->execute([$user_id]);
$user_courses = $qCourses->fetchAll();

// Hitung skills berdasarkan kategori/judul course yang diikuti
$skills = [];

foreach ($user_courses as $course) {
    $kategori = '';
    $judul    = strtolower($course['judul'] ?? '');
    $progress = (int)($course['progress'] ?? 0);

    // Nilai dasar: kalau baru enroll progress 0, tetap kasih 15 sebagai exposure awal
    $nilai = max(15, min(100, $progress));

    if (str_contains($kategori, 'program') || str_contains($judul, 'program') || str_contains($judul, 'coding')) {
        $skills['Programming'] = max($skills['Programming'] ?? 0, $nilai);
        $skills['Web Development'] = max($skills['Web Development'] ?? 0, min(100, $nilai + 5));
    } elseif (str_contains($kategori, 'desain') || str_contains($judul, 'desain') || str_contains($judul, 'ui') || str_contains($judul, 'ux')) {
        $skills['UI/UX Design'] = max($skills['UI/UX Design'] ?? 0, $nilai);
        $skills['Creativity'] = max($skills['Creativity'] ?? 0, min(100, $nilai + 5));
    } elseif (str_contains($kategori, 'public') || str_contains($judul, 'speaking')) {
        $skills['Public Speaking'] = max($skills['Public Speaking'] ?? 0, $nilai);
        $skills['Communication'] = max($skills['Communication'] ?? 0, min(100, $nilai + 5));
    } elseif (str_contains($kategori, 'musik') || str_contains($judul, 'gitar') || str_contains($judul, 'musik')) {
        $skills['Music'] = max($skills['Music'] ?? 0, $nilai);
    } elseif (str_contains($judul, 'masak')) {
        $skills['Cooking'] = max($skills['Cooking'] ?? 0, $nilai);
    } elseif (str_contains($judul, 'bahasa') || str_contains($judul, 'inggris')) {
        $skills['Language Learning'] = max($skills['Language Learning'] ?? 0, $nilai);
        $skills['Communication'] = max($skills['Communication'] ?? 0, min(100, $nilai + 5));
    } else {
        $skills['Learning Progress'] = max($skills['Learning Progress'] ?? 0, $nilai);
    }
}

arsort($skills);

// Recent activity
$activities = [];

if (!empty($user['created_at'])) {
    $activities[] = [
        'text' => 'Akun dibuat di SkillNex',
        'time' => $user['created_at']
    ];
}

foreach (array_slice($user_courses, 0, 3) as $course) {
    $activities[] = [
        'text' => 'Mendaftar kursus ' . $course['judul'],
        'time' => $course['created_at']
    ];
}

try {
    $qCommAct = $pdo->prepare("
        SELECT c.nama, cm.joined_at
        FROM community_members cm
        JOIN communities c ON cm.community_id = c.id
        WHERE cm.user_id = ?
        ORDER BY cm.joined_at DESC
        LIMIT 3
    ");
    $qCommAct->execute([$user_id]);
    foreach ($qCommAct->fetchAll() as $comm) {
        $activities[] = [
            'text' => 'Bergabung ke komunitas ' . $comm['nama'],
            'time' => $comm['joined_at']
        ];
    }
} catch (Exception $e) {}

usort($activities, function($a, $b) {
    return strtotime($b['time']) <=> strtotime($a['time']);
});
$activities = array_slice($activities, 0, 5);

// Achievements dinamis
$achievements = [];

if ($total_kursus >= 1) {
    $achievements[] = ['icon' => '🎓', 'name' => 'First Course'];
}
if ($total_kursus >= 3) {
    $achievements[] = ['icon' => '📚', 'name' => 'Active Learner'];
}
if ($completion >= 50) {
    $achievements[] = ['icon' => '🚀', 'name' => 'Halfway There'];
}
if ($completion >= 100) {
    $achievements[] = ['icon' => '🏆', 'name' => 'Course Finisher'];
}
if ($total_community >= 1) {
    $achievements[] = ['icon' => '👥', 'name' => 'Community Member'];
}
if ($total_community >= 3) {
    $achievements[] = ['icon' => '🌐', 'name' => 'Community Explorer'];
}
if ($total_forum >= 1) {
    $achievements[] = ['icon' => '💬', 'name' => 'Discussion Starter'];
}

// Tentukan foto profil
$foto_profil = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? '../assets/' . $user['foto_profil']
    : '../assets/img/default-profile.png';

// Rating belum ada sistem review, jadi jangan tampilkan angka palsu
$rating_display = '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - SkillNex</title>
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    html,
    body,
    .container,
    .main {
        background: #ffffff !important;
    }
    .alert-success {
        background: #1a4731;
        color: #6fcf97;
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #27ae60;
        font-size: 14px;
        font-weight: 500;
    }
    .alert-error {
        background: #4a1515;
        color: #eb5757;
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #e53e3e;
        font-size: 14px;
        font-weight: 500;
    }
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #2d5a8e;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        background: #1e3a5f;
        color: #ffffff;
        margin-top: 6px;
        transition: all 0.3s;
    }
    .form-input:focus {
        outline: none;
        border-color: #5b9cf5;
        box-shadow: 0 0 0 3px rgba(91,156,245,0.15);
    }
    .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #7a8ea8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .btn-primary {
        background: #2d5a8e;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        font-family: inherit;
        transition: all 0.3s;
        margin-top: 8px;
    }
    .btn-primary:hover {
        background: #5b9cf5;
        transform: translateY(-2px);
    }
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 2000;
        justify-content: center;
        align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #0f2239;
        border: 1px solid #2d5a8e;
        border-radius: 16px;
        padding: 30px;
        width: 90%;
        max-width: 450px;
        color: white;
    }
    .modal-box h3 {
        margin-bottom: 20px;
        font-size: 18px;
        color: #fff;
    }
    .modal-close {
        float: right;
        background: none;
        border: none;
        color: #7a8ea8;
        font-size: 22px;
        cursor: pointer;
        margin-top: -5px;
    }
    .empty-small {
        color: #7a8ea8;
        font-size: 14px;
        padding: 12px 0;
    }
    .skill-note {
        color:#7a8ea8;
        font-size:12px;
        margin-top:8px;
    }
  </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/img/logoo.png" alt="logo">
            <span>SkillNex</span>
        </div>
        <div class="menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="mycourse.php">My Course</a>
            <a href="message.php">Message</a>
            <a href="community.php">Community</a>
            <a href="forum.php">Forum</a>
            <a href="livechat.php">Live Chat</a>
            <a href="payment.php">Payment</a>
            <a href="profile.php" class="active">Profile</a>
            <a href="createcourse.php">➕ Buat Kursus</a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" style="color:rgba(255,255,255,0.7); text-decoration:none;">🚪 Logout</a>
        </div>
    </div>

    <div class="navbar">
        <a href="dashboard.php">Home</a>
        <a href="about.php">About</a>
        <div style="margin-left:auto; font-size:14px; color:white;">
        </div>
    </div>

  <main class="main-content">

    <?php if (!empty($success)): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-header">
      <div class="profile-pic-wrapper">
        <div class="profile-pic">
          <img src="<?= htmlspecialchars($foto_profil) ?>" alt="<?= htmlspecialchars($user['nama']) ?>"
               onerror="this.src='../assets/img/default-profile.png'">
          <div class="status-indicator online"></div>
        </div>

        <form method="POST" enctype="multipart/form-data" style="text-align:center;">
            <label for="fotoInput" class="btn-upload" style="cursor:pointer;">
                <span class="icon">📷</span> Upload New Photo
            </label>
            <input type="file" id="fotoInput" name="foto" accept="image/*"
                   style="display:none;" onchange="this.form.submit()">
        </form>
      </div>

      <div class="profile-summary">
        <div class="profile-name-section">
          <h1><?= htmlspecialchars($user['nama']) ?></h1>
          <span class="verified-badge">✓ Verified</span>
        </div>
        <p class="profile-tagline">
            <?= !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'SkillNex Member' ?>
        </p>

        <div class="profile-stats">
          <div class="stat-item">
            <span class="stat-number"><?= $total_kursus ?></span>
            <span class="stat-label">Courses</span>
          </div>
          <div class="stat-item">
            <span class="stat-number"><?= $completion ?>%</span>
            <span class="stat-label">Completion</span>
          </div>
          <div class="stat-item">
            <span class="stat-number"><?= $rating_display ?></span>
            <span class="stat-label">Rating</span>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <section class="card contact-card">
        <div class="card-header">
          <h3><span class="icon">📧</span> Contact Information</h3>
          <button class="edit-btn" onclick="document.getElementById('modalProfil').classList.add('active')">
            <span class="icon">✏️</span> Edit
          </button>
        </div>
        <div class="info-list">
          <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Phone</span>
            <span class="info-value"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '-' ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Joined</span>
            <span class="info-value"><?= !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '-' ?></span>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="card-header">
          <h3><span class="icon">📍</span> Address & Location</h3>
        </div>
        <div class="info-list">
          <div class="info-item">
            <span class="info-label">City</span>
            <span class="info-value"><?= !empty($user['kota']) ? htmlspecialchars($user['kota']) : '-' ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Bio</span>
            <span class="info-value"><?= !empty($user['bio']) ? htmlspecialchars($user['bio']) : '-' ?></span>
          </div>
        </div>
      </section>
    </div>

    <section class="card skills-card">
      <div class="card-header">
        <h3><span class="icon">🎯</span> Skills & Expertise</h3>
      </div>

      <?php if (empty($skills)): ?>
        <div class="empty-small">
            Belum ada skill yang terdeteksi. Daftar kursus dulu agar skill muncul otomatis.
        </div>
      <?php else: ?>
        <div class="skills-grid">
          <?php foreach ($skills as $skillName => $skillValue): ?>
            <div class="skill-item">
              <div class="skill-header">
                <span class="skill-name"><?= htmlspecialchars($skillName) ?></span>
                <span class="skill-percentage"><?= (int)$skillValue ?>%</span>
              </div>
              <div class="skill-bar">
                <div class="skill-progress" style="width:<?= (int)$skillValue ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="skill-note">
            Skill dihitung otomatis dari kategori kursus yang kamu ikuti dan progress belajar.
        </div>
      <?php endif; ?>
    </section>

    <div class="two-col-grid">
      <section class="card bio-card">
        <div class="card-header">
          <h3><span class="icon">✍️</span> Bio</h3>
          <button class="edit-btn" onclick="document.getElementById('modalProfil').classList.add('active')">
            <span class="icon">✏️</span> Edit
          </button>
        </div>
        <p class="bio-text">
            <?= !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'Belum ada bio. Klik Edit untuk menambahkan.' ?>
        </p>
      </section>

      <section class="card achievements-card">
        <div class="card-header">
          <h3><span class="icon">🏆</span> Achievements</h3>
        </div>

        <?php if (empty($achievements)): ?>
            <div class="empty-small">
                Belum ada achievement. Mulai daftar kursus atau join community dulu.
            </div>
        <?php else: ?>
            <div class="achievements-grid">
              <?php foreach ($achievements as $ach): ?>
                <div class="achievement-badge">
                  <span class="achievement-icon"><?= $ach['icon'] ?></span>
                  <span class="achievement-name"><?= htmlspecialchars($ach['name']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
        <?php endif; ?>
      </section>
    </div>

    <div class="two-col-grid">
      <section class="card activity-card">
        <div class="card-header">
          <h3><span class="icon">📈</span> Recent Activity</h3>
        </div>

        <div class="activity-list">
          <?php if (empty($activities)): ?>
            <div class="empty-small">Belum ada aktivitas.</div>
          <?php else: ?>
            <?php foreach ($activities as $act): ?>
              <div class="activity-item">
                <span class="activity-dot"></span>
                <div class="activity-content">
                  <p class="activity-text"><?= htmlspecialchars($act['text']) ?></p>
                  <span class="activity-time">
                    <?= !empty($act['time']) ? date('d M Y', strtotime($act['time'])) : '-' ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <section class="card">
        <div class="card-header">
          <h3><span class="icon">🔒</span> Ganti Password</h3>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Password Lama</label>
                <input type="password" name="password_lama" class="form-input" placeholder="Masukkan password lama" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password Baru</label>
                <input type="password" name="password_baru" class="form-input" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="konfirmasi_baru" class="form-input" placeholder="Ulangi password baru" required>
            </div>
            <button type="submit" name="update_password" class="btn-primary">🔒 Simpan Password</button>
        </form>
      </section>
    </div>

  </main>

  <div class="modal-overlay" id="modalProfil">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('modalProfil').classList.remove('active')">✕</button>
        <h3>✏️ Edit Profil</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-input"
                       value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">No. HP</label>
                <input type="text" name="phone" class="form-input"
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                       placeholder="+62 xxx xxxx xxxx">
            </div>
            <div class="form-group">
                <label class="form-label">Kota</label>
                <input type="text" name="kota" class="form-input"
                       value="<?= htmlspecialchars($user['kota'] ?? '') ?>"
                       placeholder="Contoh: Medan, Sumatera Utara">
            </div>
            <div class="form-group">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-input" rows="3"
                          placeholder="Ceritakan sedikit tentang dirimu..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            <button type="submit" name="update_profil" class="btn-primary" style="width:100%;">
                💾 Simpan Perubahan
            </button>
        </form>
    </div>
  </div>

</body>
</html>
