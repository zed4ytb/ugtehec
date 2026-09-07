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

// Traitement ajout actualité (Admin seulement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_actu']) && $user['role'] === 'admin') {
    $titre = trim($_POST['titre']);
    $contenu = trim($_POST['contenu']);
    if (!empty($titre) && !empty($contenu)) {
        $ins = $pdo->prepare("INSERT INTO actualites (titre, contenu, date_publication) VALUES (?, ?, NOW())");
        $ins->execute([$titre, $contenu]);
        header("Location: actualites.php?success=1");
        exit();
    }
}

$actu_stmt = $pdo->query("SELECT * FROM actualites ORDER BY id DESC");
$actualites = $actu_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Actualités - UGTE IHEC Carthage</title>
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
            --actu-bg: rgba(17, 30, 56, 0.35);
            --input-bg: rgba(7, 11, 20, 0.6);
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
            --actu-bg: rgba(255, 255, 255, 0.8);
            --input-bg: rgba(255, 255, 255, 0.9);
            --ihec-blue: #1d4ed8;
            --btn-gradient: linear-gradient(135deg, #1b2a47, #1d4ed8);
            --dropdown-bg: rgba(255, 255, 255, 0.95);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; transition: background 0.3s, color 0.3s; }
        body { background: var(--bg-gradient); color: var(--text-color); min-height: 100vh; padding-bottom: 50px; }
        navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: var(--navbar-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--navbar-border); position: sticky; top: 0; z-index: 1000; }
        .nav-brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
        .logo-img { height: 42px; width: 42px; object-fit: contain; border-radius: 8px; border: 1px solid var(--navbar-border); }
        .nav-title-group h1 { font-size: 16px; color: var(--text-color); font-weight: 700; }
        .nav-title-group span { font-size: 12px; color: var(--ihec-blue); font-weight: 500; display: block; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; }
        .nav-links a:hover { color: var(--ihec-blue); }
        
        .dropdown { position: relative; display: inline-block; }
        .dropbtn { background: var(--card-bg); border: 1px solid var(--card-border); color: var(--text-color); padding: 8px 14px; border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: var(--dropdown-bg); min-width: 200px; box-shadow: 0px 15px 35px rgba(0,0,0,0.2); border: 1px solid var(--card-border); border-radius: 14px; z-index: 1001; margin-top: 8px; overflow: hidden; }
        .dropdown-content a { color: var(--text-color) !important; padding: 12px 16px; display: block; font-size: 14px; border-bottom: 1px solid var(--card-border); }
        .dropdown-content a:hover { background-color: rgba(59, 130, 246, 0.1); color: var(--ihec-blue) !important; }
        .dropdown.active .dropdown-content { display: block; }

        .admin-link { background: rgba(27, 42, 71, 0.1); border: 1px solid rgba(27, 42, 71, 0.2); color: var(--ihec-blue) !important; padding: 6px 14px; border-radius: 8px; font-weight: 600 !important; }
        .btn-logout { background-color: rgba(239, 68, 68, 0.15) !important; border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5 !important; padding: 8px 16px; border-radius: 8px; }
        .theme-toggle { background: var(--card-bg); border: 1px solid var(--card-border); color: var(--text-color); padding: 8px 12px; border-radius: 10px; cursor: pointer; font-weight: 600; }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; }
        .section-title { color: var(--text-color); font-size: 24px; margin-bottom: 25px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        
        .actu-grid { display: flex; flex-direction: column; gap: 20px; }
        .actu-card { background: var(--actu-bg); backdrop-filter: blur(12px); border: 1px solid var(--card-border); padding: 25px; border-radius: 18px; }
        .actu-card h4 { color: var(--text-color); font-size: 18px; margin-bottom: 8px; }
        .actu-date { font-size: 12px; color: var(--ihec-blue); margin-bottom: 12px; display: block; }
        .actu-content { color: var(--text-sub); font-size: 14px; line-height: 1.6; white-space: pre-line; }

        .form-card { background: var(--actu-bg); border: 1px solid var(--card-border); padding: 25px; border-radius: 18px; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 6px; font-weight: 500; }
        input[type="text"], textarea { width: 100%; padding: 10px 14px; background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; color: var(--text-color); font-size: 14px; }
        textarea { resize: vertical; height: 100px; }
        .btn-submit { background: var(--btn-gradient); color: white; padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

    <navbar>
        <a href="home.php" class="nav-brand">
            <div style="display: flex; gap: 10px;">
                <img src="ihec.jpg" alt="Logo" class="logo-img">
                <img src="ugte.jpg" alt="Logo" class="logo-img" style="border-radius: 50%;">
            </div>
            <div class="nav-title-group">
                <h1>UGTE IHEC Carthage</h1>
                <span>Plateforme Étudiante</span>
            </div>
        </a>

        <div class="nav-links">
            <a href="home.php">Accueil</a>
            <div class="dropdown" id="navDropdown">
                <button class="dropbtn" onclick="toggleDropdown()">☰</button>
                <div class="dropdown-content">
                    <a href="profile.php">⚙️ Paramètre Profil</a>
                    <a href="actualites.php">📢 Actualités</a>
                    <a href="cours.php">📚 Cours & PDF</a>
                </div>
            </div>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="dashboard.php" class="admin-link">⚙️ Espace Admin</a>
            <?php endif; ?>
            <button class="theme-toggle" onclick="toggleTheme()"><span id="theme-icon">🌙</span></button>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </navbar>

    <div class="container">
        <h2 class="section-title">📢 Actualités et Annonces Officielles</h2>

        <?php if ($user['role'] === 'admin'): ?>
            <div class="form-card">
                <h4 style="color: var(--text-color); margin-bottom: 15px;">➕ Publier une nouvelle actualité</h4>
                <form action="actualites.php" method="POST">
                    <div class="form-group">
                        <label>Titre de l'annonce</label>
                        <input type="text" name="titre" required placeholder="Ex: Report des examens...">
                    </div>
                    <div class="form-group">
                        <label>Contenu</label>
                        <textarea name="contenu" required placeholder="Détails de l'actualité..."></textarea>
                    </div>
                    <button type="submit" name="ajouter_actu" class="btn-submit">Publier</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="actu-grid">
            <?php if (!empty($actualites)): ?>
                <?php foreach ($actualites as $actu): ?>
                    <div class="actu-card">
                        <h4><?php echo htmlspecialchars($actu['titre']); ?></h4>
                        <span class="actu-date">Publié le : <?php echo $actu['date_publication']; ?></span>
                        <div class="actu-content"><?php echo htmlspecialchars($actu['contenu']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="actu-card" style="text-align: center; color: var(--text-muted);">
                    Aucune actualité n'a été publiée pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDropdown() { document.getElementById('navDropdown').classList.toggle('active'); }
        window.onclick = function(e) { if (!e.target.matches('.dropbtn')) { document.getElementById('navDropdown').classList.remove('active'); } }
        const body = document.body;
        if (localStorage.getItem('theme') === 'light') { body.classList.add('light-mode'); document.getElementById('theme-icon').textContent = '☀️'; }
        function toggleTheme() {
            body.classList.toggle('light-mode');
            localStorage.setItem('theme', body.classList.contains('light-mode') ? 'light' : 'dark');
            document.getElementById('theme-icon').textContent = body.classList.contains('light-mode') ? '☀️' : '🌙';
        }
    </script>
</body>
</html>