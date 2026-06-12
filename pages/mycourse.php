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

// ── HAPUS KURSUS MILIK SENDIRI ──
if (isset($_GET['hapus'])) {
    $cid = (int)$_GET['hapus'];
    $cek = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND author_id = ?");
    $cek->execute([$cid, $user_id]);
    if ($cek->fetch()) {
        $pdo->prepare("DELETE FROM modules WHERE course_id = ?")->execute([$cid]);
        $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$cid]);
        header("Location: mycourse.php?tab=saya&deleted=1");
        exit;
    }
}

// ── EDIT KURSUS MILIK SENDIRI ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $cid       = (int)$_POST['edit_id'];
    $judul     = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga     = (float)($_POST['harga'] ?? 0);
    $tipe      = in_array($_POST['tipe'], ['gratis','berbayar']) ? $_POST['tipe'] : 'gratis';

    $cek = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND author_id = ?");
    $cek->execute([$cid, $user_id]);
    if ($cek->fetch()) {
        $pdo->prepare("UPDATE courses SET judul=?, deskripsi=?, harga=?, tipe=? WHERE id=?")
            ->execute([$judul, $deskripsi, $harga, $tipe, $cid]);
        header("Location: mycourse.php?tab=saya&updated=1");
        exit;
    }
}

// Tandai modul selesai (otomatis hitung progress)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selesai_modul'])) {
    $module_id = (int)$_POST['module_id'];
    $course_id = (int)$_POST['course_id'];

    $cek = $pdo->prepare("SELECT id FROM module_progress WHERE user_id = ? AND module_id = ?");
    $cek->execute([$user_id, $module_id]);
    if (!$cek->fetch()) {
        $ins = $pdo->prepare("INSERT INTO module_progress (user_id, module_id) VALUES (?, ?)");
        $ins->execute([$user_id, $module_id]);
    }

    $total = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE course_id = ?");
    $total->execute([$course_id]);
    $total_modul = (int)$total->fetchColumn();

    $done = $pdo->prepare("
        SELECT COUNT(*) FROM module_progress mp
        JOIN modules m ON mp.module_id = m.id
        WHERE mp.user_id = ? AND m.course_id = ?
    ");
    $done->execute([$user_id, $course_id]);
    $done_modul = (int)$done->fetchColumn();

    $pct = $total_modul > 0 ? round(($done_modul / $total_modul) * 100) : 0;

    $upd = $pdo->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?");
    $upd->execute([$pct, $user_id, $course_id]);

    header("Location: mycourse.php");
    exit;
}

// Ambil kursus aktif
$aktif = $pdo->prepare("
    SELECT e.id as enroll_id, e.progress, e.next_session, e.created_at,
           c.judul, c.author, c.thumbnail, c.id as course_id, c.author_id
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.progress < 100
    ORDER BY e.created_at DESC
");
$aktif->execute([$user_id]);
$kursus_aktif = $aktif->fetchAll();

// Ambil kursus selesai
$selesai = $pdo->prepare("
    SELECT e.id as enroll_id, e.progress, e.next_session, e.created_at,
           c.judul, c.author, c.thumbnail, c.id as course_id, c.author_id
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.progress = 100
    ORDER BY e.created_at DESC
");
$selesai->execute([$user_id]);
$kursus_selesai = $selesai->fetchAll();

// ── AMBIL KURSUS YANG DIBUAT USER ──
$dibuat = $pdo->prepare("
    SELECT c.*, COUNT(m.id) AS jumlah_modul
    FROM courses c
    LEFT JOIN modules m ON m.course_id = c.id
    WHERE c.author_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$dibuat->execute([$user_id]);
$kursus_dibuat = $dibuat->fetchAll();
$total_dibuat  = count($kursus_dibuat);

// Fungsi ambil modul + status per kursus
function getModuls($pdo, $course_id, $user_id) {
    $q = $pdo->prepare("
        SELECT m.id, m.judul, m.tipe, m.urutan,
               IF(mp.id IS NOT NULL, 1, 0) as selesai
        FROM modules m
        LEFT JOIN module_progress mp ON mp.module_id = m.id AND mp.user_id = ?
        WHERE m.course_id = ?
        ORDER BY m.urutan ASC
    ");
    $q->execute([$user_id, $course_id]);
    return $q->fetchAll();
}

// Tab aktif dari URL
$active_tab = $_GET['tab'] ?? 'aktif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Course - SkillNex</title>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/mycourse.css">
    <style>
        /* ── STYLE LAMA (tidak diubah) ── */
        .tab-content { display:none; }
        .tab-content.active { display:block; }
        .tabs button { cursor:pointer; transition:all 0.3s; }
        .empty-state {
            text-align:center; padding:50px 20px; color:#7a8ea8;
            background:#0f2239; border-radius:12px;
            border:1px dashed #2d5a8e; margin-top:16px;
        }
        .empty-state .empty-icon { font-size:42px; margin-bottom:12px; }
        .navbar-user { margin-left:auto; font-size:14px; color:white; }
        .badge-selesai {
            display:inline-block; background:#1a4731; color:#6fcf97;
            padding:3px 10px; border-radius:20px; font-size:11px;
            font-weight:600; border:1px solid #27ae60;
        }
        .chat-btn-link { text-decoration:none; }
        .chat-btn-link .chat-btn {
            background:linear-gradient(135deg,#667eea,#764ba2);
            border:none; cursor:pointer; transition:all 0.3s;
        }
        .chat-btn-link .chat-btn:hover { transform:translateY(-2px); opacity:0.9; }
        .chat-btn-self { opacity:0.5; cursor:not-allowed !important; }
        .next-session-label { color:#ffffff; font-weight:500; }
        .next-session-val { color:#ffffff; }
        .modul-toggle {
            background:none; border:none; color:#667eea;
            font-size:12px; cursor:pointer; font-family:inherit;
            padding:0; margin-top:6px; text-decoration:underline;
        }
        .modul-list {
            display:none; margin-top:10px;
            border-top:1px solid #2d5a8e; padding-top:10px;
        }
        .modul-list.open { display:block; }
        .modul-item {
            display:flex; align-items:center; gap:10px;
            padding:8px 0; border-bottom:1px solid #1a3a5c;
            font-size:13px; color:#cdd9e5;
        }
        .modul-item:last-child { border-bottom:none; }
        .modul-num {
            width:24px; height:24px; border-radius:50%;
            background:#1a3a5c; display:flex; align-items:center;
            justify-content:center; font-size:11px; color:#7a8ea8;
            flex-shrink:0;
        }
        .modul-num.done { background:#1a4731; color:#6fcf97; }
        .modul-num.active-mod { background:#667eea; color:#fff; }
        .modul-name { flex:1; }
        .modul-name.done-text { text-decoration:line-through; color:#7a8ea8; }
        .badge-tipe {
            font-size:10px; padding:2px 7px; border-radius:4px;
            background:#1a3a5c; color:#7ab3f7;
        }
        .badge-tipe.kuis { background:#2d1a3a; color:#c084fc; }
        .badge-tipe.materi { background:#1a3a2a; color:#6fcf97; }
        .btn-selesai-modul {
            font-size:11px; padding:3px 10px; border-radius:6px;
            background:#667eea; color:#fff; border:none;
            cursor:pointer; font-family:inherit; white-space:nowrap;
        }
        .btn-selesai-modul:hover { background:#764ba2; }
        .modul-locked { opacity:0.4; }
        .progress-wrapper { display:flex; align-items:center; gap:8px; margin-top:4px; }
        .progress-container { flex:1; }

        /* ── STYLE LAMA: Tab Kursus Saya ── */
        .stat-saya {
            display:inline-flex; align-items:center; gap:14px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            border-radius:12px; padding:16px 24px;
            margin-bottom:20px; color:white;
            box-shadow:0 4px 16px rgba(102,126,234,0.3);
        }
        .stat-saya .s-icon { font-size:32px; }
        .stat-saya .s-label { font-size:12px; opacity:.85; }
        .stat-saya .s-val { font-size:28px; font-weight:700; line-height:1.1; }

        .header-saya {
            display:flex; align-items:center;
            justify-content:space-between; margin-bottom:16px;
        }
        .header-saya h3 { margin:0; color:#cdd9e5; font-size:15px; }
        .btn-buat {
            padding:8px 18px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:white; border:none; border-radius:8px;
            font-size:13px; font-weight:600; cursor:pointer;
            text-decoration:none; display:inline-block;
            transition:opacity .2s, transform .2s;
        }
        .btn-buat:hover { opacity:.9; transform:translateY(-1px); }

        .alert-saya {
            padding:10px 14px; border-radius:8px; font-size:13px;
            margin-bottom:14px;
        }
        .alert-saya.success { background:#1a4731; color:#6fcf97; border:1px solid #27ae60; }

        .tabel-saya-wrap {
            background:#0f2239; border-radius:12px;
            border:1px solid #2d5a8e; overflow:hidden;
        }
        .tabel-saya { width:100%; border-collapse:collapse; }
        .tabel-saya thead tr { background:#0a1929; }
        .tabel-saya thead th {
            padding:12px 14px; text-align:left; font-size:12px;
            color:#7a8ea8; font-weight:600; border-bottom:1px solid #2d5a8e;
        }
        .tabel-saya tbody tr { transition:background .15s; }
        .tabel-saya tbody tr:hover { background:#1a2e4a; }
        .tabel-saya tbody td {
            padding:12px 14px; font-size:13px; color:#cdd9e5;
            border-bottom:1px solid #1a3a5c; vertical-align:middle;
        }
        .tabel-saya tbody tr:last-child td { border-bottom:none; }

        .thumb-saya {
            width:56px; height:38px; object-fit:cover;
            border-radius:6px; border:1px solid #2d5a8e;
        }
        .thumb-placeholder-saya {
            width:56px; height:38px; background:#1a3a5c;
            border-radius:6px; display:flex; align-items:center;
            justify-content:center; font-size:16px;
        }
        .badge-gratis   { background:#1a4731; color:#6fcf97; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-berbayar { background:#3a2d0a; color:#f6c90e; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }

        .aksi-btns { display:flex; gap:6px; }
        .btn-edit-saya {
            padding:5px 12px; background:#1a2e4a; color:#667eea;
            border:1px solid #667eea; border-radius:6px; font-size:12px;
            font-weight:600; cursor:pointer; font-family:inherit;
            transition:background .2s; white-space:nowrap;
        }
        .btn-edit-saya:hover { background:#2d4a7a; }
        .btn-hapus-saya {
            padding:5px 12px; background:#2a1a1a; color:#ef4444;
            border:1px solid #ef4444; border-radius:6px; font-size:12px;
            font-weight:600; cursor:pointer; font-family:inherit;
            transition:background .2s; text-decoration:none; white-space:nowrap;
        }
        .btn-hapus-saya:hover { background:#3a1a1a; }

        /* ════ MODAL OVERLAY BARU — PENGGANTI MODAL LAMA ════ */
        .overlay-edit {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0px);
            transition: background 0.3s ease, backdrop-filter 0.3s ease;
            align-items: center;
            justify-content: center;
        }
        .overlay-edit.open {
            display: flex;
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: blur(4px);
        }
        .overlay-panel {
            background: #0d1e35;
            border: 1px solid #2d5a8e;
            border-radius: 20px;
            width: 100%;
            max-width: 520px;
            max-height: 92vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 24px 60px rgba(0,0,0,0.6);
            transform: translateY(32px) scale(0.97);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(.34,1.56,.64,1), opacity 0.25s ease;
            margin: 16px;
        }
        .overlay-edit.open .overlay-panel {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        /* Strip header di atas panel */
        .overlay-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px 16px;
            border-bottom: 1px solid #1a3a5c;
            position: sticky;
            top: 0;
            background: #0d1e35;
            z-index: 1;
            border-radius: 20px 20px 0 0;
        }
        .overlay-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .overlay-icon-wrap {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg,#667eea,#764ba2);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .overlay-title {
            font-size: 15px;
            font-weight: 700;
            color: #cdd9e5;
            margin: 0;
        }
        .overlay-subtitle {
            font-size: 11px;
            color: #7a8ea8;
            margin: 0;
        }
        .overlay-close {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: #1a2e4a;
            border: 1px solid #2d5a8e;
            color: #7a8ea8;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s, color .2s;
            font-family: inherit;
        }
        .overlay-close:hover { background: #2d1a1a; color: #ef4444; border-color: #ef4444; }

        /* Body form */
        .overlay-body { padding: 22px 24px; }

        .ov-fg { margin-bottom: 18px; }
        .ov-fg label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #7a8ea8;
            margin-bottom: 7px;
        }
        .ov-fg input[type=text],
        .ov-fg input[type=number],
        .ov-fg textarea,
        .ov-fg select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #2d5a8e;
            border-radius: 10px;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            background: #0a1929;
            color: #cdd9e5;
            box-sizing: border-box;
            transition: border-color .2s, box-shadow .2s;
        }
        .ov-fg input:focus,
        .ov-fg textarea:focus,
        .ov-fg select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.18);
        }
        .ov-fg textarea { min-height: 90px; resize: vertical; line-height: 1.6; }

        /* Tipe selector tombol radio visual */
        .tipe-selector {
            display: flex;
            gap: 10px;
        }
        .tipe-opt {
            flex: 1;
            position: relative;
        }
        .tipe-opt input[type=radio] { display: none; }
        .tipe-opt label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            border: 1.5px solid #2d5a8e;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #7a8ea8;
            background: #0a1929;
            transition: all .2s;
            text-transform: none;
            letter-spacing: 0;
        }
        .tipe-opt input[type=radio]:checked + label {
            border-color: #667eea;
            background: rgba(102,126,234,0.12);
            color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }
        .tipe-opt.berbayar input[type=radio]:checked + label {
            border-color: #f6c90e;
            background: rgba(246,201,14,0.1);
            color: #f6c90e;
            box-shadow: 0 0 0 3px rgba(246,201,14,0.12);
        }

        /* Harga field dengan animasi */
        .ov-harga-wrap {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height 0.35s ease, opacity 0.25s ease, margin 0.25s ease;
            margin-bottom: 0;
        }
        .ov-harga-wrap.visible {
            max-height: 100px;
            opacity: 1;
            margin-bottom: 18px;
        }
        .ov-harga-inner { padding-top: 2px; }

        /* Input harga dengan prefix Rp */
        .harga-input-wrap {
            position: relative;
        }
        .harga-prefix {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: #7a8ea8;
            font-weight: 600;
            pointer-events: none;
        }
        .harga-input-wrap input {
            padding-left: 36px !important;
        }

        /* Divider */
        .ov-divider {
            border: none;
            border-top: 1px solid #1a3a5c;
            margin: 4px 0 20px;
        }

        /* Footer action */
        .overlay-footer {
            display: flex;
            gap: 10px;
            padding: 0 24px 22px;
        }
        .btn-ov-save {
            flex: 1;
            padding: 11px 22px;
            background: linear-gradient(135deg,#667eea,#764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: opacity .2s, transform .15s;
            letter-spacing: .01em;
        }
        .btn-ov-save:hover { opacity: .9; transform: translateY(-1px); }
        .btn-ov-save:active { transform: translateY(0); }
        .btn-ov-cancel {
            padding: 11px 20px;
            background: #1a2e4a;
            color: #7a8ea8;
            border: 1px solid #2d5a8e;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background .2s;
        }
        .btn-ov-cancel:hover { background: #243d5a; color: #cdd9e5; }

        /* Scrollbar overlay panel */
        .overlay-panel::-webkit-scrollbar { width: 5px; }
        .overlay-panel::-webkit-scrollbar-track { background: transparent; }
        .overlay-panel::-webkit-scrollbar-thumb { background: #2d5a8e; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/img/logoo.png" width="90" alt="logo">
            <span>SkillNex</span>
        </div>
        <div class="menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="mycourse.php" class="active">My Course</a>
            <a href="message.php">Message</a>
            <a href="community.php">Community</a>
            <a href="forum.php">Forum</a>
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

    <div class="content">
        <div style="padding:16px 0 8px;">
            <input type="text" id="searchInput" placeholder="🔍 Cari kursus..."
                   class="search-box" oninput="filterCourse()">
        </div>
        <div class="tabs">
            <button class="<?= $active_tab === 'aktif' ? 'active' : '' ?>"
                    onclick="switchTab('aktif',this)">
                Belajar Aktif <?= count($kursus_aktif) ?>
            </button>
            <button class="<?= $active_tab === 'selesai' ? 'active' : '' ?>"
                    onclick="switchTab('selesai',this)">
                Riwayat <?= count($kursus_selesai) ?>
            </button>
            <button class="<?= $active_tab === 'saya' ? 'active' : '' ?>"
                    onclick="switchTab('saya',this)">
                Kursus Saya <?= $total_dibuat ?>
            </button>
        </div>

        <!-- TAB: Kursus Aktif (tidak diubah sama sekali) -->
        <div class="tab-content <?= $active_tab === 'aktif' ? 'active' : '' ?>" id="tab-aktif">
            <?php if (empty($kursus_aktif)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <p>Belum ada kursus aktif.<br>
                    Yuk daftar kursus di halaman <a href="payment.php" style="color:#667eea;">Payment</a>!</p>
                </div>
            <?php else: ?>
                <?php foreach ($kursus_aktif as $k):
                    $moduls = getModuls($pdo, $k['course_id'], $user_id);
                    $total_m = count($moduls);
                    $done_m  = count(array_filter($moduls, fn($m) => $m['selesai']));
                    $next_modul = null;
                    foreach ($moduls as $m) {
                        if (!$m['selesai']) { $next_modul = $m; break; }
                    }
                ?>
                    <div class="course-card" data-judul="<?= strtolower($k['judul']) ?>">
                        <div class="course-info">
                            <?php $thumb = !empty($k['thumbnail']) ? '../assets/'.$k['thumbnail'] : '../assets/img/default-profile.png'; ?>
                            <img src="<?= htmlspecialchars($thumb) ?>" alt="thumbnail"
                                 onerror="this.src='../assets/img/default-profile.png'">
                            <div class="course-details">
                                <p><strong><?= htmlspecialchars($k['judul']) ?></strong></p>
                                <p>Author: <?= htmlspecialchars($k['author'] ?? '-') ?></p>
                                <p style="font-size:12px;color:#7a8ea8;"><?= $done_m ?>/<?= $total_m ?> modul selesai</p>
                                <div class="progress-wrapper">
                                    <div class="progress-container">
                                        <div class="progress-fill" style="width:<?= $k['progress'] ?>%"></div>
                                    </div>
                                    <span style="font-size:12px;color:#cdd9e5;"><?= $k['progress'] ?>%</span>
                                </div>
                                <?php if ($total_m > 0): ?>
                                    <button class="modul-toggle"
                                            onclick="toggleModul('modul-<?= $k['course_id'] ?>')">
                                        📋 Lihat Modul
                                    </button>
                                    <div class="modul-list" id="modul-<?= $k['course_id'] ?>">
                                        <?php foreach ($moduls as $i => $m):
                                            $is_done   = (bool)$m['selesai'];
                                            $is_active = !$is_done && ($i === 0 || (bool)$moduls[$i-1]['selesai']);
                                            $is_locked = !$is_done && !$is_active;
                                        ?>
                                            <div class="modul-item <?= $is_locked ? 'modul-locked' : '' ?>">
                                                <div class="modul-num <?= $is_done ? 'done' : ($is_active ? 'active-mod' : '') ?>">
                                                    <?= $is_done ? '✓' : ($i + 1) ?>
                                                </div>
                                                <span class="modul-name <?= $is_done ? 'done-text' : '' ?>">
                                                    <?= htmlspecialchars($m['judul']) ?>
                                                </span>
                                                <span class="badge-tipe <?= $m['tipe'] ?>">
                                                    <?= $m['tipe'] ?>
                                                </span>
                                                <?php if ($is_active): ?>
                                                    <form method="POST" style="margin:0;">
                                                        <input type="hidden" name="module_id" value="<?= $m['id'] ?>">
                                                        <input type="hidden" name="course_id" value="<?= $k['course_id'] ?>">
                                                        <input type="hidden" name="selesai_modul" value="1">
                                                        <button type="submit" class="btn-selesai-modul">Selesai ✓</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <p class="next-session-label"><strong>Next Session</strong></p>
                            <p class="next-session-val">
                                <?= $next_modul ? htmlspecialchars($next_modul['judul']) : '🎉 Semua selesai!' ?>
                            </p>
                        </div>

                        <?php $author_id = $k['author_id'] ?? null;
                        if ($author_id && $author_id != $user_id): ?>
                            <a href="message.php?with=<?= $author_id ?>" class="chat-btn-link">
                                <button class="chat-btn">💬 Chat</button>
                            </a>
                        <?php elseif ($author_id == $user_id): ?>
                            <button class="chat-btn chat-btn-self" disabled>💬 Chat</button>
                        <?php else: ?>
                            <button class="chat-btn" onclick="alert('Mentor belum tersedia.')">💬 Chat</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB: Riwayat (tidak diubah sama sekali) -->
        <div class="tab-content <?= $active_tab === 'selesai' ? 'active' : '' ?>" id="tab-selesai">
            <?php if (empty($kursus_selesai)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🏆</div>
                    <p>Belum ada kursus yang selesai.<br>Semangat belajar!</p>
                </div>
            <?php else: ?>
                <?php foreach ($kursus_selesai as $k): ?>
                    <div class="course-card" data-judul="<?= strtolower($k['judul']) ?>">
                        <div class="course-info">
                            <?php $thumb = !empty($k['thumbnail']) ? '../assets/'.$k['thumbnail'] : '../assets/img/logoo.png'; ?>
                            <img src="<?= htmlspecialchars($thumb) ?>" alt="thumbnail"
                                 onerror="this.src='../assets/img/logoo.png'">
                            <div class="course-details">
                                <p><strong><?= htmlspecialchars($k['judul']) ?></strong></p>
                                <p>Author: <?= htmlspecialchars($k['author'] ?? '-') ?></p>
                                <span class="badge-selesai">✅ Selesai</span>
                                <div class="progress-container" style="margin-top:8px;">
                                    <div class="progress-fill" style="width:100%"></div>
                                </div>
                                <p>100%</p>
                            </div>
                        </div>
                        <div>
                            <p><strong>Selesai</strong></p>
                            <p><?= date('d/m/Y', strtotime($k['created_at'])) ?></p>
                        </div>
                        <?php $author_id = $k['author_id'] ?? null;
                        if ($author_id && $author_id != $user_id): ?>
                            <a href="message.php?with=<?= $author_id ?>" class="chat-btn-link">
                                <button class="chat-btn">💬 Chat</button>
                            </a>
                        <?php else: ?>
                            <button class="chat-btn" style="background:#27ae60;">Selesai 🎉</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ════ TAB: Kursus Saya ════ -->
        <div class="tab-content <?= $active_tab === 'saya' ? 'active' : '' ?>" id="tab-saya">

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert-saya success">✅ Kursus berhasil dihapus.</div>
            <?php endif; ?>
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert-saya success">✅ Kursus berhasil diperbarui.</div>
            <?php endif; ?>

            <div class="stat-saya">
                <div class="s-icon">📚</div>
                <div>
                    <div class="s-label">Total Kursus Dibuat</div>
                    <div class="s-val"><?= $total_dibuat ?></div>
                </div>
            </div>

            <div class="header-saya">
                <h3>Kursus yang Saya Buat</h3>
                <a href="createcourse.php" class="btn-buat">+ Buat Kursus Baru</a>
            </div>

            <?php if ($total_dibuat === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>Kamu belum membuat kursus apapun.<br>Mulai bagikan ilmumu sekarang!</p>
                    <a href="createcourse.php" class="btn-buat" style="margin-top:12px;display:inline-block;">
                        Buat Kursus Pertama
                    </a>
                </div>
            <?php else: ?>
                <div class="tabel-saya-wrap">
                    <table class="tabel-saya">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Thumbnail</th>
                                <th>Judul Kursus</th>
                                <th>Tipe</th>
                                <th>Harga</th>
                                <th>Modul</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kursus_dibuat as $i => $c): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php if (!empty($c['thumbnail'])): ?>
                                        <img src="../assets/<?= htmlspecialchars($c['thumbnail']) ?>"
                                             class="thumb-saya"
                                             onerror="this.parentElement.innerHTML='<div class=thumb-placeholder-saya>🖼️</div>'">
                                    <?php else: ?>
                                        <div class="thumb-placeholder-saya">🖼️</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($c['judul']) ?></strong></td>
                                <td><span class="badge-<?= $c['tipe'] ?>"><?= ucfirst($c['tipe']) ?></span></td>
                                <td>
                                    <?= $c['tipe'] === 'berbayar'
                                        ? 'Rp ' . number_format($c['harga'], 0, ',', '.')
                                        : '—' ?>
                                </td>
                                <td><?= $c['jumlah_modul'] ?> modul</td>
                                <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <div class="aksi-btns">
                                        <!-- TOMBOL EDIT: data-* attribute, aman dari karakter khusus -->
                                        <button class="btn-edit-saya"
                                            data-id="<?= $c['id'] ?>"
                                            data-judul="<?= htmlspecialchars($c['judul'], ENT_QUOTES) ?>"
                                            data-deskripsi="<?= htmlspecialchars($c['deskripsi'] ?? '', ENT_QUOTES) ?>"
                                            data-tipe="<?= htmlspecialchars($c['tipe'], ENT_QUOTES) ?>"
                                            data-harga="<?= (float)$c['harga'] ?>"
                                            onclick="bukaOverlayEdit(this)">
                                            ✏️ Edit
                                        </button>
                                        <a href="mycourse.php?hapus=<?= $c['id'] ?>&tab=saya"
                                           class="btn-hapus-saya"
                                           onclick="return confirm('Hapus kursus \'<?= addslashes(htmlspecialchars($c['judul'])) ?>\'?\n\nSemua modul juga akan terhapus.')">
                                           🗑️ Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <!-- ════ END TAB KURSUS SAYA ════ -->
    </div>

    <!-- ════ OVERLAY EDIT KURSUS ════ -->
    <div class="overlay-edit" id="overlayEdit" onclick="handleOverlayClick(event)">
        <div class="overlay-panel" id="overlayPanel">

            <!-- Header -->
            <div class="overlay-header">
                <div class="overlay-header-left">
                    <div class="overlay-icon-wrap">✏️</div>
                    <div>
                        <p class="overlay-title">Edit Kursus</p>
                        <p class="overlay-subtitle" id="ovSubtitle">Perbarui informasi kursus kamu</p>
                    </div>
                </div>
                <button class="overlay-close" onclick="tutupOverlayEdit()" title="Tutup">✕</button>
            </div>

            <!-- Form -->
            <form method="POST" action="mycourse.php?tab=saya" id="formEditKursus">
                <input type="hidden" name="edit_id" id="ovEditId">

                <div class="overlay-body">

                    <!-- Judul -->
                    <div class="ov-fg">
                        <label>Judul Kursus *</label>
                        <input type="text" name="judul" id="ovJudul"
                               required placeholder="Masukkan judul kursus...">
                    </div>

                    <!-- Deskripsi -->
                    <div class="ov-fg">
                        <label>Deskripsi Kursus</label>
                        <textarea name="deskripsi" id="ovDeskripsi"
                                  placeholder="Jelaskan apa yang akan dipelajari..."></textarea>
                    </div>

                    <hr class="ov-divider">

                    <!-- Tipe — radio visual -->
                    <div class="ov-fg">
                        <label>Tipe Kursus</label>
                        <div class="tipe-selector">
                            <div class="tipe-opt gratis">
                                <input type="radio" name="tipe" id="tipeGratis" value="gratis"
                                       onchange="handleTipeChange('gratis')">
                                <label for="tipeGratis">🎁 Gratis</label>
                            </div>
                            <div class="tipe-opt berbayar">
                                <input type="radio" name="tipe" id="tipeBerbayar" value="berbayar"
                                       onchange="handleTipeChange('berbayar')">
                                <label for="tipeBerbayar">💰 Berbayar</label>
                            </div>
                        </div>
                    </div>

                    <!-- Harga — muncul animasi jika berbayar -->
                    <div class="ov-harga-wrap" id="ovHargaWrap">
                        <div class="ov-fg ov-harga-inner">
                            <label>Harga (Rp)</label>
                            <div class="harga-input-wrap">
                                <span class="harga-prefix">Rp</span>
                                <input type="number" name="harga" id="ovHarga"
                                       min="0" step="1000" placeholder="Contoh: 75000">
                            </div>
                        </div>
                    </div>

                </div><!-- /overlay-body -->

                <!-- Footer -->
                <div class="overlay-footer">
                    <button type="submit" class="btn-ov-save">💾 Simpan Perubahan</button>
                    <button type="button" class="btn-ov-cancel" onclick="tutupOverlayEdit()">Batal</button>
                </div>
            </form>

        </div><!-- /overlay-panel -->
    </div>
    <!-- ════ END OVERLAY ════ -->

    <script>
        /* ── Tab switching (tidak diubah) ── */
        function switchTab(tab, btn) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            btn.classList.add('active');
        }

        function toggleModul(id) {
            const el = document.getElementById(id);
            el.classList.toggle('open');
        }

        function filterCourse() {
            const keyword = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.course-card').forEach(card => {
                const judul = card.getAttribute('data-judul') || '';
                card.style.display = judul.includes(keyword) ? '' : 'none';
            });
        }

        /* ── Auto tab dari URL ── */
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam  = urlParams.get('tab');
        if (tabParam) {
            const tabEl = document.getElementById('tab-' + tabParam);
            if (tabEl) {
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
                tabEl.classList.add('active');
                document.querySelectorAll('.tabs button').forEach(b => {
                    if (b.getAttribute('onclick') && b.getAttribute('onclick').includes("'" + tabParam + "'")) {
                        b.classList.add('active');
                    }
                });
            }
        }

        /* ── Auto hide alert ── */
        setTimeout(function() {
            document.querySelectorAll('.alert-saya').forEach(a => {
                a.style.transition = 'opacity .5s';
                a.style.opacity = '0';
                setTimeout(() => a.remove(), 500);
            });
        }, 4000);

        /* ════ OVERLAY EDIT — fungsi baru ════ */

        function bukaOverlayEdit(btn) {
            const id        = btn.dataset.id;
            const judul     = btn.dataset.judul;
            const deskripsi = btn.dataset.deskripsi;
            const tipe      = btn.dataset.tipe;
            const harga     = btn.dataset.harga;

            /* Isi field */
            document.getElementById('ovEditId').value    = id;
            document.getElementById('ovJudul').value     = judul;
            document.getElementById('ovDeskripsi').value = deskripsi;
            document.getElementById('ovHarga').value     = harga;
            document.getElementById('ovSubtitle').textContent = judul.length > 36
                ? judul.substring(0, 36) + '…'
                : judul;

            /* Set radio tipe */
            if (tipe === 'berbayar') {
                document.getElementById('tipeBerbayar').checked = true;
            } else {
                document.getElementById('tipeGratis').checked = true;
            }
            handleTipeChange(tipe);

            /* Buka overlay */
            document.getElementById('overlayEdit').classList.add('open');
            document.body.style.overflow = 'hidden';

            /* Fokus ke judul setelah animasi */
            setTimeout(() => document.getElementById('ovJudul').focus(), 280);
        }

        function tutupOverlayEdit() {
            const overlay = document.getElementById('overlayEdit');
            const panel   = document.getElementById('overlayPanel');

            /* Animasi keluar */
            panel.style.transform  = 'translateY(24px) scale(0.97)';
            panel.style.opacity    = '0';
            overlay.style.background      = 'rgba(0,0,0,0)';
            overlay.style.backdropFilter  = 'blur(0px)';

            setTimeout(() => {
                overlay.classList.remove('open');
                panel.style.transform = '';
                panel.style.opacity   = '';
                overlay.style.background     = '';
                overlay.style.backdropFilter = '';
                document.body.style.overflow = '';
            }, 280);
        }

        /* Klik area gelap di luar panel = tutup */
        function handleOverlayClick(e) {
            if (e.target === document.getElementById('overlayEdit')) {
                tutupOverlayEdit();
            }
        }

        /* Animasi field harga */
        function handleTipeChange(val) {
            const wrap = document.getElementById('ovHargaWrap');
            if (val === 'berbayar') {
                wrap.classList.add('visible');
                document.getElementById('ovHarga').required = true;
            } else {
                wrap.classList.remove('visible');
                document.getElementById('ovHarga').required = false;
                document.getElementById('ovHarga').value = 0;
            }
        }

        /* ESC untuk tutup overlay */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('overlayEdit');
                if (overlay.classList.contains('open')) tutupOverlayEdit();
            }
        });
    </script>
</body>
</html>