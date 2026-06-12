<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user untuk nama dan foto
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$foto = !empty($user['foto_profil']) && $user['foto_profil'] !== 'default.jpg'
    ? '../assets/' . $user['foto_profil']
    : '../assets/img/default-profile.png';

$nama = htmlspecialchars($user['nama']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Chat - SkillNex</title>
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="../assets/css/livechat.css">
  <style>
    .navbar-user { margin-left: auto; font-size: 14px; color: white; }

    /* Badge live */
    .live-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: #e53e3e; color: white;
        padding: 4px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 700;
        margin-left: 12px; vertical-align: middle;
    }
    .live-dot {
        width: 8px; height: 8px; background: white;
        border-radius: 50%; animation: blink 1s infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    .online-count {
        font-size: 13px; color: #7a8ea8; margin-bottom: 12px;
    }
    /* Pesan milik sendiri rata kanan */
    .msg.mine {
        flex-direction: row-reverse;
    }
    .msg.mine .bubble {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 18px 4px 18px 18px;
    }
    .msg.mine .username {
        color: rgba(255,255,255,0.8);
    }
    html, body, .container {
    background: #ffffff !important;
    }

    /* Override warna teks biar keliatan di background putih */
    .page-title {
        color: #1a202c !important;  /* hitam gelap */
    }

    .online-count {
        color: #4a5568 !important;  /* abu gelap */
    }

    .live-badge {
        /* badge LIVE udah merah, jadi fine — tapi kalau mau pastiin */
        background: #e53e3e !important;
        color: white !important;
    }
    .main h1 {
    color: #1a202c !important;
    }

    .main p {
        color: #4a5568 !important;
    }
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
      <a href="forum.php">Forum</a>
      <a href="livechat.php" class="active">Live Chat</a>
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

  <main class="main">
    <h1 class="page-title">
        Global Live Chat
        <span class="live-badge">
            <span class="live-dot"></span> LIVE
        </span>
    </h1>

    <p class="online-count">
        💬 Chat bersifat sementara — pesan tidak tersimpan dan akan hilang saat halaman di-refresh
    </p>

    <section class="chat-window">
      <div class="message-list" id="messageList">

        <!-- Pesan awal / welcome -->
        <div class="msg">
          <div class="avatar">
            <img src="../assets/img/logoo.png" alt="SkillNex">
          </div>
          <div class="bubble">
            <div class="username">SkillNex Bot</div>
            Selamat datang di Global Live Chat! 👋 Mulai percakapan dan terhubung dengan semua pengguna SkillNex secara real-time!
          </div>
        </div>

      </div>
    </section>

    <div class="input-row">
      <div class="input-wrap">
        <input type="text" id="chatInput" placeholder="Ketik pesan...">
        <div class="emoji-btn" onclick="toggleEmoji()">😊</div>
      </div>
      <button class="send-btn" onclick="sendMessage()">Kirim</button>
    </div>

    <!-- Emoji picker sederhana -->
    <div id="emojiPicker" style="display:none; flex-wrap:wrap; gap:6px; margin-top:8px; background:#1e3a5f; padding:10px; border-radius:10px;">
        <?php
        $emojis = ['😀','😍','🔥','👍','🎉','😂','🤔','💪','✨','😎','🙌','❤️','💡','🎯','👏','😊','🚀','💻','🎵','🌟'];
        foreach ($emojis as $e) echo "<span onclick=\"addEmoji('$e')\" style='font-size:22px;cursor:pointer;'>$e</span>";
        ?>
    </div>

  </main>

  <script>
    // Nama dan foto dari PHP (tidak bisa dilihat user lain karena JS)
    const myName = <?= json_encode($user['nama']) ?>;
    const myFoto = <?= json_encode($foto) ?>;

    function sendMessage() {
        const input   = document.getElementById('chatInput');
        const message = input.value.trim();
        if (!message) return;

        const list = document.getElementById('messageList');
        const div  = document.createElement('div');
        div.className = 'msg mine';

        // Escape HTML biar aman
        const safe = message.replace(/</g,'&lt;').replace(/>/g,'&gt;');

        div.innerHTML = `
            <div class="avatar">
                <img src="${myFoto}" alt="${myName}" onerror="this.src='../assets/img/default-profile.png'">
            </div>
            <div class="bubble">
                <div class="username">${myName}</div>
                ${safe}
            </div>
        `;
        list.appendChild(div);

        // Auto scroll ke bawah
        const win = document.querySelector('.chat-window');
        win.scrollTop = win.scrollHeight;

        input.value = '';
        document.getElementById('emojiPicker').style.display = 'none';
    }

    // Enter untuk kirim
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });

    // Toggle emoji picker
    function toggleEmoji() {
        const picker = document.getElementById('emojiPicker');
        picker.style.display = picker.style.display === 'none' ? 'flex' : 'none';
    }

    // Tambah emoji ke input
    function addEmoji(emoji) {
        const input = document.getElementById('chatInput');
        input.value += emoji;
        input.focus();
    }
  </script>
</body>
</html>