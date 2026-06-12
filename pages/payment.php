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

// === AMBIL KURSUS YANG SUDAH DIIKUTI ===
$enrolled = $pdo->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
$enrolled->execute([$user_id]);
$enrolled_ids = array_column($enrolled->fetchAll(), 'course_id');

// === ENROLL GRATIS ===
if (isset($_GET['enroll_gratis'])) {
    $course_id = (int)$_GET['enroll_gratis'];
    $cek = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND tipe = 'gratis'");
    $cek->execute([$course_id]);
    if ($cek->rowCount() > 0 && !in_array($course_id, $enrolled_ids)) {
        $pdo->prepare("INSERT INTO enrollments (user_id, course_id, progress) VALUES (?, ?, 0)")
            ->execute([$user_id, $course_id]);
    }
    header("Location: payment.php?sukses=enroll_gratis");
    exit;
}

// === PROSES PEMBAYARAN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar'])) {
    $course_id = (int)$_POST['course_id'];
    $metode    = trim($_POST['metode']);
    $course_data = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND tipe = 'berbayar'");
    $course_data->execute([$course_id]);
    $course_info = $course_data->fetch();

    if (!$course_info) {
        $error = "Kursus tidak ditemukan!";
    } elseif (empty($metode)) {
        $error = "Pilih metode pembayaran!";
    } elseif (in_array($course_id, $enrolled_ids)) {
        $error = "Kamu sudah terdaftar di kursus ini!";
    } else {
        $invoice = 'INV-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $pdo->prepare("INSERT INTO payments (user_id, course_id, jumlah, metode, status, invoice) VALUES (?, ?, ?, ?, 'sukses', ?)")
            ->execute([$user_id, $course_id, $course_info['harga'], $metode, $invoice]);
        $pdo->prepare("INSERT INTO enrollments (user_id, course_id, progress) VALUES (?, ?, 0)")
            ->execute([$user_id, $course_id]);
        header("Location: payment.php?sukses=bayar&invoice=$invoice");
        exit;
    }
}

if (isset($_GET['sukses'])) {
    if ($_GET['sukses'] === 'enroll_gratis') $success = "✅ Berhasil mendaftar kursus gratis! Cek My Course.";
    if ($_GET['sukses'] === 'bayar') $success = "✅ Pembayaran berhasil! Kursus sudah bisa diakses di My Course. Invoice: " . ($_GET['invoice'] ?? '');
}

// === AMBIL SEMUA KURSUS ===
$semua_kursus = $pdo->query("SELECT * FROM courses ORDER BY tipe ASC, harga ASC")->fetchAll();

// === RIWAYAT PEMBAYARAN ===
$riwayat = $pdo->prepare("
    SELECT p.*, c.judul FROM payments p
    JOIN courses c ON p.course_id = c.id
    WHERE p.user_id = ? ORDER BY p.created_at DESC
");
$riwayat->execute([$user_id]);
$riwayat_list = $riwayat->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - SkillNex</title>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/payment.css">
    <style>
        /* Fix teks di background putih */
        .riwayat-title, 
        h2, h3 {
            color: #1a202c !important;
        }

        .riwayat-section,
        .payment-history,
        .history-box {
            color: #4a5568 !important;
        }
        html,
        body,
        .container,
        .main {
            background: #ffffff !important;
        }
        .alert-success { background:#1a4731; color:#6fcf97; padding:14px 18px; border-radius:10px; margin-bottom:16px; border-left:4px solid #27ae60; font-size:14px; }
        .alert-error { background:#4a1515; color:#eb5757; padding:14px 18px; border-radius:10px; margin-bottom:16px; border-left:4px solid #e53e3e; font-size:14px; }

        /* Badges */
        .badge-gratis { display:inline-block; background:#e1f5ee; color:#0f6e56; border:1px solid #1d9e75; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; margin-left:6px; }
        .badge-berbayar { display:inline-block; background:#faeeda; color:#ba7517; border:1px solid #ef9f27; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; margin-left:6px; }
        .badge-enrolled {
            display:inline-block; background:#eef2ff; color:#534ab7;
            border:1px solid #7f77dd; padding:2px 10px; border-radius:20px;
            font-size:11px; font-weight:700; margin-left:6px;
            cursor:pointer; text-decoration:none;
            transition: all 0.2s;
        }
        .badge-enrolled:hover { background:#534ab7; color:white; }

        /* Kursus grid */
        .kursus-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px; margin-bottom:28px; }
        .kursus-card { background:#0f2239; border:1px solid #1e3a5f; border-radius:14px; padding:16px; transition:all 0.3s; color:white; }
        .kursus-card:hover { border-color:#2d5a8e; transform:translateY(-3px); }
        .kursus-thumb { width:100%; height:100px; object-fit:cover; border-radius:8px; margin-bottom:10px; background:#1e3a5f; }
        .kursus-thumb-empty { width:100%; height:100px; background:#1e3a5f; border-radius:8px; margin-bottom:10px; display:flex; align-items:center; justify-content:center; font-size:30px; }
        .kursus-judul { font-size:13px; font-weight:600; color:#fff; margin-bottom:4px; }
        .kursus-author { font-size:12px; color:#7a8ea8; margin-bottom:8px; }
        .kursus-harga { font-size:15px; font-weight:700; color:#5b9cf5; margin-bottom:10px; }
        .btn-gratis { width:100%; padding:9px; border:none; border-radius:8px; background:linear-gradient(135deg,#1d9e75,#0f6e56); color:white; font-weight:600; font-size:13px; cursor:pointer; font-family:inherit; transition:all 0.3s; }
        .btn-beli { width:100%; padding:9px; border:none; border-radius:8px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; font-weight:600; font-size:13px; cursor:pointer; font-family:inherit; transition:all 0.3s; }
        .btn-gratis:hover, .btn-beli:hover { transform:translateY(-1px); opacity:0.9; }
        .btn-mycourse {
            width:100%; padding:9px; border:none; border-radius:8px;
            background:linear-gradient(135deg,#534ab7,#7f77dd);
            color:white; font-weight:600; font-size:13px;
            cursor:pointer; font-family:inherit; transition:all 0.3s;
            text-decoration:none; display:block; text-align:center;
        }
        .btn-mycourse:hover { transform:translateY(-1px); opacity:0.9; }

        /* Tab filter */
        .tab-filter { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
        .tab-filter button { padding:7px 18px; border-radius:20px; border:2px solid #2d5a8e; background:none; color:#9eafc4; font-size:13px; cursor:pointer; font-family:inherit; transition:all 0.3s; }
        .tab-filter button.active-tab { background:#667eea; border-color:#667eea; color:white; font-weight:600; }

        /* Modal pilih metode */
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:2000; justify-content:center; align-items:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:#0f2239; border:1px solid #2d5a8e; border-radius:16px; padding:28px; width:90%; max-width:460px; color:white; }
        .modal-close { float:right; background:none; border:none; color:#7a8ea8; font-size:22px; cursor:pointer; }
        .modal-harga { font-size:24px; font-weight:700; color:#5b9cf5; margin:10px 0 18px; }
        .metode-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px; }
        .metode-item { background:#1e3a5f; border:2px solid #2d5a8e; border-radius:10px; padding:12px 8px; text-align:center; cursor:pointer; transition:all 0.3s; }
        .metode-item:hover { border-color:#667eea; }
        .metode-item.selected { border-color:#667eea; background:#2d3f7a; }
        .metode-nama { font-size:13px; font-weight:600; color:#fff; }
        .metode-info { font-size:11px; color:#7a8ea8; margin-top:2px; }
        .btn-lanjut { width:100%; padding:12px; border:none; border-radius:10px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; font-weight:600; font-size:15px; cursor:pointer; font-family:inherit; transition:all 0.3s; }
        .btn-lanjut:disabled { background:#2d5a8e; color:#7a8ea8; cursor:not-allowed; }
        .btn-lanjut:not(:disabled):hover { transform:translateY(-2px); opacity:0.9; }

        /* Modal detail bayar */
        .konfirmasi-box { background:#0f2239; border:1px solid #2d5a8e; border-radius:16px; padding:28px; width:90%; max-width:420px; color:white; }
        .detail-bayar { background:#1e3a5f; border-radius:12px; padding:16px; margin:16px 0; }
        .detail-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #2d5a8e; font-size:14px; }
        .detail-row:last-child { border-bottom:none; }
        .detail-label { color:#7a8ea8; }
        .detail-value { color:#fff; font-weight:600; }
        .detail-value.highlight { color:#5b9cf5; font-size:16px; }
        .qris-box { background:#fff; border-radius:12px; padding:20px; text-align:center; margin:14px 0; }
        .qris-pattern { width:120px; height:120px; margin:0 auto; background:repeating-conic-gradient(#000 0% 25%, #fff 0% 50%) 0 0/16px 16px; border:8px solid #000; border-radius:4px; }
        .countdown { text-align:center; color:#ef9f27; font-size:13px; margin-top:8px; font-weight:600; }
        .btn-konfirmasi-final { width:100%; padding:13px; border:none; border-radius:10px; background:linear-gradient(135deg,#1d9e75,#0f6e56); color:white; font-weight:700; font-size:15px; cursor:pointer; font-family:inherit; margin-top:6px; transition:all 0.3s; }
        .btn-konfirmasi-final:hover { transform:translateY(-2px); opacity:0.9; }
        .btn-back-modal { width:100%; padding:10px; border:1px solid #2d5a8e; border-radius:10px; background:none; color:#9eafc4; font-size:14px; cursor:pointer; font-family:inherit; margin-top:8px; }
        .btn-back-modal:hover { border-color:#667eea; color:white; }
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
        <a href="mycourse.php">My Course</a>
        <a href="message.php">Message</a>
        <a href="community.php">Community</a>
        <a href="forum.php">Forum</a>
        <a href="livechat.php">Live Chat</a>
        <a href="payment.php" class="active">Payment</a>
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

    <input type="text" class="search" id="searchKursus"
           placeholder="Cari kursus, misal: gitar, desain, programming..."
           oninput="filterKursus(this.value)">

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tab filter -->
    <div class="tab-filter">
        <button class="active-tab" onclick="filterTab('semua', this)">Semua Kursus</button>
        <button onclick="filterTab('gratis', this)">🆓 Gratis</button>
        <button onclick="filterTab('berbayar', this)">💳 Berbayar</button>
    </div>

    <!-- Grid kursus -->
    <div class="kursus-grid" id="kursusGrid">
        <?php foreach ($semua_kursus as $k): ?>
            <?php
            $sudah = in_array($k['id'], $enrolled_ids);
            $thumb = !empty($k['thumbnail']) ? '../assets/' . $k['thumbnail'] : '../assets/img/logoo.png';
            ?>
            <div class="kursus-card" data-tipe="<?= $k['tipe'] ?>" data-judul="<?= strtolower($k['judul']) ?>">
                <?php if ($thumb): ?>
                    <img src="<?= htmlspecialchars($thumb) ?>" class="kursus-thumb" alt="thumbnail" onerror="this.style.display='none'">
                <?php else: ?>
                    <div class="kursus-thumb-empty">📚</div>
                <?php endif; ?>

                <div class="kursus-judul">
                    <?= htmlspecialchars($k['judul']) ?>
                    <?php if ($sudah): ?>
                        <!-- Badge enrolled = link ke mycourse -->
                        <a href="mycourse.php" class="badge-enrolled" title="Lihat di My Course">✓ Enrolled</a>
                    <?php elseif ($k['tipe'] === 'gratis'): ?>
                        <span class="badge-gratis">GRATIS</span>
                    <?php else: ?>
                        <span class="badge-berbayar">BERBAYAR</span>
                    <?php endif; ?>
                </div>
                <div class="kursus-author">👤 <?= htmlspecialchars($k['author'] ?? 'SkillNex') ?></div>
                <div class="kursus-harga">
                    <?= $k['tipe'] === 'gratis' ? 'Gratis' : 'Rp ' . number_format($k['harga'], 0, ',', '.') ?>
                </div>

                <?php if ($sudah): ?>
                    <!-- Tombol juga ke mycourse -->
                    <a href="mycourse.php" class="btn-mycourse">📚 Lihat di My Course</a>
                <?php elseif ($k['tipe'] === 'gratis'): ?>
                    <a href="payment.php?enroll_gratis=<?= $k['id'] ?>">
                        <button class="btn-gratis">🆓 Daftar Gratis</button>
                    </a>
                <?php else: ?>
                    <button class="btn-beli"
                        onclick="bukaModalMetode(<?= $k['id'] ?>, '<?= addslashes($k['judul']) ?>', <?= $k['harga'] ?>)">
                        💳 Beli Kursus
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Riwayat pembayaran -->
    <h2 class="section-title">Riwayat pembayaran</h2>
    <?php if (empty($riwayat_list)): ?>
        <div style="text-align:center; padding:24px; color:#7a8ea8; font-size:14px; background:#0f2239; border-radius:12px; border:1px dashed #2d5a8e;">
            Belum ada riwayat pembayaran.
        </div>
    <?php else: ?>
        <?php foreach ($riwayat_list as $r): ?>
            <div class="history-card">
                <div class="left">
                    <div class="img-box"></div>
                    <div>
                        <h3><?= htmlspecialchars($r['judul']) ?></h3>
                        <p><?= date('d M Y H:i', strtotime($r['created_at'])) ?> · <?= htmlspecialchars($r['metode']) ?> · <?= $r['invoice'] ?></p>
                    </div>
                </div>
                <div class="right">
                    <h3>Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></h3>
                    <span class="status <?= $r['status'] === 'sukses' ? 'success' : 'pending' ?>">
                        <?= strtoupper($r['status']) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- ===== MODAL 1: PILIH METODE ===== -->
<div class="modal-overlay" id="modalMetode">
    <div class="modal-box">
        <button class="modal-close" onclick="tutupModal('modalMetode')">✕</button>
        <h3 id="m1Judul">Beli Kursus</h3>
        <div class="modal-harga" id="m1Harga">Rp 0</div>
        <p style="font-size:13px; color:#7a8ea8; margin-bottom:12px;">Pilih metode pembayaran:</p>
        <div class="metode-grid">
            <div class="metode-item" onclick="pilihMetode('OVO', this)">
                <div class="metode-nama">OVO</div>
                <div class="metode-info">No Fee</div>
            </div>
            <div class="metode-item" onclick="pilihMetode('DANA', this)">
                <div class="metode-nama">DANA</div>
                <div class="metode-info">Virtual Account</div>
            </div>
            <div class="metode-item" onclick="pilihMetode('GoPay', this)">
                <div class="metode-nama">GoPay</div>
                <div class="metode-info">QRIS, In App</div>
            </div>
            <div class="metode-item" onclick="pilihMetode('Virtual Account', this)">
                <div class="metode-nama">Virtual Account</div>
                <div class="metode-info">Bank Transfer</div>
            </div>
            <div class="metode-item" onclick="pilihMetode('Debit/Credit', this)">
                <div class="metode-nama">Debit/Credit</div>
                <div class="metode-info">Visa/Mastercard</div>
            </div>
            <div class="metode-item" onclick="pilihMetode('QRIS', this)">
                <div class="metode-nama">QRIS</div>
                <div class="metode-info">Scan & Bayar</div>
            </div>
        </div>
        <button class="btn-lanjut" id="btnLanjut" disabled onclick="bukaDetailBayar()">Lanjut →</button>
    </div>
</div>

<!-- ===== MODAL 2: DETAIL PEMBAYARAN ===== -->
<div class="modal-overlay" id="modalDetail">
    <div class="konfirmasi-box">
        <button class="modal-close" onclick="tutupModal('modalDetail')">✕</button>
        <h3 id="m2Title">Detail Pembayaran</h3>
        <div id="detailKonten"></div>
        <form method="POST" action="" id="formBayarFinal">
            <input type="hidden" name="course_id" id="finalCourseId">
            <input type="hidden" name="metode" id="finalMetode">
            <input type="hidden" name="bayar" value="1">
            <button type="submit" class="btn-konfirmasi-final">✅ Konfirmasi & Bayar Sekarang</button>
        </form>
        <button class="btn-back-modal" onclick="kembaliKeMetode()">← Ganti Metode</button>
    </div>
</div>

<script>
let selectedCourseId = 0, selectedJudul = '', selectedHarga = 0, selectedMetode = '';

function bukaModalMetode(courseId, judul, harga) {
    selectedCourseId = courseId; selectedJudul = judul; selectedHarga = harga; selectedMetode = '';
    document.getElementById('m1Judul').textContent = '💳 ' + judul;
    document.getElementById('m1Harga').textContent = 'Rp ' + harga.toLocaleString('id-ID');
    document.getElementById('btnLanjut').disabled = true;
    document.querySelectorAll('.metode-item').forEach(m => m.classList.remove('selected'));
    document.getElementById('modalMetode').classList.add('active');
}

function pilihMetode(metode, el) {
    selectedMetode = metode;
    document.querySelectorAll('.metode-item').forEach(m => m.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('btnLanjut').disabled = false;
}

function bukaDetailBayar() {
    document.getElementById('finalCourseId').value = selectedCourseId;
    document.getElementById('finalMetode').value   = selectedMetode;
    const hargaFmt = 'Rp ' + selectedHarga.toLocaleString('id-ID');
    const detailUmum = `<div class="detail-bayar">
        <div class="detail-row"><span class="detail-label">Kursus</span><span class="detail-value">${selectedJudul}</span></div>
        <div class="detail-row"><span class="detail-label">Total Bayar</span><span class="detail-value highlight">${hargaFmt}</span></div>
    </div>`;
    let konten = '';
    if (selectedMetode === 'OVO') {
        konten = detailUmum + `<div class="detail-bayar">
            <div class="detail-row"><span class="detail-label">Metode</span><span class="detail-value">OVO</span></div>
            <div class="detail-row"><span class="detail-label">Nomor OVO</span><span class="detail-value highlight">0812-3456-7890</span></div>
            <div class="detail-row"><span class="detail-label">Atas Nama</span><span class="detail-value">SkillNex Official</span></div>
            <div class="detail-row"><span class="detail-label">Berlaku</span><span class="detail-value">30 menit</span></div>
        </div><div class="countdown">⏱ Selesaikan pembayaran sebelum waktu habis</div>`;
    } else if (selectedMetode === 'DANA') {
        konten = detailUmum + `<div class="detail-bayar">
            <div class="detail-row"><span class="detail-label">Metode</span><span class="detail-value">DANA</span></div>
            <div class="detail-row"><span class="detail-label">Nomor DANA</span><span class="detail-value highlight">0821-9876-5432</span></div>
            <div class="detail-row"><span class="detail-label">Atas Nama</span><span class="detail-value">SkillNex Official</span></div>
            <div class="detail-row"><span class="detail-label">Berlaku</span><span class="detail-value">30 menit</span></div>
        </div><div class="countdown">⏱ Selesaikan pembayaran sebelum waktu habis</div>`;
    } else if (selectedMetode === 'GoPay') {
        konten = detailUmum + `<div class="detail-bayar">
            <div class="detail-row"><span class="detail-label">Metode</span><span class="detail-value">GoPay</span></div>
            <div class="detail-row"><span class="detail-label">Nomor GoPay</span><span class="detail-value highlight">0856-1234-5678</span></div>
            <div class="detail-row"><span class="detail-label">Atas Nama</span><span class="detail-value">SkillNex Official</span></div>
        </div><p style="font-size:12px;color:#7a8ea8;margin:8px 0 4px;">Atau scan QR berikut:</p>
        <div class="qris-box"><div class="qris-pattern"></div><p style="color:#333;font-size:12px;margin-top:8px;">GoPay - SkillNex</p></div>`;
    } else if (selectedMetode === 'Virtual Account') {
        konten = detailUmum + `<div class="detail-bayar">
            <div class="detail-row"><span class="detail-label">Metode</span><span class="detail-value">Virtual Account BCA</span></div>
            <div class="detail-row"><span class="detail-label">No. VA</span><span class="detail-value highlight">8277 0012 3456 7890</span></div>
            <div class="detail-row"><span class="detail-label">Atas Nama</span><span class="detail-value">SkillNex Official</span></div>
            <div class="detail-row"><span class="detail-label">Berlaku</span><span class="detail-value">24 jam</span></div>
        </div><div class="countdown">⏱ Transfer sesuai nominal untuk verifikasi otomatis</div>`;
    } else if (selectedMetode === 'Debit/Credit') {
        konten = detailUmum + `<div class="detail-bayar">
            <div class="detail-row"><span class="detail-label">Metode</span><span class="detail-value">Debit / Credit Card</span></div>
            <div class="detail-row"><span class="detail-label">Diterima</span><span class="detail-value">Visa · Mastercard · JCB</span></div>
            <div class="detail-row"><span class="detail-label">Keamanan</span><span class="detail-value">🔒 SSL Encrypted</span></div>
        </div>`;
    } else if (selectedMetode === 'QRIS') {
        konten = detailUmum + `<p style="font-size:13px;color:#7a8ea8;margin:10px 0 6px;">Scan QR Code berikut:</p>
        <div class="qris-box"><div class="qris-pattern"></div><p style="color:#333;font-size:12px;margin-top:8px;">SkillNex · ${hargaFmt}</p></div>
        <div class="countdown">⏱ QR berlaku selama 10 menit</div>`;
    }
    document.getElementById('detailKonten').innerHTML = konten;
    document.getElementById('m2Title').textContent = '🧾 ' + selectedMetode + ' — Detail Pembayaran';
    tutupModal('modalMetode');
    document.getElementById('modalDetail').classList.add('active');
}

function kembaliKeMetode() { tutupModal('modalDetail'); document.getElementById('modalMetode').classList.add('active'); }
function tutupModal(id) { document.getElementById(id).classList.remove('active'); }

document.addEventListener('click', function(e) {
    ['modalMetode','modalDetail'].forEach(id => {
        const modal = document.getElementById(id);
        if (e.target === modal) modal.classList.remove('active');
    });
});

function filterTab(tipe, btn) {
    document.querySelectorAll('.tab-filter button').forEach(b => b.classList.remove('active-tab'));
    btn.classList.add('active-tab');
    document.querySelectorAll('.kursus-card').forEach(card => {
        card.style.display = (tipe === 'semua' || card.dataset.tipe === tipe) ? '' : 'none';
    });
}

function filterKursus(keyword) {
    keyword = keyword.toLowerCase();
    document.querySelectorAll('.kursus-card').forEach(card => {
        card.style.display = card.dataset.judul.includes(keyword) ? '' : 'none';
    });
}
</script>
</body>
</html>