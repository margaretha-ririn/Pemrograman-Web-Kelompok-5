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

// === JOIN ===
if (isset($_GET['join'])) {
    $cid = (int)$_GET['join'];
    $cek = $pdo->prepare("SELECT id FROM community_members WHERE community_id = ? AND user_id = ?");
    $cek->execute([$cid, $user_id]);
    if ($cek->rowCount() === 0) {
        $pdo->prepare("INSERT INTO community_members (community_id, user_id) VALUES (?,?)")->execute([$cid, $user_id]);
        $pdo->prepare("UPDATE communities SET total_member = total_member + 1 WHERE id = ?")->execute([$cid]);
    }
    header("Location: community_detail.php?id=$cid");
    exit;
}

// === LEAVE ===
if (isset($_GET['leave'])) {
    $cid = (int)$_GET['leave'];
    $pdo->prepare("DELETE FROM community_members WHERE community_id = ? AND user_id = ?")->execute([$cid, $user_id]);
    $pdo->prepare("UPDATE communities SET total_member = total_member - 1 WHERE id = ? AND total_member > 0")->execute([$cid]);
    header("Location: community.php");
    exit;
}

// Komunitas yang diikuti user
$my = $pdo->prepare("SELECT community_id FROM community_members WHERE user_id = ?");
$my->execute([$user_id]);
$my_ids = array_column($my->fetchAll(), 'community_id');

// Filter kategori
$filter = $_GET['kategori'] ?? '';
if ($filter) {
    $all = $pdo->prepare("SELECT * FROM communities WHERE kategori = ? ORDER BY total_member DESC");
    $all->execute([$filter]);
} else {
    $all = $pdo->query("SELECT * FROM communities ORDER BY total_member DESC");
}
$communities = $all->fetchAll();

$foto = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? '../assets/' . $user['foto_profil'] : '../assets/img/default-profile.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - SkillNex</title>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/community.css">
<style>
.navbar-user{margin-left:auto;font-size:14px;color:white;}

/* Grid komunitas */
.comm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-top:16px;}
.comm-card{background:#0f2239;border:1px solid #1e3a5f;border-radius:14px;overflow:hidden;transition:all 0.3s;color:white;}
.comm-card:hover{border-color:#2d5a8e;transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.2);}
.comm-banner{width:100%;height:100px;object-fit:cover;background:linear-gradient(135deg,#1e3a5f,#2d5a8e);}
.comm-banner-placeholder{width:100%;height:100px;background:linear-gradient(135deg,#1e3a5f,#2d5a8e);display:flex;align-items:center;justify-content:center;font-size:36px;}
.comm-body{padding:16px;}
.comm-kat{display:inline-block;background:#1e3a5f;color:#5b9cf5;border:1px solid #2d5a8e;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:8px;}
.comm-nama{font-size:15px;font-weight:700;color:#fff;margin-bottom:4px;}
.comm-desc{font-size:12px;color:#7a8ea8;margin-bottom:12px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.comm-meta{display:flex;justify-content:space-between;align-items:center;}
.comm-member{font-size:12px;color:#7a8ea8;}
.btn-join{padding:7px 18px;border:none;border-radius:20px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.3s;text-decoration:none;display:inline-block;}
.btn-join:hover{transform:scale(1.05);opacity:0.9;}
.btn-buka{padding:7px 18px;border:none;border-radius:20px;background:linear-gradient(135deg,#1d9e75,#0f6e56);color:white;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.3s;text-decoration:none;display:inline-block;}
.btn-buka:hover{transform:scale(1.05);opacity:0.9;}
.btn-leave{padding:7px 14px;border:1px solid #e53e3e;border-radius:20px;background:none;color:#e53e3e;font-size:11px;cursor:pointer;font-family:inherit;transition:all 0.3s;text-decoration:none;display:inline-block;margin-left:6px;}
.btn-leave:hover{background:#e53e3e;color:white;}
.badge-member{display:inline-block;background:#1a4731;color:#6fcf97;border:1px solid #27ae60;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;margin-bottom:6px;}

/* Filter */
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.filter-bar a{text-decoration:none;}
.filter-btn{padding:7px 18px;border-radius:20px;border:2px solid #2d5a8e;background:none;color:#9eafc4;font-size:13px;cursor:pointer;font-family:inherit;transition:all 0.3s;}
.filter-btn.active-f{background:#667eea;border-color:#667eea;color:white;font-weight:600;}
.filter-btn:hover{border-color:#667eea;}

/* Hero banner */
.hero-banner{background:linear-gradient(135deg,#0f2239,#1e3a5f);border-radius:16px;padding:28px 32px;color:white;margin-bottom:24px;display:flex;align-items:center;gap:20px;}
.hero-text h2{font-size:22px;font-weight:700;margin-bottom:6px;}
.hero-text p{font-size:14px;color:rgba(255,255,255,0.7);}
.hero-icon{font-size:56px;flex-shrink:0;}

/* Rightbar */
.my-comm-list{display:flex;flex-direction:column;gap:8px;}
.my-comm-item{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#1e3a5f;border-radius:10px;text-decoration:none;color:white;transition:all 0.2s;}
.my-comm-item:hover{background:#2d5a8e;}
.my-comm-name{font-size:13px;font-weight:600;}
.my-comm-kat{font-size:11px;color:#7a8ea8;}

/* Friends */
.friend img{width:36px;height:36px;border-radius:50%;object-fit:cover;background:#1e3a5f;border:2px solid #2d5a8e;}
.chat-icon-link{text-decoration:none;}
<link rel="stylesheet" href=" ../assets/css/sidebar.css">
<link rel="stylesheet" href="../assets/css/community.css">

<style>
.navbar-user{margin-left:auto;font-size:14px;color:white;}

/* CSS lain kamu di sini */

/* Paksa background putih */
html,
body,
.container,
.main {
    background: #ffffff !important;
}

.rightbar {
    background: #ffffff !important;
    border-left: 1px solid #e5e7eb !important;
}

.rightbar .card {
    background: #ffffff !important;
    color: #111827 !important;
    border: 1px solid #e5e7eb !important;
}

.rightbar .card h3,
.friend-info strong {
    color: #111827 !important;
}

.friend-info p {
    color: #6b7280 !important;
}
/* Paksa rightbar tetap gelap */


body .rightbar .card {
    background: #0f2239 !important;
    color: #ffffff !important;
    border: 1px solid rgba(255,255,255,0.06) !important;
}

body .rightbar .card h3 {
    color: #5b9cf5 !important;
}

body .rightbar .friend-info strong {
    color: #ffffff !important;
}

body .rightbar .friend-info p {
    color: rgba(255,255,255,0.6) !important;
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><img src="../assets/img/logoo.png" alt="logo"><span>SkillNex</span></div>
    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="mycourse.php">My Course</a>
        <a href="message.php">Message</a>
        <a href="community.php" class="active">Community</a>
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

<div class="container">
    <main class="main">

        <!-- Hero -->
        <div class="hero-banner">
            <div class="hero-icon">👥</div>
            <div class="hero-text">
                <h2>Temukan Komunitas Skillmu</h2>
                <p>Bergabung dengan komunitas sesuai minatmu, bagikan skill, dan tumbuh bersama!</p>
                <p style="margin-top:6px;font-size:13px;color:#5b9cf5;">
                    Kamu sudah bergabung di <strong><?= count($my_ids) ?></strong> komunitas
                </p>
            </div>
        </div>

        <!-- Search -->
        <div class="topbar">
            <input type="text" id="searchComm" placeholder="🔍 Cari komunitas..."
                   oninput="cariComm(this.value)">
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <a href="community.php">
                <button class="filter-btn <?= !$filter ? 'active-f' : '' ?>">Semua</button>
            </a>
            <?php foreach(['Public Speaking','Desain','Teknologi','Musik'] as $kat): ?>
            <a href="community.php?kategori=<?= urlencode($kat) ?>">
                <button class="filter-btn <?= $filter === $kat ? 'active-f' : '' ?>"><?= $kat ?></button>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Grid komunitas -->
        <div class="comm-grid" id="commGrid">
            <?php foreach ($communities as $c): ?>
                <?php $joined = in_array($c['id'], $my_ids); ?>
                <div class="comm-card" data-nama="<?= strtolower($c['nama']) ?>">
                    <div class="comm-banner-placeholder">
                        <?php
                        $icons = ['Public Speaking'=>'🎤','Desain'=>'🎨','Teknologi'=>'💻','Musik'=>'🎵','Umum'=>'🌐'];
                        echo $icons[$c['kategori']] ?? '👥';
                        ?>
                    </div>
                    <div class="comm-body">
                        <?php if ($joined): ?>
                            <span class="badge-member">✓ Member</span><br>
                        <?php endif; ?>
                        <span class="comm-kat"><?= htmlspecialchars($c['kategori']) ?></span>
                        <div class="comm-nama"><?= htmlspecialchars($c['nama']) ?></div>
                        <div class="comm-desc"><?= htmlspecialchars($c['deskripsi']) ?></div>
                        <div class="comm-meta">
                            <span class="comm-member">👥 <?= number_format($c['total_member']) ?> members</span>
                            <div>
                                <?php if ($joined): ?>
                                    <a href="community_detail.php?id=<?= $c['id'] ?>" class="btn-buka">Buka →</a>
                                    <a href="community.php?leave=<?= $c['id'] ?>"
                                       class="btn-leave"
                                       onclick="return confirm('Keluar dari komunitas ini?')">Keluar</a>
                                <?php else: ?>
                                    <a href="community.php?join=<?= $c['id'] ?>" class="btn-join">+ Join</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- Rightbar -->
    <aside class="rightbar">
        <!-- Komunitas yang diikuti -->
        <div class="card">
            <h3>My Communities</h3>
            <?php if (empty($my_ids)): ?>
                <p style="font-size:13px;color:#7a8ea8;">Belum bergabung komunitas.</p>
            <?php else: ?>
                <div class="my-comm-list">
                    <?php
                    $myComms = $pdo->prepare("SELECT * FROM communities WHERE id IN (" . implode(',', $my_ids) . ")");
                    $myComms->execute();
                    foreach ($myComms->fetchAll() as $mc):
                    ?>
                        <a href="community_detail.php?id=<?= $mc['id'] ?>" class="my-comm-item">
                            <div>
                                <div class="my-comm-name"><?= htmlspecialchars($mc['nama']) ?></div>
                                <div class="my-comm-kat"><?= htmlspecialchars($mc['kategori']) ?></div>
                            </div>
                            <span style="color:#5b9cf5;font-size:18px;">›</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Friends -->
        <div class="card">
            <h3>My Friends Online</h3>
            <?php
            $friends = $pdo->prepare("SELECT id, nama, foto_profil FROM users WHERE id != ? LIMIT 5");
            $friends->execute([$user_id]);
            foreach ($friends->fetchAll() as $fr):
                $ff = !empty($fr['foto_profil']) && $fr['foto_profil'] !== 'default.jpg'
                    ? '../assets/'.$fr['foto_profil'] : '../assets/img/default-profile.png';
            ?>
            <div class="friend">
                <img src="<?= htmlspecialchars($ff) ?>" onerror="this.src='../assets/img/default-profile.png'">
                <div class="friend-info">
                    <strong><?= htmlspecialchars($fr['nama']) ?></strong>
                    <p>@<?= strtolower(str_replace(' ','',$fr['nama'])) ?></p>
                </div>
                <a href="message.php?with=<?= $fr['id'] ?>" class="chat-icon-link">
                    <span class="chat-icon">💬</span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<script>
function cariComm(keyword) {
    keyword = keyword.toLowerCase();
    document.querySelectorAll('.comm-card').forEach(c => {
        c.style.display = c.dataset.nama.includes(keyword) ? '' : 'none';
    });
}
</script>
</body>
</html>