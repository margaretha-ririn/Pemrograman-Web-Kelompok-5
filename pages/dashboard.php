<?php
session_start();
require '../config/db.php';

date_default_timezone_set('Asia/Jakarta');

function tanggalIndonesia($timestamp = null) {
    $timestamp = $timestamp ?? time();

    $hari = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];

    $bulan = [
        'Jan' => 'Jan',
        'Feb' => 'Feb',
        'Mar' => 'Mar',
        'Apr' => 'Apr',
        'May' => 'Mei',
        'Jun' => 'Jun',
        'Jul' => 'Jul',
        'Aug' => 'Agu',
        'Sep' => 'Sep',
        'Oct' => 'Okt',
        'Nov' => 'Nov',
        'Dec' => 'Des'
    ];

    return $hari[date('l', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulan[date('M', $timestamp)] . ' ' . date('Y', $timestamp);
}

$jam_sekarang = (int)date('H');

if ($jam_sekarang >= 4 && $jam_sekarang < 11) {
    $greeting = '🌅 Selamat Pagi,';
} elseif ($jam_sekarang >= 11 && $jam_sekarang < 15) {
    $greeting = '☀️ Selamat Siang,';
} elseif ($jam_sekarang >= 15 && $jam_sekarang < 18) {
    $greeting = '🌇 Selamat Sore,';
} else {
    $greeting = '🌙 Selamat Malam,';
}

$current_time = date('H:i');
$current_date = tanggalIndonesia();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Jumlah kursus aktif
$jml_aktif = $pdo->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND progress < 100");
$jml_aktif->execute([$user_id]);
$total_aktif = $jml_aktif->fetch()['total'];

// Jumlah kursus selesai
$jml_selesai = $pdo->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ? AND progress = 100");
$jml_selesai->execute([$user_id]);
$total_selesai = $jml_selesai->fetch()['total'];

// Pesan belum dibaca
$jml_pesan = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE penerima_id = ? AND dibaca = 0");
$jml_pesan->execute([$user_id]);
$total_pesan = $jml_pesan->fetch()['total'];

// Jumlah post forum milik user
$jml_forum = $pdo->prepare("SELECT COUNT(*) as total FROM forum_posts WHERE user_id = ?");
$jml_forum->execute([$user_id]);
$total_forum = $jml_forum->fetch()['total'];

// Kursus aktif terbaru (max 3)
$kursus_terbaru = $pdo->prepare("
    SELECT e.progress, c.judul, c.thumbnail, c.author, c.id as course_id
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.progress < 100
    ORDER BY e.created_at DESC LIMIT 3
");
$kursus_terbaru->execute([$user_id]);
$kursus_list = $kursus_terbaru->fetchAll();

// Diskusi forum terbaru (max 4)
$forum_terbaru = $pdo->query("
    SELECT fp.id, fp.judul, fp.kategori, fp.created_at,
           u.nama, u.foto_profil,
           COUNT(fc.id) as jumlah_komentar
    FROM forum_posts fp
    JOIN users u ON fp.user_id = u.id
    LEFT JOIN forum_comments fc ON fc.post_id = fp.id
    GROUP BY fp.id
    ORDER BY fp.created_at DESC LIMIT 4
")->fetchAll();

// Pesan terbaru (max 3)
$pesan_terbaru = $pdo->prepare("
    SELECT m.pesan, m.created_at, m.dibaca, u.nama, u.foto_profil, u.id as sender_id
    FROM messages m
    JOIN users u ON m.pengirim_id = u.id
    WHERE m.penerima_id = ?
    ORDER BY m.created_at DESC LIMIT 3
");
$pesan_terbaru->execute([$user_id]);
$pesan_list = $pesan_terbaru->fetchAll();

// Foto profil
$foto_profil = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? '../assets/' . $user['foto_profil']
    : '../assets/img/default-profile.png';

// Kalender
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$nama_bulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
$hari_pertama = mktime(0,0,0,$bulan,1,$tahun);
$total_hari   = date('t', $hari_pertama);
$mulai_hari   = date('w', $hari_pertama);
$hari_ini     = (int)date('d');
$bulan_ini    = (int)date('m');
$tahun_ini    = (int)date('Y');
$prev_bulan = $bulan-1; $prev_tahun = $tahun;
if ($prev_bulan < 1) { $prev_bulan = 12; $prev_tahun--; }
$next_bulan = $bulan+1; $next_tahun = $tahun;
if ($next_bulan > 12) { $next_bulan = 1; $next_tahun++; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SkillNex</title>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        /* ===== LAYOUT OVERRIDE ===== */
        .main-content {
            margin-left: 180px;
            margin-top: 56px;
            padding: 24px 12px; /* tightened to sidebar */
            background: #f1f5f9;
            min-height: calc(100vh - 56px);
        }

        /* ===== SIDEBAR ===== */
        .logout-btn {
            display: block; text-align: center;
            background: #e53e3e; color: white; padding: 10px;
            border-radius: 8px; text-decoration: none;
            font-size: 14px; font-weight: 600;
            margin: 20px 20px 0; transition: background 0.3s;
        }
        .logout-btn:hover { background: #c53030; }
        .navbar-user { margin-left: auto; font-size: 14px; color: white; display: flex; align-items: center; gap: 10px; }
        .navbar-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.4); }

        /* ===== WELCOME CARD ===== */
        .welcome-card {
            background: linear-gradient(135deg, #0f2239 0%, #1e3a5f 60%, #2d5a8e 100%);
            border-radius: 18px; padding: 28px 32px;
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 24px; color: white;
            box-shadow: 0 4px 20px rgba(15,34,57,0.3);
            position: relative; overflow: hidden;
        }
        .welcome-card::before {
            content: ''; position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            background: rgba(102,126,234,0.15);
            border-radius: 50%;
        }
        .welcome-avatar {
            width: 72px; height: 72px; border-radius: 50%;
            object-fit: cover; border: 3px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
        }
        .welcome-text h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .welcome-text p { font-size: 14px; color: rgba(255,255,255,0.7); }
        .welcome-time { margin-left: auto; text-align: right; }
        .welcome-time .time { font-size: 28px; font-weight: 700; color: #5b9cf5; }
        .welcome-time .date { font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 4px; }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 16px; margin-bottom: 24px;
        }
        .stat-card {
            background: white; border-radius: 14px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            display: flex; align-items: center; gap: 14px;
            text-decoration: none; color: inherit;
            transition: all 0.3s; border: 1px solid #f1f5f9;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); border-color: #667eea; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .stat-icon.blue { background: #eef2ff; }
        .stat-icon.green { background: #e1f5ee; }
        .stat-icon.orange { background: #fef3e2; }
        .stat-icon.purple { background: #f5eeff; }
        .stat-number { font-size: 26px; font-weight: 700; color: #1e293b; line-height: 1; }
        .stat-label { font-size: 12px; color: #64748b; margin-top: 3px; }

        /* ===== GRID LAYOUT ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 320px;
            gap: 20px;
        }

        /* ===== SECTION CARD ===== */
        .section-card {
            background: white; border-radius: 16px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .section-card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px; padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .section-card-header h3 { font-size: 15px; font-weight: 700; color: #1e293b; }
        .section-card-header a { font-size: 12px; color: #667eea; text-decoration: none; font-weight: 600; }
        .section-card-header a:hover { text-decoration: underline; }

        /* ===== KURSUS ITEM ===== */
        .kursus-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0; border-bottom: 1px solid #f8fafc;
        }
        .kursus-item:last-child { border-bottom: none; }
        .kursus-thumb {
            width: 48px; height: 48px; border-radius: 8px;
            object-fit: cover; flex-shrink: 0; background: #e2e8f0;
        }
        .kursus-info { flex: 1; min-width: 0; }
        .kursus-judul { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .kursus-author { font-size: 11px; color: #94a3b8; margin-bottom: 6px; }
        .progress-bar-wrap { background: #f1f5f9; border-radius: 6px; height: 6px; }
        .progress-bar-fill { height: 6px; border-radius: 6px; background: linear-gradient(135deg, #667eea, #764ba2); }
        .kursus-pct { font-size: 11px; color: #667eea; font-weight: 700; margin-top: 3px; }

        /* ===== FORUM ITEM ===== */
        .forum-item {
            padding: 10px 0; border-bottom: 1px solid #f8fafc;
            text-decoration: none; display: block;
        }
        .forum-item:last-child { border-bottom: none; }
        .forum-item:hover .forum-judul { color: #667eea; }
        .forum-kategori { display: inline-block; background: #eef2ff; color: #667eea; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; margin-bottom: 4px; }
        .forum-judul { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 4px; transition: color 0.2s; }
        .forum-meta { font-size: 11px; color: #94a3b8; display: flex; gap: 10px; }

        /* ===== PESAN ITEM ===== */
        .pesan-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 0; border-bottom: 1px solid #f8fafc;
            text-decoration: none; color: inherit;
        }
        .pesan-item:last-child { border-bottom: none; }
        .pesan-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .pesan-info { flex: 1; min-width: 0; }
        .pesan-nama { font-size: 13px; font-weight: 600; color: #1e293b; }
        .pesan-preview { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pesan-unread { width: 8px; height: 8px; background: #667eea; border-radius: 50%; flex-shrink: 0; }

        /* ===== KALENDER ===== */
        .kalender-card { background: #0f2239; color: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .kalender-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .kalender-nav a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 18px; }
        .kalender-nav a:hover { color: white; }
        .kalender-nav strong { font-size: 14px; }
        .kalender-table { width: 100%; border-collapse: collapse; }
        .kalender-table td { text-align: center; padding: 5px 2px; font-size: 12px; color: rgba(255,255,255,0.7); border-radius: 6px; }
        .kalender-table td.header { font-weight: 700; color: #5b9cf5; font-size: 11px; }
        .kalender-table td.today { background: #667eea; color: white; font-weight: 700; border-radius: 50%; }

        /* ===== SHORTCUT ===== */
        .shortcut-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 16px; }
        .shortcut-item { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 6px; background: rgba(255,255,255,0.06); border-radius: 10px; text-decoration: none; color: white; transition: all 0.2s; font-size: 11px; }
        .shortcut-item:hover { background: rgba(255,255,255,0.12); transform: translateY(-2px); }
        .shortcut-icon { font-size: 20px; }

        /* ===== EMPTY ===== */
        .empty-mini { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; }

        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/img/logoo.png" width="90" alt="logo">
            <span>SkillNex</span>
        </div>
        <div class="menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="mycourse.php">My Course</a>
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
            <img src="<?= htmlspecialchars($foto_profil) ?>" class="navbar-avatar"
                 onerror="this.src='../assets/img/default-profile.png'">
            <strong><?= htmlspecialchars($user['nama']) ?></strong>
        </div>
    </div>

    <div class="main-content">

        <!-- Welcome Card -->
        <div class="welcome-card">
            <img src="<?= htmlspecialchars($foto_profil) ?>" class="welcome-avatar"
                 onerror="this.src='../assets/img/default-profile.png'">
            <div class="welcome-text">
                <h2>
                    <span id="greetingText"><?= $greeting ?></span>
                    <?= htmlspecialchars($user['nama']) ?>!
                </h2>
                <p>
                    <?php if ($total_aktif > 0): ?>
                        Kamu punya <strong><?= $total_aktif ?> kursus aktif</strong> yang sedang berjalan.
                        <?php if ($total_pesan > 0): ?>
                            Ada <strong><?= $total_pesan ?> pesan baru</strong> untukmu!
                        <?php endif; ?>
                    <?php else: ?>
                        Belum ada kursus aktif. Yuk mulai belajar skill baru hari ini!
                    <?php endif; ?>
                </p>
            </div>
            <div class="welcome-time">
                <div class="time" id="liveTime"><?= $current_time ?></div>
                <div class="date" id="liveDate"><?= $current_date ?></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <a href="mycourse.php" class="stat-card">
                <div class="stat-icon blue">📚</div>
                <div>
                    <div class="stat-number"><?= $total_aktif ?></div>
                    <div class="stat-label">Kursus Aktif</div>
                </div>
            </a>
            <a href="mycourse.php?tab=selesai" class="stat-card">
                <div class="stat-icon green">🏆</div>
                <div>
                    <div class="stat-number"><?= $total_selesai ?></div>
                    <div class="stat-label">Kursus Selesai</div>
                </div>
            </a>
            <a href="message.php" class="stat-card">
                <div class="stat-icon orange">💬</div>
                <div>
                    <div class="stat-number"><?= $total_pesan ?></div>
                    <div class="stat-label">Pesan Belum Dibaca</div>
                </div>
            </a>
            <a href="forum.php" class="stat-card">
                <div class="stat-icon purple">📝</div>
                <div>
                    <div class="stat-number"><?= $total_forum ?></div>
                    <div class="stat-label">Diskusi Dibuat</div>
                </div>
            </a>
        </div>

        <!-- Main Grid -->
        <div class="dashboard-grid">

            <!-- Kursus Aktif -->
            <div class="section-card">
                <div class="section-card-header">
                    <h3>📚 Kursus Aktif</h3>
                    <a href="mycourse.php">Lihat semua →</a>
                </div>
                <?php if (empty($kursus_list)): ?>
                    <div class="empty-mini">
                        Belum ada kursus.<br>
                        <a href="payment.php" style="color:#667eea;">Daftar kursus sekarang →</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($kursus_list as $k): ?>
                        <?php $t = !empty($k['thumbnail']) ? '../assets/'.$k['thumbnail'] : '../assets/img/logoo.png'; ?>
                        <div class="kursus-item">
                            <img src="<?= htmlspecialchars($t) ?>" class="kursus-thumb" onerror="this.src='../assets/img/logoo.png'">
                            <div class="kursus-info">
                                <div class="kursus-judul"><?= htmlspecialchars($k['judul']) ?></div>
                                <div class="kursus-author">👤 <?= htmlspecialchars($k['author'] ?? '-') ?></div>
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill" style="width:<?= $k['progress'] ?>%"></div>
                                </div>
                                <div class="kursus-pct"><?= $k['progress'] ?>% selesai</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Diskusi Forum Terbaru -->
            <div class="section-card">
                <div class="section-card-header">
                    <h3>💬 Diskusi Terbaru</h3>
                    <a href="forum.php">Lihat semua →</a>
                </div>
                <?php if (empty($forum_terbaru)): ?>
                    <div class="empty-mini">
                        Belum ada diskusi.<br>
                        <a href="forum.php" style="color:#667eea;">Mulai diskusi →</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($forum_terbaru as $f): ?>
                        <a href="forum_detail.php?id=<?= $f['id'] ?>" class="forum-item">
                            <span class="forum-kategori"><?= htmlspecialchars($f['kategori']) ?></span>
                            <div class="forum-judul"><?= htmlspecialchars($f['judul']) ?></div>
                            <div class="forum-meta">
                                <span>👤 <?= htmlspecialchars($f['nama']) ?></span>
                                <span>💬 <?= $f['jumlah_komentar'] ?> komentar</span>
                                <span>🕐 <?= date('d M', strtotime($f['created_at'])) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar kanan: Kalender + Pesan + Shortcut -->
            <div style="display:flex; flex-direction:column; gap:16px;">

                <!-- Kalender -->
                <div class="kalender-card">
                    <div class="kalender-nav">
                        <a href="?bulan=<?= $prev_bulan ?>&tahun=<?= $prev_tahun ?>">‹</a>
                        <strong><?= $nama_bulan[$bulan] . ' ' . $tahun ?></strong>
                        <a href="?bulan=<?= $next_bulan ?>&tahun=<?= $next_tahun ?>">›</a>
                    </div>
                    <table class="kalender-table">
                        <tr>
                            <?php foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $h): ?>
                                <td class="header"><?= $h ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php
                        $hari = 1; $kolom = 0;
                        echo "<tr>";
                        for ($i = 0; $i < $mulai_hari; $i++) { echo "<td></td>"; $kolom++; }
                        while ($hari <= $total_hari) {
                            $isToday = ($hari == $hari_ini && $bulan == $bulan_ini && $tahun == $tahun_ini);
                            echo "<td class='" . ($isToday ? 'today' : '') . "'>$hari</td>";
                            $kolom++;
                            if ($kolom == 7 && $hari < $total_hari) { echo "</tr><tr>"; $kolom = 0; }
                            $hari++;
                        }
                        while ($kolom > 0 && $kolom < 7) { echo "<td></td>"; $kolom++; }
                        echo "</tr>";
                        ?>
                    </table>

                    <!-- Shortcut menu -->
                    <div class="shortcut-grid" style="margin-top:16px;">
                        <a href="mycourse.php" class="shortcut-item">
                            <span class="shortcut-icon">📚</span>
                            <span>Course</span>
                        </a>
                        <a href="forum.php" class="shortcut-item">
                            <span class="shortcut-icon">💬</span>
                            <span>Forum</span>
                        </a>
                        <a href="message.php" class="shortcut-item">
                            <span class="shortcut-icon">✉️</span>
                            <span>Pesan</span>
                        </a>
                        <a href="community.php" class="shortcut-item">
                            <span class="shortcut-icon">👥</span>
                            <span>Komunitas</span>
                        </a>
                        <a href="payment.php" class="shortcut-item">
                            <span class="shortcut-icon">💳</span>
                            <span>Payment</span>
                        </a>
                        <a href="profile.php" class="shortcut-item">
                            <span class="shortcut-icon">👤</span>
                            <span>Profil</span>
                        </a>
                        <a href="livechat.php" class="shortcut-item">
                            <span class="shortcut-icon">🎙️</span>
                            <span>Live Chat</span>
                        </a>
                        <a href="forum.php" class="shortcut-item">
                            <span class="shortcut-icon">✏️</span>
                            <span>Diskusi</span>
                        </a>
                    </div>
                </div>

                <!-- Pesan Terbaru -->
                <div class="section-card">
                    <div class="section-card-header">
                        <h3>✉️ Pesan Terbaru</h3>
                        <a href="message.php">Lihat semua →</a>
                    </div>
                    <?php if (empty($pesan_list)): ?>
                        <div class="empty-mini">Belum ada pesan masuk.</div>
                    <?php else: ?>
                        <?php foreach ($pesan_list as $pm): ?>
                            <?php $fp = !empty($pm['foto_profil']) && $pm['foto_profil'] !== 'default.jpg' ? '../assets/'.$pm['foto_profil'] : 'assets/img/logoo.png'; ?>
                            <a href="message.php?with=<?= $pm['sender_id'] ?>" class="pesan-item">
                                <img src="<?= htmlspecialchars($fp) ?>" class="pesan-avatar" onerror="this.src='../assets/img/logoo.png'">
                                <div class="pesan-info">
                                    <div class="pesan-nama"><?= htmlspecialchars($pm['nama']) ?></div>
                                    <div class="pesan-preview"><?= htmlspecialchars(mb_strimwidth($pm['pesan'], 0, 40, '...')) ?></div>
                                </div>
                                <?php if (!$pm['dibaca']): ?>
                                    <div class="pesan-unread"></div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

<script>
function updateDashboardClock() {
    const now = new Date();

    const timeEl = document.getElementById('liveTime');
    const dateEl = document.getElementById('liveDate');
    const greetingEl = document.getElementById('greetingText');

    const jam = now.getHours();
    const menit = String(now.getMinutes()).padStart(2, '0');

    if (timeEl) {
        timeEl.textContent = String(jam).padStart(2, '0') + ':' + menit;
    }

    const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    if (dateEl) {
        dateEl.textContent =
            namaHari[now.getDay()] + ', ' +
            String(now.getDate()).padStart(2, '0') + ' ' +
            namaBulan[now.getMonth()] + ' ' +
            now.getFullYear();
    }

    if (greetingEl) {
        if (jam >= 4 && jam < 11) {
            greetingEl.textContent = '🌅 Selamat Pagi,';
        } else if (jam >= 11 && jam < 15) {
            greetingEl.textContent = '☀️ Selamat Siang,';
        } else if (jam >= 15 && jam < 18) {
            greetingEl.textContent = '🌇 Selamat Sore,';
        } else {
            greetingEl.textContent = '🌙 Selamat Malam,';
        }
    }
}

updateDashboardClock();
setInterval(updateDashboardClock, 1000);
</script>

</body>
</html>