<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - UGTE IHEC Carthage</title>
    <style>
        :root {
            --bg-gradient: radial-gradient(circle at top right, #111e38, #070b14);
            --text-color: #f1f5f9;
            --navbar-bg: rgba(17, 30, 56, 0.85);
            --navbar-border: rgba(27, 42, 71, 0.8);
            --card-bg: rgba(17, 30, 56, 0.5);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-muted: #94a3b8;
            --text-sub: #cbd5e1;
            --info-box-bg: rgba(7, 11, 20, 0.6);
            --ihec-blue: #3b82f6;
            --btn-gradient: linear-gradient(135deg, #1b2a47, #2563eb);
            --dropdown-bg: rgba(17, 30, 56, 0.95);
        }

        body.light-mode {
            --bg-gradient: linear-gradient(135deg, #f8fafc, #edf2f7);
            --text-color: #0f172a;
            --navbar-bg: rgba(255, 255, 255, 0.9);
            --navbar-border: rgba(27, 42, 71, 0.12);
            --card-bg: rgba(255, 255, 255, 0.9);
            --card-border: rgba(27, 42, 71, 0.1);
            --text-muted: #64748b;
            --text-sub: #334155;
            --info-box-bg: rgba(241, 245, 249, 0.8);
            --ihec-blue: #1d4ed8;
            --btn-gradient: linear-gradient(135deg, #1b2a47, #1d4ed8);
            --dropdown-bg: rgba(255, 255, 255, 0.95);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s; }
        body {
            background: var(--bg-gradient);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 50px;
        }

        navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: var(--navbar-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--navbar-border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }
        .logo-container { display: flex; align-items: center; gap: 10px; }
        .logo-img {
            height: 42px; width: 42px; object-fit: contain; border-radius: 8px;
            background: rgba(255, 255, 255, 0.05); padding: 2px; border: 1px solid var(--navbar-border);
        }
        .nav-title-group h1 { font-size: 16px; color: var(--text-color); font-weight: 700; letter-spacing: -0.3px; }
        .nav-title-group span { font-size: 12px; color: var(--ihec-blue); font-weight: 500; display: block; }

        .nav-links { display: flex; align-items: center; gap: 15px; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover { color: var(--ihec-blue); }

        /* Dropdown Menu Style (3 tirets ☰) avec Support رجع */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropbtn {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-color);
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dropbtn:hover { color: var(--ihec-blue); border-color: var(--ihec-blue); }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: var(--dropdown-bg);
            min-width: 210px;
            box-shadow: 0px 15px 35px rgba(0,0,0,0.2);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            z-index: 1001;
            backdrop-filter: blur(16px);
            overflow: hidden;
            margin-top: 8px;
        }
        .dropdown-content a {
            color: var(--text-color) !important;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 1px solid var(--card-border);
        }
        .dropdown-content a:last-child { border-bottom: none; }
        .dropdown-content a:hover {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--ihec-blue) !important;
        }
        .dropdown.active .dropdown-content { display: block; }
        
        .btn-admin-special {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white !important;
            border: 1px solid rgba(239, 68, 68, 0.4);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600 !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-admin-special:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-logout { background-color: rgba(239, 68, 68, 0.15) !important; border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5 !important; padding: 8px 16px; border-radius: 8px; }

        .theme-toggle {
            background: var(--card-bg); border: 1px solid var(--card-border); color: var(--text-color);
            padding: 8px 12px; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600;
            display: flex; align-items: center; gap: 6px;
        }
        .theme-toggle:hover { opacity: 0.8; }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .hero-card {
            background: var(--card-bg); backdrop-filter: blur(16px); border: 1px solid var(--card-border);
            padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(27, 42, 71, 0.15);
            text-align: center; margin-bottom: 40px;
        }
        .hero-card h2 { font-size: 28px; color: var(--ihec-blue); margin-bottom: 10px; font-weight: 700; }
        .hero-card p { color: var(--text-muted); font-size: 15px; margin-bottom: 30px; }

        .user-info-box {
            display: inline-block; background: var(--info-box-bg); border: 1px solid var(--card-border);
            padding: 20px 30px; border-radius: 16px; text-align: left; margin-bottom: 30px; width: 100%; max-width: 500px;
        }
        .user-info-box p { margin-bottom: 8px; color: var(--text-sub); font-size: 14px; }
        .user-info-box p strong { color: var(--text-color); }

        .actions { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
        .btn {
            background: var(--btn-gradient); color: white; padding: 10px 20px; border-radius: 10px;
            text-decoration: none; font-size: 14px; font-weight: 600; box-shadow: 0 10px 15px -3px rgba(27, 42, 71, 0.3);
        }
        .btn:hover { opacity: 0.9; }
        
        .badge-role { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 15px; }
        .badge-etudiant { background: rgba(29, 78, 216, 0.12); color: var(--ihec-blue); border: 1px solid rgba(29, 78, 216, 0.25); }
        .badge-admin { background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }

        @media(max-width: 768px) {
            navbar { padding: 15px 20px; flex-direction: column; gap: 15px; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

    <navbar>
        <a href="home.php" class="nav-brand">
            <div class="logo-container">
                <img src="ihec.jpg" alt="Logo IHEC Carthage" class="logo-img">
                <img src="ugte.jpg" alt="Logo UGTE" class="logo-img" style="border-radius: 50%;">
            </div>
            <div class="nav-title-group">
                <h1>UGTE IHEC Carthage</h1>
                <span>Plateforme Étudiante</span>
            </div>
        </a>

        <div class="nav-links">
            <a href="home.php" style="color: var(--ihec-blue); font-weight: 600;">Accueil</a>

            <!-- Dropdown Menu (مع إرجاع Support وعدم وجود Sondages) -->
            <div class="dropdown" id="navDropdown">
                <button class="dropbtn" onclick="toggleDropdown()">☰</button>
                <div class="dropdown-content">
                    <a href="profile.php">⚙️ Paramètre Profil</a>
                    <a href="actualites.php">📢 Actualités</a>
                    <a href="cours.php">📚 Cours & PDF</a>
                    <a href="support.php">🛠️ Support & Réclamations</a>
                </div>
            </div>
            
            <?php if ($user['role'] === 'admin'): ?>
                <a href="dashboard.php" class="btn-admin-special">⚡ Espace Admin</a>
            <?php endif; ?>
            
            <button class="theme-toggle" onclick="toggleTheme()">
                <span id="theme-icon">🌙</span>
            </button>

            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </navbar>

    <div class="container">
        <div class="hero-card">
            <?php if ($user['role'] === 'admin'): ?>
                <span class="badge-role badge-admin">Compte Administrateur</span>
            <?php else: ?>
                <span class="badge-role badge-etudiant">Compte Étudiant</span>
            <?php endif; ?>

            <h2>Ahla bik, <?php echo htmlspecialchars($user['nom']); ?> !</h2>
            <p>Bienvenue sur la plateforme officielle de l'UGTE IHEC Carthage.</p>

            <div class="user-info-box">
                <p>📧 <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p>🎓 <strong>Niveau:</strong> <?php echo htmlspecialchars($user['niveau']); ?></p>
                <p>📚 <strong>Filière:</strong> <?php echo htmlspecialchars($user['filiere']); ?></p>
            </div>

            <!-- زر البروفايل فقط (مع زر الأدمن لو موجود) -->
            <div class="actions">
                <a href="profile.php" class="btn">Modifier mon Profile</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="dashboard.php" class="btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);">Tableau de Bord Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('navDropdown');
            dropdown.classList.toggle('active');
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn')) {
                const dropdowns = document.getElementsByClassName("dropdown");
                for (let i = 0; i < dropdowns.length; i++) {
                    let openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('active')) {
                        openDropdown.classList.remove('active');
                    }
                }
            }
        }

        const body = document.body;
        const themeIcon = document.getElementById('theme-icon');

        if (localStorage.getItem('theme') === 'light') {
            body.classList.add('light-mode');
            themeIcon.textContent = '☀️';
        }

        function toggleTheme() {
            body.classList.toggle('light-mode');
            if (body.classList.contains('light-mode')) {
                localStorage.setItem('theme', 'light');
                themeIcon.textContent = '☀️';
            } else {
                localStorage.setItem('theme', 'dark');
                themeIcon.textContent = '🌙';
            }
        }
    </script>
</body>
</html>