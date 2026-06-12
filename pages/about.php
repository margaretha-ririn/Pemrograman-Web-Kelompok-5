<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillNex - About</title>
    <link rel="stylesheet" href="../assets/css/about.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        /* ── VIDEO BACKGROUND HERO ── */
        .hero {
            position: relative;
            overflow: hidden;
        }
        .hero {
    position: relative;
    overflow: hidden;
    padding-top: 72px; /* ← tambahkan baris ini saja */
}
        /* ── NAVBAR TRANSPARAN ── */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 100;
    box-sizing: border-box;
   background: transparent;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    transition: background 0.3s ease;
}
        .hero-video-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .hero-video-bg video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .hero-video-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
        }
        .hero-content {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../assets/img/logoo.png" alt="SkillNex Logo">
            <span>SkillNex</span>
        </div>
        <div class="nav-links">
            <a class="active">About</a>
            <a href="../login.php">Log In</a>
        </div>
    </nav>

    <section class="hero">
        <!-- Video sebagai latar belakang hero -->
        <div class="hero-video-bg">
            <video autoplay muted loop playsinline>
                <source src="../assets/videoskillnex.mp4" type="video/mp4">
            </video>
        </div>

        <div class="hero-content">
            <div class="hero-badge">🚀 Platform Pembelajaran #1 di Indonesia</div>
            <h1 class="hero-title">Selamat Datang di <span class="gradient-text">SkillNex</span></h1>
            <p class="hero-subtitle">Tempat di mana skill bertemu dengan peluang, dan pembelajaran menjadi petualangan yang tak terlupakan</p>
            <div class="hero-buttons">
                <a href="../login.php" class="btn-primary">Mulai Belajar</a>
            </div>
        </div>
    </section>

    <section class="about-section">
        <div class="about-container">
            <div class="about-logo-section">
                <img src="../assets/img/logoo.png" alt="SkillNex Logo" class="about-logo">
                <h2>Tentang SkillNex</h2>
            </div>
            <p class="about-description">
                SkillNex adalah platform inovatif yang menghubungkan individu dari berbagai bidang keahlian di seluruh dunia. 
                Di sini, setiap orang dapat menampilkan skill mereka, berbagi pengetahuan, serta belajar dari keahlian orang lain.
                Website ini dirancang sebagai ruang interaktif bagi komunitas pembelajaran dan profesional yang ingin berkembang bersama. 
                Pengguna dapat membuat profil keahlian, mengunggah portofolio, dan berinteraksi melalui forum diskusi atau sesi berbagi langsung.
            </p>
        </div>
    </section>

    <section class="stats-section">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-number">10,000+</div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-number">500+</div>
            <div class="stat-label">Online Courses</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-number">98%</div>
            <div class="stat-label">Success Rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-number">4.9/5</div>
            <div class="stat-label">User Rating</div>
        </div>
    </section>

    <section class="vision-mission">
        <div class="vm-card">
            <div class="vm-icon">
                <img src="../assets/img/buku.png" alt="Vision Icon">
            </div>
            <div class="vm-content">
                <h3>Visi Kami</h3>
                <p>Membangun dunia digital di mana setiap individu dapat tumbuh bersama melalui berbagi ilmu dan kolaborasi yang bermakna.</p>
            </div>
        </div>
        <div class="vm-card">
            <div class="vm-icon">
                <img src="../assets/img/roket.png" alt="Mission Icon">
            </div>
            <div class="vm-content">
                <h3>Misi Kami</h3>
                <p>Menyatukan berbagai keahlian dalam satu platform untuk menciptakan ekosistem pembelajaran yang terbuka, inspiratif, dan bermanfaat bagi semua.</p>
            </div>
        </div>
    </section>

    <section class="features-section">
        <h2 class="section-title">Mengapa Memilih SkillNex?</h2>
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon">💡</div>
                <h4>Pembelajaran Interaktif</h4>
                <p>Belajar dengan metode interaktif yang menyenangkan dan efektif</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h4>Mentor Berpengalaman</h4>
                <p>Didampingi oleh mentor profesional di bidangnya</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h4>Akses Fleksibel</h4>
                <p>Belajar kapan saja, di mana saja, sesuai ritme Anda</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤝</div>
                <h4>Komunitas Aktif</h4>
                <p>Bergabung dengan ribuan learner dari seluruh Indonesia</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h4>Progress Tracking</h4>
                <p>Pantau perkembangan belajar Anda secara real-time</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎓</div>
                <h4>Sertifikat Resmi</h4>
                <p>Dapatkan sertifikat yang diakui industri</p>
            </div>
        </div>
    </section>

    <section class="quote-section">
        <div class="quote-icon">💬</div>
        <blockquote class="quote-text">
            "Setiap skill memiliki cerita, dan setiap cerita memiliki kekuatan untuk mengubah cara pandang seseorang. 
            Dengan berbagi ilmu, kita tak hanya membentuk pengalaman baru, tetapi juga memperkaya wawasan bagi diri sendiri dan orang lain."
        </blockquote>
        <div class="quote-author">— Tim SkillNex</div>
    </section>

    <section class="testimonials-section">
        <h2 class="section-title">Apa Kata Mereka?</h2>
        <div class="testimonials-container">
            <div class="testimonial-card">
                <div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
                <p class="testimonial-text">"Platform yang luar biasa! Saya berhasil menguasai web development dalam 3 bulan."</p>
                <div class="testimonial-author">
                    <img src="../assets/img/dhe.jpg" alt="dhea">
                    <div>
                        <div class="author-name">Dhea Tri</div>
                        <div class="author-title">Full Stack Developer</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
                <p class="testimonial-text">"Mentor-mentornya sangat supportive dan materinya mudah dipahami!"</p>
                <div class="testimonial-author">
                    <img src="../assets/img/tegar.jpg" alt="tegar">
                    <div>
                        <div class="author-name">Tegar Madya</div>
                        <div class="author-title">UI/UX Designer</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
                <p class="testimonial-text">"Investasi terbaik untuk pengembangan karir saya. Highly recommended!"</p>
                <div class="testimonial-author">
                    <img src="../assets/img/sultan.jpg" alt="Putri">
                    <div>
                        <div class="author-name">TRIXXX</div>
                        <div class="author-title">Data Analyst</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <h2>Siap Memulai Perjalanan Belajar Anda?</h2>
        <p>Bergabunglah dengan ribuan learner lainnya dan kembangkan skill Anda hari ini!</p>
         <a href="../login.php"  class="btn-cta">Daftar Sekarang - Gratis!</a>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>SkillNex</h4>
                <p>Platform pembelajaran skill terbaik untuk masa depan yang lebih cerah.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="#">Home</a>
                <a href="#">Courses</a>
                <a href="#">Mentors</a>
                <a href="#">Community</a>
            </div>
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>📧 Skillnex@gmail.com</p>
                <p>📱 +062 211111</p>
                <p>🐙 github.com/Skill-Nex</p>
                <p>📱 @SkillNex</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 SkillNex. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>