<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$foto = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? '../assets/' . $user['foto_profil']
    : '../assets/img/logoo.png';

$nama = htmlspecialchars($user['nama']);

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga     = (float)($_POST['harga'] ?? 0);
    $tipe      = in_array($_POST['tipe'], ['gratis', 'berbayar']) ? $_POST['tipe'] : 'gratis';
    $thumbnail = '';

    // Upload thumbnail
    if (!empty($_FILES['thumbnail']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename  = 'course_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $uploadDir = '../assets/thumbnails/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $filename)) {
                $thumbnail = 'thumbnails/' . $filename;
            }
        } else {
            $error = 'Format thumbnail harus JPG, PNG, atau WEBP.';
        }
    }

    if (empty($error) && !empty($judul)) {
        // Insert course
        $stmt = $pdo->prepare("
            INSERT INTO courses (judul, deskripsi, harga, tipe, thumbnail, author, author_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$judul, $deskripsi, $harga, $tipe, $thumbnail, $user['nama'], $user_id]);
        $course_id = $pdo->lastInsertId();

        // Insert modul-modul
        $modul_juduls = $_POST['modul_judul'] ?? [];
        $modul_tipes  = $_POST['modul_tipe']  ?? [];
        $urutan = 1;
        foreach ($modul_juduls as $i => $mj) {
            $mj = trim($mj);
            if (empty($mj)) continue;
            $mt = in_array($modul_tipes[$i] ?? '', ['video', 'materi', 'kuis']) ? $modul_tipes[$i] : 'materi';
            $ins = $pdo->prepare("INSERT INTO modules (course_id, judul, tipe, urutan) VALUES (?, ?, ?, ?)");
            $ins->execute([$course_id, $mj, $mt, $urutan]);
            $urutan++;
        }

        $success = 'Kursus berhasil dibuat! <a href="payment.php">Lihat di Payment</a>';
    } elseif (empty($judul)) {
        $error = 'Judul kursus tidak boleh kosong.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat Kursus - SkillNex</title>
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <style>
    /* ── LAYOUT ── */
    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }

    .sidebar { position:fixed; left:0; top:0; height:100%; width:180px;
      background:#0f2239; color:white; padding:30px 20px;
      z-index:1000; display:flex; flex-direction:column; }
    .sidebar .logo { display:flex; align-items:center; gap:8px; margin-bottom:30px; }
    .sidebar .logo img { width:45px; height:45px; object-fit:cover; }
    .sidebar .logo span { font-weight:600; font-size:18px; }
    .sidebar .menu { display:flex; flex-direction:column; gap:4px; }
    .sidebar .menu a { color:rgba(255,255,255,0.85); text-decoration:none;
      padding:9px 12px; border-radius:8px; font-size:14px; transition:all .2s; }
    .sidebar .menu a:hover { background:rgba(255,255,255,0.08); }
    .sidebar .menu a.active { background:white; color:#0f2239; font-weight:600; }
    .sidebar-footer { margin-top:auto; }
    .sidebar-footer a { color:rgba(255,255,255,0.6); text-decoration:none; font-size:13px; }

    .navbar { position:fixed; left:180px; top:0; right:0;
      background:#0f2239; padding:15px 32px;
      display:flex; align-items:center; gap:24px; z-index:999; }
    .navbar a { color:white; text-decoration:none; font-size:14px; }
    .navbar a.active { border-bottom:2px solid white; font-weight:600; }
    .navbar-user { margin-left:auto; font-size:14px; color:white; }

    .main { margin-left:180px; margin-top:56px; padding:32px; }

    /* ── FORM CARD ── */
    .form-card {
      background: white;
      border-radius: 16px;
      padding: 36px 40px;
      max-width: 760px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    }

    .form-card h1 {
      font-size: 22px;
      color: #1a202c;
      margin: 0 0 6px;
    }
    .form-card .subtitle {
      font-size: 13px;
      color: #64748b;
      margin-bottom: 28px;
    }

    .form-group { margin-bottom: 20px; }
    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 6px;
    }
    .form-group input[type=text],
    .form-group input[type=number],
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      font-family: inherit;
      outline: none;
      background: #f8fafc;
      color: #1e293b;
      transition: border-color .2s;
      box-sizing: border-box;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus { border-color: #667eea; background: white; }
    .form-group textarea { resize: vertical; min-height: 90px; }

    .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    /* ── MODUL SECTION ── */
    .modul-section { margin-top: 28px; }
    .modul-section h2 {
      font-size: 16px; color: #1a202c; margin-bottom: 4px;
    }
    .modul-section .hint {
      font-size: 12px; color: #94a3b8; margin-bottom: 14px;
    }

    .modul-list { display: flex; flex-direction: column; gap: 10px; }

    .modul-item {
      display: grid;
      grid-template-columns: 1fr 160px 40px;
      gap: 10px;
      align-items: center;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 12px 14px;
    }
    .modul-item input[type=text] {
      padding: 8px 12px;
      border: 1.5px solid #e2e8f0;
      border-radius: 8px;
      font-size: 13px;
      font-family: inherit;
      outline: none;
      background: white;
      width: 100%;
      box-sizing: border-box;
    }
    .modul-item input:focus { border-color: #667eea; }
    .modul-item select {
      padding: 8px 10px;
      border: 1.5px solid #e2e8f0;
      border-radius: 8px;
      font-size: 13px;
      font-family: inherit;
      outline: none;
      background: white;
      width: 100%;
      box-sizing: border-box;
    }
    .modul-item select:focus { border-color: #667eea; }
    .btn-hapus-modul {
      width: 36px; height: 36px;
      border-radius: 8px;
      background: #fee2e2;
      color: #ef4444;
      border: none;
      cursor: pointer;
      font-size: 16px;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s;
    }
    .btn-hapus-modul:hover { background: #fecaca; }

    .btn-tambah-modul {
      margin-top: 10px;
      padding: 9px 18px;
      border-radius: 8px;
      background: #eef2ff;
      color: #667eea;
      border: 2px dashed #c7d2fe;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all .2s;
      font-family: inherit;
    }
    .btn-tambah-modul:hover { background: #e0e7ff; border-color: #667eea; }

    /* ── ACTIONS ── */
    .form-actions { margin-top: 32px; display: flex; gap: 12px; align-items: center; }
    .btn-submit {
      padding: 12px 32px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 600;
      cursor: pointer; font-family: inherit;
      transition: opacity .2s, transform .2s;
    }
    .btn-submit:hover { opacity: .9; transform: translateY(-1px); }
    .btn-cancel {
      padding: 12px 24px;
      background: #f1f5f9;
      color: #64748b; border: none; border-radius: 10px;
      font-size: 14px; cursor: pointer; font-family: inherit;
      text-decoration: none; display: inline-block;
    }
    .btn-cancel:hover { background: #e2e8f0; }

    /* ── ALERT ── */
    .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
    .alert.success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .alert.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

    /* thumbnail preview */
    #thumbPreview {
      display: none; margin-top: 8px;
      width: 120px; height: 80px;
      object-fit: cover; border-radius: 8px;
      border: 2px solid #e2e8f0;
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
    <a href="profile.php">Profile</a>
  </div>
  <div class="sidebar-footer">
    <a href="../logout.php">🚪 Logout</a>
  </div>
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

<div class="main">
  <div class="form-card">
    <h1>📚 Buat Kursus Baru</h1>
    <p class="subtitle">Bagikan ilmumu kepada pengguna SkillNex lainnya.</p>

    <?php if ($success): ?>
      <div class="alert success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

      <div class="form-group">
        <label>Judul Kursus *</label>
        <input type="text" name="judul" placeholder="Contoh: Belajar Desain Grafis Dasar" required>
      </div>

      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" placeholder="Jelaskan isi kursus, untuk siapa, dan apa yang akan dipelajari..."></textarea>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label>Tipe Kursus</label>
          <select name="tipe" id="tipeSelect" onchange="toggleHarga(this.value)">
            <option value="gratis">Gratis</option>
            <option value="berbayar">Berbayar</option>
          </select>
        </div>
        <div class="form-group" id="hargaGroup" style="display:none;">
          <label>Harga (Rp)</label>
          <input type="number" name="harga" min="0" step="1000" placeholder="Contoh: 75000">
        </div>
      </div>

      <div class="form-group">
        <label>Thumbnail Kursus</label>
        <input type="file" name="thumbnail" accept="image/*" onchange="previewThumb(this)">
        <img id="thumbPreview" alt="Preview thumbnail">
      </div>

      <!-- MODUL -->
      <div class="modul-section">
        <h2>📋 Daftar Modul</h2>
        <p class="hint">Tambahkan modul/materi yang akan ada di kursus ini. Minimal 1 modul.</p>

        <div class="modul-list" id="modulList">
          <!-- Modul pertama default -->
          <div class="modul-item">
            <input type="text" name="modul_judul[]" placeholder="Judul modul..." required>
            <select name="modul_tipe[]">
              <option value="materi">📄 Materi</option>
              <option value="video">🎬 Video</option>
              <option value="kuis">📝 Kuis</option>
            </select>
            <button type="button" class="btn-hapus-modul" onclick="hapusModul(this)">✕</button>
          </div>
        </div>

        <button type="button" class="btn-tambah-modul" onclick="tambahModul()">
          + Tambah Modul
        </button>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-submit">🚀 Buat Kursus</button>
        <a href="payment.php" class="btn-cancel">Batal</a>
      </div>

    </form>
  </div>
</div>

<script>
  function toggleHarga(val) {
    document.getElementById('hargaGroup').style.display = val === 'berbayar' ? 'block' : 'none';
  }

  function previewThumb(input) {
    const preview = document.getElementById('thumbPreview');
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  function tambahModul() {
    const list = document.getElementById('modulList');
    const div  = document.createElement('div');
    div.className = 'modul-item';
    div.innerHTML = `
      <input type="text" name="modul_judul[]" placeholder="Judul modul...">
      <select name="modul_tipe[]">
        <option value="materi">📄 Materi</option>
        <option value="video">🎬 Video</option>
        <option value="kuis">📝 Kuis</option>
      </select>
      <button type="button" class="btn-hapus-modul" onclick="hapusModul(this)">✕</button>
    `;
    list.appendChild(div);
  }

  function hapusModul(btn) {
    const list = document.getElementById('modulList');
    if (list.children.length <= 1) {
      alert('Minimal harus ada 1 modul.');
      return;
    }
    btn.closest('.modul-item').remove();
  }
</script>

</body>
</html>