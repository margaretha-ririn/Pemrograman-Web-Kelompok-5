<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$comm_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$comm_id) {
    header("Location: community.php");
    exit;
}

// Ambil data komunitas
$stmt = $pdo->prepare("SELECT * FROM communities WHERE id = ?");
$stmt->execute([$comm_id]);
$comm = $stmt->fetch();

if (!$comm) {
    header("Location: community.php");
    exit;
}

// Cek apakah user sudah join
$cek = $pdo->prepare("SELECT id FROM community_members WHERE community_id = ? AND user_id = ?");
$cek->execute([$comm_id, $user_id]);
$is_member = $cek->rowCount() > 0;

if (!$is_member) {
    header("Location: community.php");
    exit;
}

$success = '';
$error   = '';

// === BUAT POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_post'])) {
    $konten = trim($_POST['konten']);
    $tipe   = $_POST['tipe'] ?? 'butuh_skill';

    if (empty($konten)) {
        $error = "Isi post tidak boleh kosong!";
    } else {
        $pdo->prepare("
            INSERT INTO community_posts (community_id, user_id, konten, tipe)
            VALUES (?,?,?,?)
        ")->execute([$comm_id, $user_id, $konten, $tipe]);

        header("Location: community_detail.php?id=$comm_id");
        exit;
    }
}

// === BUAT KOMENTAR ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_komentar'])) {
    $post_id  = (int)$_POST['post_id'];
    $komentar = trim($_POST['komentar']);

    if (!empty($komentar)) {
        $cekPost = $pdo->prepare("SELECT id FROM community_posts WHERE id = ? AND community_id = ?");
        $cekPost->execute([$post_id, $comm_id]);

        if ($cekPost->rowCount() > 0) {
            $pdo->prepare("
                INSERT INTO community_comments (post_id, user_id, komentar)
                VALUES (?,?,?)
            ")->execute([$post_id, $user_id, $komentar]);
        }
    }

    header("Location: community_detail.php?id=$comm_id");
    exit;
}

// === GROUP CHAT COMMUNITY ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_group_chat'])) {
    $pesan = trim($_POST['pesan_group']);

    if (!empty($pesan)) {
        $pdo->prepare("
            INSERT INTO community_messages (community_id, user_id, pesan)
            VALUES (?,?,?)
        ")->execute([$comm_id, $user_id, $pesan]);
    }

    header("Location: community_detail.php?id=$comm_id#group-chat");
    exit;
}

// === HAPUS POST ===
if (isset($_GET['hapus'])) {
    $pid = (int)$_GET['hapus'];

    $cekP = $pdo->prepare("SELECT user_id FROM community_posts WHERE id = ? AND community_id = ?");
    $cekP->execute([$pid, $comm_id]);
    $p = $cekP->fetch();

    if ($p && $p['user_id'] == $user_id) {
        $pdo->prepare("DELETE FROM community_comments WHERE post_id = ?")->execute([$pid]);
        $pdo->prepare("DELETE FROM community_posts WHERE id = ?")->execute([$pid]);
    }

    header("Location: community_detail.php?id=$comm_id");
    exit;
}

// Ambil semua post di komunitas ini
$posts = $pdo->prepare("
    SELECT cp.*, u.nama, u.foto_profil
    FROM community_posts cp
    JOIN users u ON cp.user_id = u.id
    WHERE cp.community_id = ?
    ORDER BY cp.created_at DESC
");
$posts->execute([$comm_id]);
$post_list = $posts->fetchAll();

// Ambil komentar untuk setiap post
foreach ($post_list as &$p) {
    $cmt = $pdo->prepare("
        SELECT cc.*, u.nama, u.foto_profil
        FROM community_comments cc
        JOIN users u ON cc.user_id = u.id
        WHERE cc.post_id = ?
        ORDER BY cc.created_at ASC
    ");
    $cmt->execute([$p['id']]);
    $p['comments'] = $cmt->fetchAll();
}
unset($p);

// Ambil daftar member
$members = $pdo->prepare("
    SELECT u.id, u.nama, u.foto_profil
    FROM community_members cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.community_id = ?
    ORDER BY cm.joined_at ASC
");
$members->execute([$comm_id]);
$member_list = $members->fetchAll();

// Ambil group chat komunitas
$groupChat = $pdo->prepare("
    SELECT cm.*, u.nama, u.foto_profil
    FROM community_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.community_id = ?
    ORDER BY cm.created_at ASC
");
$groupChat->execute([$comm_id]);
$group_messages = $groupChat->fetchAll();

// Data user login
$me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$me->execute([$user_id]);
$user = $me->fetch();

$foto_profil = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? '../assets/' . $user['foto_profil']
    : '../assets/img/logoo.png';

$icons = [
    'Public Speaking' => '🎤',
    'Desain'          => '🎨',
    'Teknologi'       => '💻',
    'Musik'           => '🎵',
    'Umum'            => '🌐'
];

$icon = $icons[$comm['kategori']] ?? '👥';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($comm['nama']) ?> - SkillNex</title>
<link rel="stylesheet" href="../assets/css/sidebar.css">
<link rel="stylesheet" href="../assets/css/community.css">

<style>
.logout-btn{display:block;text-align:center;background:#e53e3e;color:white;padding:10px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;margin:20px 20px 0;}
.logout-btn:hover{background:#c53030;}
.navbar-user{margin-left:auto;font-size:14px;color:white;}

.alert-success{background:#1a4731;color:#6fcf97;padding:12px 16px;border-radius:8px;margin-bottom:14px;border-left:4px solid #27ae60;font-size:14px;}
.alert-error{background:#4a1515;color:#eb5757;padding:12px 16px;border-radius:8px;margin-bottom:14px;border-left:4px solid #e53e3e;font-size:14px;}

.comm-header{background:linear-gradient(135deg,#0f2239,#1e3a5f,#2d5a8e);border-radius:16px;padding:24px 28px;color:white;margin-bottom:20px;display:flex;align-items:center;gap:20px;}
.comm-header-icon{width:64px;height:64px;background:rgba(255,255,255,0.1);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0;}
.comm-header-info h2{font-size:20px;font-weight:700;margin-bottom:4px;}
.comm-header-info p{font-size:13px;color:rgba(255,255,255,0.7);}
.comm-header-stats{margin-left:auto;text-align:right;}
.comm-header-stats .num{font-size:24px;font-weight:700;color:#5b9cf5;}
.comm-header-stats .lbl{font-size:12px;color:rgba(255,255,255,0.6);}
.btn-back{display:inline-flex;align-items:center;gap:6px;color:#5b9cf5;text-decoration:none;font-size:14px;font-weight:600;margin-bottom:16px;}
.btn-back:hover{color:#fff;}
.btn-leave-header{padding:8px 18px;border:1px solid #e53e3e;border-radius:20px;background:none;color:#e53e3e;font-size:13px;cursor:pointer;font-family:inherit;transition:all 0.3s;text-decoration:none;display:inline-block;margin-left:16px;}
.btn-leave-header:hover{background:#e53e3e;color:white;}

.group-tabs{display:flex;gap:10px;margin-bottom:18px;}
.group-tab{padding:9px 18px;border-radius:20px;border:1px solid #2d5a8e;background:#0f2239;color:#9eafc4;text-decoration:none;font-size:13px;font-weight:600;}
.group-tab:hover{background:#1e3a5f;color:white;}

.post-form-card,.group-chat-card{background:#0f2239;border:1px solid #2d5a8e;border-radius:14px;padding:20px;margin-bottom:20px;}
.post-form-header{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.post-form-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #2d5a8e;}
.post-form-name{font-size:14px;font-weight:600;color:#fff;}
.tipe-radio{display:flex;gap:16px;margin-bottom:12px;flex-wrap:wrap;}
.tipe-radio label{display:flex;align-items:center;gap:6px;font-size:13px;color:#9eafc4;cursor:pointer;}
.tipe-radio input[type=radio]:checked + span{color:#fff;font-weight:600;}
.post-textarea{width:100%;padding:12px 16px;border:2px solid #2d5a8e;border-radius:10px;font-size:14px;font-family:inherit;background:#1e3a5f;color:#fff;resize:vertical;min-height:80px;transition:all 0.3s;}
.post-textarea:focus{outline:none;border-color:#5b9cf5;}
.btn-post{padding:10px 24px;border:none;border-radius:10px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;font-weight:600;font-size:14px;cursor:pointer;font-family:inherit;margin-top:10px;transition:all 0.3s;float:right;}
.btn-post:hover{transform:translateY(-1px);opacity:0.9;}

.post-card{background:#0f2239;border:1px solid #1e3a5f;border-radius:14px;padding:18px;margin-bottom:14px;transition:all 0.3s;}
.post-card:hover{border-color:#2d5a8e;}
.post-head{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.post-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #2d5a8e;}
.post-user-name{font-size:14px;font-weight:600;color:#fff;}
.post-time{font-size:11px;color:#7a8ea8;}
.post-tipe{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:8px;}
.tipe-butuh{background:#faeeda;color:#ba7517;border:1px solid #ef9f27;}
.tipe-tawarkan{background:#e1f5ee;color:#0f6e56;border:1px solid #1d9e75;}
.post-konten{font-size:14px;color:#b8c5d6;line-height:1.7;margin:8px 0 0;}
.post-footer{display:flex;justify-content:flex-end;margin-top:10px;padding-top:10px;border-top:1px solid #1e3a5f;}
.btn-hapus-post{background:none;border:1px solid #e53e3e;color:#e53e3e;padding:4px 12px;border-radius:8px;font-size:12px;cursor:pointer;font-family:inherit;transition:all 0.3s;}
.btn-hapus-post:hover{background:#e53e3e;color:white;}

.comments-section{margin-top:15px;padding-top:15px;border-top:1px solid #1e3a5f;}
.comment-title{font-size:13px;font-weight:700;color:#fff;margin-bottom:10px;}
.comment-item{background:#132844;padding:10px 12px;border-radius:10px;margin-bottom:8px;}
.comment-item strong{color:#fff;font-size:13px;}
.comment-item p{color:#b8c5d6;margin-top:4px;font-size:13px;line-height:1.5;}
.comment-time{font-size:10px;color:#7a8ea8;margin-top:3px;}
.comment-form{display:flex;gap:8px;margin-top:10px;}
.comment-form input{flex:1;padding:10px 12px;border-radius:8px;border:1px solid #2d5a8e;background:#1e3a5f;color:white;font-family:inherit;}
.comment-form button{background:#5b9cf5;border:none;color:white;padding:8px 16px;border-radius:8px;cursor:pointer;font-family:inherit;font-weight:600;}

.empty-post{text-align:center;padding:40px;color:#7a8ea8;background:#0f2239;border-radius:12px;border:1px dashed #2d5a8e;margin-bottom:14px;}
.empty-post .ei{font-size:40px;margin-bottom:12px;}

.group-chat-card h3{color:#fff;margin-bottom:12px;}
.group-chat-box{height:280px;overflow-y:auto;background:#07172a;border:1px solid #1e3a5f;border-radius:12px;padding:14px;margin-bottom:12px;}
.group-msg{display:flex;gap:10px;margin-bottom:12px;align-items:flex-start;}
.group-msg.me{flex-direction:row-reverse;}
.group-msg img{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid #2d5a8e;}
.group-bubble{max-width:75%;background:#132844;border:1px solid #1e3a5f;border-radius:12px;padding:9px 12px;color:#b8c5d6;font-size:13px;line-height:1.5;}
.group-msg.me .group-bubble{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;}
.group-bubble strong{display:block;color:#fff;font-size:12px;margin-bottom:3px;}
.group-time{display:block;font-size:10px;opacity:.7;margin-top:5px;text-align:right;}
.group-chat-form{display:flex;gap:10px;}
.group-chat-form input{flex:1;padding:11px 14px;border-radius:20px;border:1px solid #2d5a8e;background:#1e3a5f;color:white;font-family:inherit;}
.group-chat-form button{border:none;border-radius:20px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:10px 18px;font-weight:700;cursor:pointer;}

.member-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.06);}
.member-item:last-child{border-bottom:none;}
.member-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #2d5a8e;}
.member-name{font-size:13px;font-weight:600;color:#fff;}
.member-you{font-size:10px;color:#5b9cf5;font-weight:700;}
.btn-chat-member{background:none;border:1px solid #2d5a8e;color:#5b9cf5;padding:4px 10px;border-radius:8px;font-size:11px;cursor:pointer;font-family:inherit;text-decoration:none;transition:all 0.2s;}
.btn-chat-member:hover{background:#2d5a8e;color:white;}
.member-count{font-size:12px;color:#7a8ea8;margin-bottom:10px;}
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
    <main class="main">

        <a href="community.php" class="btn-back">← Kembali ke Komunitas</a>

        <div class="comm-header">
            <div class="comm-header-icon"><?= $icon ?></div>
            <div class="comm-header-info">
                <h2><?= htmlspecialchars($comm['nama']) ?></h2>
                <p><?= htmlspecialchars($comm['deskripsi']) ?></p>
                <p style="margin-top:6px;font-size:12px;">
                    <span style="background:rgba(255,255,255,0.1);padding:2px 10px;border-radius:20px;">
                        <?= htmlspecialchars($comm['kategori']) ?>
                    </span>
                </p>
            </div>
            <div class="comm-header-stats">
                <div class="num"><?= number_format($comm['total_member']) ?></div>
                <div class="lbl">Members</div>
                <a href="community.php?leave=<?= $comm_id ?>"
                   class="btn-leave-header"
                   onclick="return confirm('Keluar dari komunitas ini?')">Keluar</a>
            </div>
        </div>

        <div class="group-tabs">
            <a href="#feed" class="group-tab">📢 Feed</a>
            <a href="#group-chat" class="group-tab">💬 Group Chat</a>
            <a href="#members" class="group-tab">👥 Members</a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="post-form-card" id="feed">
            <div class="post-form-header">
                <img src="<?= htmlspecialchars($foto_profil) ?>" class="post-form-avatar"
                     onerror="this.src='../assets/img/default-profile.png'">
                <span class="post-form-name"><?= htmlspecialchars($user['nama']) ?></span>
            </div>

            <form method="POST" action="">
                <div class="tipe-radio">
                    <label>
                        <input type="radio" name="tipe" value="butuh_skill" checked>
                        <span>🔍 Saya membutuhkan skill...</span>
                    </label>
                    <label>
                        <input type="radio" name="tipe" value="tawarkan_skill">
                        <span>🌟 Saya menawarkan skill saya</span>
                    </label>
                    <label>
                        <input type="radio" name="tipe" value="diskusi">
                        <span>💬 Diskusi</span>
                    </label>
                </div>

                <textarea name="konten" class="post-textarea"
                          placeholder="Tulis sesuatu untuk komunitas ini..." required></textarea>

                <button type="submit" name="buat_post" class="btn-post">🚀 Post</button>
                <div style="clear:both;"></div>
            </form>
        </div>

        <?php if (empty($post_list)): ?>
            <div class="empty-post">
                <div class="ei">📢</div>
                <p>Belum ada post di komunitas ini.<br>Jadilah yang pertama berbagi!</p>
            </div>
        <?php else: ?>
            <?php foreach ($post_list as $post): ?>
                <?php
                $fp = !empty($post['foto_profil']) && $post['foto_profil'] !== 'default.jpg'
                    ? '../assets/'.$post['foto_profil']
                    : '../assets/img/default-profile.png';

                $tipeClass = 'tipe-butuh';
                $tipeLabel = '🔍 Butuh Skill';

                if ($post['tipe'] === 'tawarkan_skill') {
                    $tipeClass = 'tipe-tawarkan';
                    $tipeLabel = '🌟 Tawarkan Skill';
                } elseif ($post['tipe'] === 'diskusi') {
                    $tipeClass = 'tipe-butuh';
                    $tipeLabel = '💬 Diskusi';
                }
                ?>

                <div class="post-card">
                    <div class="post-head">
                        <img src="<?= htmlspecialchars($fp) ?>" class="post-avatar"
                             onerror="this.src='../assets/img/default-profile.png'">
                        <div>
                            <div class="post-user-name"><?= htmlspecialchars($post['nama']) ?></div>
                            <div class="post-time"><?= date('d M Y H:i', strtotime($post['created_at'])) ?></div>
                        </div>
                    </div>

                    <span class="post-tipe <?= $tipeClass ?>"><?= $tipeLabel ?></span>

                    <p class="post-konten">
                        <?= nl2br(htmlspecialchars($post['konten'])) ?>
                    </p>

                    <div class="comments-section">
                        <div class="comment-title">
                            💬 Komentar (<?= count($post['comments']) ?>)
                        </div>

                        <?php if (empty($post['comments'])): ?>
                            <p style="font-size:12px;color:#7a8ea8;margin-bottom:8px;">Belum ada komentar.</p>
                        <?php else: ?>
                            <?php foreach ($post['comments'] as $c): ?>
                                <div class="comment-item">
                                    <strong><?= htmlspecialchars($c['nama']) ?></strong>
                                    <p><?= nl2br(htmlspecialchars($c['komentar'])) ?></p>
                                    <div class="comment-time">
                                        <?= date('d M Y H:i', strtotime($c['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <form method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="text" name="komentar" placeholder="Tulis komentar..." required>
                            <button type="submit" name="buat_komentar">Kirim</button>
                        </form>
                    </div>

                    <?php if ($post['user_id'] == $user_id): ?>
                        <div class="post-footer">
                            <a href="community_detail.php?id=<?= $comm_id ?>&hapus=<?= $post['id'] ?>"
                               onclick="return confirm('Hapus post ini?')">
                                <button class="btn-hapus-post" type="button">🗑 Hapus</button>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="group-chat-card" id="group-chat">
            <h3>💬 Group Chat <?= htmlspecialchars($comm['nama']) ?></h3>

            <div class="group-chat-box" id="groupChatBox">
                <?php if (empty($group_messages)): ?>
                    <p style="text-align:center;color:#7a8ea8;font-size:13px;margin-top:90px;">
                        Belum ada chat grup. Mulai obrolan pertama 👋
                    </p>
                <?php else: ?>
                    <?php foreach ($group_messages as $gm): ?>
                        <?php
                        $fg = !empty($gm['foto_profil']) && $gm['foto_profil'] !== 'default.jpg'
                            ? '../assets/'.$gm['foto_profil']
                            : '../assets/img/logoo.png';
                        $isMeMsg = $gm['user_id'] == $user_id;
                        ?>
                        <div class="group-msg <?= $isMeMsg ? 'me' : '' ?>">
                            <img src="<?= htmlspecialchars($fg) ?>" onerror="this.src='../assets/img/logoo.png'">
                            <div class="group-bubble">
                                <?php if (!$isMeMsg): ?>
                                    <strong><?= htmlspecialchars($gm['nama']) ?></strong>
                                <?php endif; ?>
                                <?= nl2br(htmlspecialchars($gm['pesan'])) ?>
                                <span class="group-time"><?= date('H:i', strtotime($gm['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" class="group-chat-form">
                <input type="text" name="pesan_group" placeholder="Ketik pesan ke grup..." required autocomplete="off">
                <button type="submit" name="kirim_group_chat">Kirim</button>
            </form>
        </div>

    </main>

    <aside class="rightbar" id="members">
        <div class="card">
            <h3>👥 Members</h3>
            <p class="member-count"><?= count($member_list) ?> anggota</p>

            <?php foreach ($member_list as $m): ?>
                <?php
                $fm = !empty($m['foto_profil']) && $m['foto_profil'] !== 'default.jpg'
                    ? '../assets/'.$m['foto_profil']
                    : '../assets/img/logoo.png';

                $isMe = $m['id'] == $user_id;
                ?>

                <div class="member-item">
                    <img src="<?= htmlspecialchars($fm) ?>" class="member-avatar"
                         onerror="this.src='../assets/img/logoo.png'">

                    <div style="flex:1;min-width:0;">
                        <div class="member-name">
                            <?= htmlspecialchars($m['nama']) ?>
                            <?php if ($isMe): ?>
                                <span class="member-you">(Kamu)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$isMe): ?>
                        <a href="message.php?with=<?= $m['id'] ?>" class="btn-chat-member">💬</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3>ℹ️ Tentang</h3>
            <p style="font-size:13px;color:#9eafc4;line-height:1.6;margin-top:8px;">
                <?= htmlspecialchars($comm['deskripsi']) ?>
            </p>

            <div style="margin-top:12px;font-size:12px;color:#7a8ea8;">
                <div style="margin-bottom:6px;">
                    🏷️ Kategori:
                    <strong style="color:#5b9cf5;"><?= htmlspecialchars($comm['kategori']) ?></strong>
                </div>
                <div>👥 <?= number_format($comm['total_member']) ?> anggota</div>
            </div>
        </div>
    </aside>
</div>

<script>
const groupChatBox = document.getElementById('groupChatBox');
if (groupChatBox) {
    groupChatBox.scrollTop = groupChatBox.scrollHeight;
}
</script>

</body>
</html>
