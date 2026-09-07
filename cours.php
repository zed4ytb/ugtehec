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

// Traitement ajout de cours (Admin seulement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_cours']) && $user['role'] === 'admin') {
    $titre = trim($_POST['titre']);
    $matiere = trim($_POST['matiere']);
    $niveau = trim($_POST['niveau']);
    
    if (isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['fichier_pdf']['name'];
        $file_tmp = $_FILES['fichier_pdf']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext === 'pdf') {
            $new_file_name = uniqid('cours_', true) . '.pdf';
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $file_path = $upload_dir . $new_file_name;
                $ins_stmt = $pdo->prepare("INSERT INTO cours (titre, matiere, niveau, fichier, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $ins_stmt->execute([$titre, $matiere, $niveau, $file_path, $user['nom']]);
                header("Location: cours.php?success=1");
                exit();
            }
        }
    }
}

// Suppression de cours (Admin seulement)
if (isset($_GET['delete_cours']) && $user['role'] === 'admin') {
    $cours_id = $_GET['delete_cours'];
    $get_file = $pdo->prepare("SELECT fichier FROM cours WHERE id = ?");
    $get_file->execute([$cours_id]);
    $cours_item = $get_file->fetch();
    if ($cours_item) {
        if (file_exists($cours_item['fichier'])) { unlink($cours_item['fichier']); }
        $pdo->prepare("DELETE FROM cours WHERE id = ?")->execute([$cours_id]);
    }
    header("Location: cours.php");
    exit();
}

$cours_stmt = $pdo->query("SELECT * FROM cours ORDER BY id DESC");
$liste_cours = $cours_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Cours & PDF - UGTE IHEC Carthage</title>
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
        
        .form-card { background: var(--actu-bg); border: 1px solid var(--card-border); padding: 25px; border-radius: 18px; margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 6px; font-weight: 500; }
        input[type="text"], select, input[type="file"] { width: 100%; padding: 10px 14px; background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; color: var(--text-color); font-size: 14px; }
        input[type="file"] { padding: 8px; cursor: pointer; }
        .btn-submit { background: var(--btn-gradient); color: white; padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        
        .cours-actions { display: flex; gap: 10px; align-items: center; margin-top: 15px; }
        .btn-download { background: var(--btn-gradient); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-block; }
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 8px 12px; border-radius: 8px; font-size: 12px; text-decoration: none; }
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
        <h2 class="section-title">📚 Cours, Résumés et Documents PDF</h2>

        <?php if ($user['role'] === 'admin'): ?>
            <div class="form-card">
                <h4 style="color: var(--text-color); margin-bottom: 15px;">➕ Ajouter un nouveau cours</h4>
                <form action="cours.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Titre du cours / Résumé</label>
                            <input type="text" name="titre" required placeholder="Ex: Résumé Économie Monétaire">
                        </div>
                        <div class="form-group">
                            <label>Matière</label>
                            <input type="text" name="matiere" required placeholder="Ex: Économie, Management...">
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Niveau</label>
                            <select name="niveau">
                                <option value="1ère Année">1ère Année</option>
                                <option value="2ème Année">2ème Année</option>
                                <option value="3ème Année">3ème Année</option>
                                <option value="Mastère">Mastère</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fichier PDF</label>
                            <input type="file" name="fichier_pdf" accept=".pdf" required>
                        </div>
                    </div>
                    <button type="submit" name="ajouter_cours" class="btn-submit">Mettre en ligne</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="actu-grid">
            <?php if (!empty($liste_cours)): ?>
                <?php foreach ($liste_cours as $c): ?>
                    <div class="actu-card">
                        <span class="actu-date" style="background: rgba(59, 130, 246, 0.12); padding: 3px 10px; border-radius: 12px; width: fit-content; display: inline-block;">
                            <?php echo htmlspecialchars($c['matiere']); ?> — <?php echo htmlspecialchars($c['niveau']); ?>
                        </span>
                        <h4 style="margin-top: 10px;"><?php echo htmlspecialchars($c['titre']); ?></h4>
                        <span style="font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 12px;">Ajouté par : <?php echo htmlspecialchars($c['uploaded_by'] ?? 'Admin'); ?></span>
                        
                        <div class="cours-actions">
                            <a href="<?php echo htmlspecialchars($c['fichier']); ?>" class="btn-download" target="_blank">📥 Télécharger le PDF</a>
                            <?php if ($user['role'] === 'admin'): ?>
                                <a href="cours.php?delete_cours=<?php echo $c['id']; ?>" class="btn-delete" onclick="return confirm('Voulez-vous supprimer ce cours ?');">Supprimer</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="actu-card" style="text-align: center; color: var(--text-muted);">
                    Aucun cours n'a été publié pour le moment.
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