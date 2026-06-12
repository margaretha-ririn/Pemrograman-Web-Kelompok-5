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

// === KIRIM PESAN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pesan'])) {
    $penerima_id = (int)$_POST['penerima_id'];
    $pesan       = trim($_POST['pesan']);

    if (!empty($pesan) && $penerima_id !== $user_id) {
        $stmt = $pdo->prepare("INSERT INTO messages (pengirim_id, penerima_id, pesan) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $penerima_id, $pesan]);
    }
    header("Location: message.php?with=" . $penerima_id);
    exit;
}

// === TANDAI PESAN SUDAH DIBACA ===
$with = isset($_GET['with']) ? (int)$_GET['with'] : 0;
if ($with > 0) {
    $pdo->prepare("UPDATE messages SET dibaca = 1 WHERE penerima_id = ? AND pengirim_id = ?")
        ->execute([$user_id, $with]);
}

// === AMBIL SEMUA USER LAIN ===
$semua_user = $pdo->prepare("SELECT id, nama, foto_profil FROM users WHERE id != ? ORDER BY nama ASC");
$semua_user->execute([$user_id]);
$daftar_user = $semua_user->fetchAll();

// === AMBIL LIST CHAT ===
$list_chat = [];
foreach ($daftar_user as $u) {
    $last = $pdo->prepare("
        SELECT pesan, created_at, pengirim_id
        FROM messages
        WHERE (pengirim_id = ? AND penerima_id = ?)
           OR (pengirim_id = ? AND penerima_id = ?)
        ORDER BY created_at DESC LIMIT 1
    ");
    $last->execute([$user_id, $u['id'], $u['id'], $user_id]);
    $lastMsg = $last->fetch();

    $unread = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE pengirim_id = ? AND penerima_id = ? AND dibaca = 0");
    $unread->execute([$u['id'], $user_id]);
    $unread_count = $unread->fetch()['total'];

    if ($lastMsg) {
        $list_chat[] = [
            'id'          => $u['id'],
            'nama'        => $u['nama'],
            'foto_profil' => $u['foto_profil'],
            'last_msg'    => $lastMsg['pesan'] ?? '',
            'last_time'   => $lastMsg ? date('H:i', strtotime($lastMsg['created_at'])) : '',
            'unread'      => $unread_count,
        ];
    }
}

// === AMBIL PERCAKAPAN ===
$percakapan  = [];
$chat_dengan = null;
if ($with > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$with]);
    $chat_dengan = $stmt->fetch();

    if ($chat_dengan) {
        $stmt = $pdo->prepare("
            SELECT m.*, u.nama, u.foto_profil
            FROM messages m
            JOIN users u ON m.pengirim_id = u.id
            WHERE (m.pengirim_id = ? AND m.penerima_id = ?)
               OR (m.pengirim_id = ? AND m.penerima_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $with, $with, $user_id]);
        $percakapan = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Message - SkillNex</title>
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* LAYOUT */
    .main-wrap { margin-left:180px; margin-top:56px; height:calc(100vh - 56px); display:flex; flex-direction:column; padding:16px 20px; gap:12px; }

    /* TOP BAR */
    .top-bar { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-shrink:0; background:white; padding:12px 18px; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    .recent-label { font-size:13px; font-weight:600; color:#334155; white-space:nowrap; }
    .avatars { display:flex; gap:8px; align-items:center; }
    .avatar-img { width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #e2e8f0; cursor:pointer; transition:transform 0.2s; }
    .avatar-img:hover { transform:scale(1.1); border-color:#667eea; }

    /* SEARCH */
    .search-wrapper { position:relative; }
    .search-wrapper input { width:240px; padding:8px 16px; border:2px solid #e2e8f0; border-radius:24px; font-size:13px; font-family:inherit; outline:none; background:#f8fafc; transition:all 0.3s; }
    .search-wrapper input:focus { border-color:#667eea; background:white; }
    .user-dropdown { display:none; position:absolute; top:calc(100% + 6px); right:0; width:240px; background:white; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.1); z-index:200; max-height:200px; overflow-y:auto; }
    .user-dropdown.show { display:block; }
    .dropdown-item { display:flex; align-items:center; gap:10px; padding:10px 14px; text-decoration:none; color:#1e293b; font-size:13px; transition:background 0.2s; }
    .dropdown-item:hover { background:#f1f5f9; }
    .dropdown-item img { width:30px; height:30px; border-radius:50%; object-fit:cover; }

    /* CHAT BODY */
    .chat-body { display:grid; grid-template-columns:270px 1fr; gap:16px; flex:1; min-height:0; }

    /* LEFT BOX */
    .left-box { background:white; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); display:flex; flex-direction:column; overflow:hidden; }
    .left-header { padding:14px 16px 10px; border-bottom:1px solid #f1f5f9; font-size:14px; font-weight:600; color:#1e293b; }
    .list-area { flex:1; overflow-y:auto; padding:6px; }
    .chat-item { display:flex; align-items:center; gap:10px; padding:10px; border-radius:10px; cursor:pointer; transition:background 0.2s; text-decoration:none; color:inherit; }
    .chat-item:hover { background:#f8fafc; }
    .chat-item.active-chat { background:#eef2ff; }
    .chat-avatar { width:42px; height:42px; border-radius:50%; object-fit:cover; flex-shrink:0; border:2px solid #e2e8f0; }
    .chat-info { flex:1; min-width:0; }
    .chat-name-row { display:flex; justify-content:space-between; margin-bottom:2px; }
    .chat-name { font-size:13px; font-weight:600; color:#1e293b; }
    .chat-time { font-size:11px; color:#94a3b8; }
    .chat-preview { font-size:12px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .unread-badge { background:#667eea; color:white; font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px; flex-shrink:0; }
    .empty-list { padding:24px; text-align:center; color:#94a3b8; font-size:13px; line-height:1.6; }

    /* CHAT WINDOW */
    .chat-window { background:white; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); display:flex; flex-direction:column; overflow:hidden; }
    .chat-header { display:flex; align-items:center; gap:12px; padding:14px 20px; border-bottom:1px solid #f1f5f9; }
    .chat-header-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #e2e8f0; }
    .chat-header-name { font-size:15px; font-weight:600; color:#1e293b; }
    .chat-header-status { font-size:11px; color:#22c55e; }
    .online-dot { width:7px; height:7px; border-radius:50%; background:#22c55e; display:inline-block; margin-right:3px; }

    /* MESSAGES */
    .chat-message-area { flex:1; overflow-y:auto; padding:16px 20px; display:flex; flex-direction:column; gap:8px; background:#f8fafc; }
    .msg { max-width:65%; padding:10px 14px; border-radius:16px; font-size:14px; line-height:1.5; word-break:break-word; }
    .msg.left { align-self:flex-start; background:white; border:1px solid #e2e8f0; border-bottom-left-radius:4px; color:#1e293b; }
    .msg.right { align-self:flex-end; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-bottom-right-radius:4px; }
    .msg-time { font-size:10px; display:block; text-align:right; margin-top:4px; opacity:0.6; }

    /* INPUT */
    .chat-input-area { padding:12px 20px; border-top:1px solid #f1f5f9; background:white; }
    .chat-input-area form { display:flex; gap:10px; align-items:center; }
    .chat-input-area input { flex:1; padding:11px 18px; border:2px solid #e2e8f0; border-radius:24px; font-size:14px; font-family:inherit; outline:none; background:#f8fafc; transition:all 0.3s; }
    .chat-input-area input:focus { border-color:#667eea; background:white; }
    .send-btn { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; cursor:pointer; font-size:18px; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.3s; }
    .send-btn:hover { transform:scale(1.08); }

    /* EMPTY */
    .empty-chat { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; text-align:center; padding:40px; }
    .empty-chat .icon { font-size:52px; margin-bottom:16px; }
    .empty-chat h3 { font-size:16px; color:#475569; margin-bottom:6px; }
    .empty-chat p { font-size:13px; }

    ::-webkit-scrollbar { width:4px; }
    ::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
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
        <a href="message.php" class="active">Message</a>
        <a href="community.php">Community</a>
        <a href="forum.php">Forum</a>
        <a href="livechat.php">Live Chat</a>
        <a href="payment.php">Payment</a>
        <a href="profile.php">Profile</a>
        <a href="createcourse.php">➕ Buat Kursus</a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php" style="color:rgba(255,255,255,0.7); text-decoration:none;">🚪 Logout</a>
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

<div class="main-wrap">
    <!-- TOP BAR -->
    <div class="top-bar">
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="recent-label">Recent Chat</span>
            <div class="avatars">
                <?php if (empty($list_chat)): ?>
                    <span style="font-size:12px;color:#94a3b8;">Belum ada chat</span>
                <?php else: ?>
                    <?php foreach (array_slice($list_chat, 0, 6) as $rc): ?>
                        <?php $f = !empty($rc['foto_profil']) && $rc['foto_profil'] !== 'default.jpg' ? '../assets/'.$rc['foto_profil'] : '../assets/img/logoo.png'; ?>
                        <img class="avatar-img" src="<?= htmlspecialchars($f) ?>"
                             title="<?= htmlspecialchars($rc['nama']) ?>"
                             onerror="this.src='../assets/img/logoo.png'"
                             onclick="location.href='message.php?with=<?= $rc['id'] ?>'">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="search-wrapper">
            <input type="text" id="searchUser" placeholder="🔍 Cari user..." autocomplete="off" oninput="cariUser(this.value)">
            <div class="user-dropdown" id="userDropdown">
                <?php foreach ($daftar_user as $u): ?>
                    <?php $f = !empty($u['foto_profil']) && $u['foto_profil'] !== 'default.jpg' ? '../assets/'.$u['foto_profil'] : '../assets/img/logoo.png'; ?>
                    <a href="message.php?with=<?= $u['id'] ?>" class="dropdown-item" data-nama="<?= strtolower($u['nama']) ?>">
                        <img src="<?= htmlspecialchars($f) ?>" onerror="this.src='../assets/img/logoo.png'">
                        <span><?= htmlspecialchars($u['nama']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- CHAT BODY -->
    <div class="chat-body">
        <!-- LIST KIRI -->
        <div class="left-box">
            <div class="left-header">💬 My List Chat</div>
            <div class="list-area">
                <?php if (empty($list_chat)): ?>
                    <div class="empty-list">Belum ada percakapan.<br>Cari user di atas untuk mulai chat!</div>
                <?php else: ?>
                    <?php foreach ($list_chat as $lc): ?>
                        <?php
                        $f = !empty($lc['foto_profil']) && $lc['foto_profil'] !== 'default.jpg' ? '../assets/'.$lc['foto_profil'] : '../assets/img/logoo.png';
                        $active = ($with == $lc['id']) ? 'active-chat' : '';
                        ?>
                        <a href="message.php?with=<?= $lc['id'] ?>" class="chat-item <?= $active ?>">
                            <img class="chat-avatar" src="<?= htmlspecialchars($f) ?>" onerror="this.src='../assets/img/logoo.png'">
                            <div class="chat-info">
                                <div class="chat-name-row">
                                    <span class="chat-name"><?= htmlspecialchars($lc['nama']) ?></span>
                                    <span class="chat-time"><?= $lc['last_time'] ?></span>
                                </div>
                                <div class="chat-preview"><?= htmlspecialchars($lc['last_msg']) ?></div>
                            </div>
                            <?php if ($lc['unread'] > 0): ?>
                                <span class="unread-badge"><?= $lc['unread'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHAT WINDOW -->
        <div class="chat-window">
            <?php if ($chat_dengan): ?>
                <?php $fc = !empty($chat_dengan['foto_profil']) && $chat_dengan['foto_profil'] !== 'default.jpg' ? '../assets/'.$chat_dengan['foto_profil'] : '../assets/img/logoo.png'; ?>
                <div class="chat-header">
                    <img class="chat-header-avatar" src="<?= htmlspecialchars($fc) ?>" onerror="this.src='../assets/img/logoo.png'">
                    <div>
                        <div class="chat-header-name"><?= htmlspecialchars($chat_dengan['nama']) ?></div>
                        <div class="chat-header-status"><span class="online-dot"></span>Online</div>
                    </div>
                </div>

                <div class="chat-message-area" id="chatArea">
                    <?php if (empty($percakapan)): ?>
                        <div style="text-align:center;color:#94a3b8;font-size:13px;margin-top:40px;">Belum ada pesan. Mulai percakapan! 👋</div>
                    <?php else: ?>
                        <?php foreach ($percakapan as $msg): ?>
                            <div class="msg <?= $msg['pengirim_id'] == $user_id ? 'right' : 'left' ?>">
                                <?= htmlspecialchars($msg['pesan']) ?>
                                <span class="msg-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="chat-input-area">
                    <form method="POST" action="" id="chatForm">
                        <input type="hidden" name="penerima_id" value="<?= $with ?>">
                        <input type="text" name="pesan" id="inputPesan" placeholder="Ketik pesan..." autocomplete="off">
                        <button type="submit" name="kirim_pesan" class="send-btn">➤</button>
                    </form>
                </div>

            <?php else: ?>
                <div class="empty-chat">
                    <div class="icon">💬</div>
                    <h3>Mulai percakapan</h3>
                    <p>Pilih user dari daftar kiri atau<br>cari user baru di kolom pencarian</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Auto scroll ke bawah
    const chatArea = document.getElementById('chatArea');
    if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;

    // Kirim dengan Enter
    const inputPesan = document.getElementById('inputPesan');
    if (inputPesan) {
        inputPesan.addEventListener('keypress', e => {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('chatForm').submit(); }
        });
    }

    // Cari user
    function cariUser(keyword) {
        const dd = document.getElementById('userDropdown');
        keyword = keyword.toLowerCase().trim();
        if (!keyword) { dd.classList.remove('show'); return; }
        dd.classList.add('show');
        dd.querySelectorAll('.dropdown-item').forEach(item => {
            item.style.display = item.dataset.nama.includes(keyword) ? 'flex' : 'none';
        });
    }

    document.addEventListener('click', e => {
        const dd = document.getElementById('userDropdown');
        const si = document.getElementById('searchUser');
        if (!dd.contains(e.target) && e.target !== si) dd.classList.remove('show');
    });

    // Auto refresh HANYA kalau input kosong (tidak sedang mengetik)
    <?php if ($with > 0): ?>
    let isTyping = false;
    if (inputPesan) {
        inputPesan.addEventListener('input', () => { isTyping = inputPesan.value.trim() !== ''; });
    }
    setInterval(() => { if (!isTyping) location.reload(); }, 5000);
    <?php endif; ?>
</script>

</body>
</html>