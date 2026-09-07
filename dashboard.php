<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();

    if (!$current_user || $current_user['role'] !== 'admin') {
        header("Location: home.php?error=unauthorized");
        exit();
    }

    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        if ($delete_id != $_SESSION['user_id']) {
            $del_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del_stmt->execute([$delete_id]);
        }
        header("Location: dashboard.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_actu'])) {
        $titre = trim($_POST['titre']);
        $contenu = trim($_POST['contenu']);
        
        if (!empty($titre) && !empty($contenu)) {
            $ins_stmt = $pdo->prepare("INSERT INTO actualites (titre, contenu) VALUES (?, ?)");
            $ins_stmt->execute([$titre, $contenu]);
            header("Location: dashboard.php");
            exit();
        }
    }

    if (isset($_GET['delete_actu'])) {
        $actu_id = $_GET['delete_actu'];
        $del_actu = $pdo->prepare("DELETE FROM actualites WHERE id = ?");
        $del_actu->execute([$actu_id]);
        header("Location: dashboard.php");
        exit();
    }

    $stats_stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'etudiant'");
    $total_etudiants = $stats_stmt->fetch()['total'] ?? 0;

    $users_stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    $all_users = $users_stmt->fetchAll();

    $actu_stmt = $pdo->query("SELECT * FROM actualites ORDER BY id DESC");
    $all_actus = $actu_stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Administration - UGTE IHEC Carthage</title>
    <style>
        :root {
            --bg-gradient: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            --text-color: #fff;
            --navbar-bg: rgba(255, 255, 255, 0.02);
            --navbar-border: rgba(255, 255, 255, 0.06);
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-muted: #94a3b8;
            --text-sub: #e2e8f0;
            --th-bg: rgba(15, 23, 42, 0.4);
            --input-bg: rgba(15, 23, 42, 0.6);
        }

        body.light-mode {
            --bg-gradient: linear-gradient(135deg, #f8fafc, #e2e8f0);
            --text-color: #0f172a;
            --navbar-bg: rgba(255, 255, 255, 0.7);
            --navbar-border: rgba(0, 0, 0, 0.08);
            --card-bg: rgba(255, 255, 255, 0.8);
            --card-border: rgba(0, 0, 0, 0.08);
            --text-muted: #64748b;
            --text-sub: #334155;
            --th-bg: rgba(241, 245, 249, 0.9);
            --input-bg: rgba(255, 255, 255, 0.9);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; transition: background 0.3s, color 0.3s; }
        body {
            background: var(--bg-gradient);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 40px;
        }
        navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: var(--navbar-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--navbar-border);
        }
        navbar h1 { font-size: 20px; color: #818cf8; font-weight: 700; letter-spacing: -0.5px; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover { color: #818cf8; }
        .btn-logout { background-color: rgba(239, 68, 68, 0.15) !important; border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5 !important; padding: 8px 16px; border-radius: 8px; }

        .theme-toggle {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-color);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; width: 100%; }
        
        .welcome-banner {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-banner h2 { color: #818cf8; font-size: 22px; font-weight: 700; margin-bottom: 5px; }
        .welcome-banner p { color: var(--text-muted); font-size: 14px; }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            padding: 15px 25px;
            border-radius: 14px;
            text-align: right;
        }
        .stat-card span { display: block; font-size: 24px; font-weight: bold; color: #818cf8; }
        .stat-card label { font-size: 12px; color: var(--text-muted); }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
            margin-bottom: 30px;
        }
        h3 { color: #818cf8; margin-bottom: 20px; font-size: 18px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid var(--card-border); font-size: 14px; }
        th { color: var(--text-muted); font-weight: 600; font-size: 13px; background: var(--th-bg); }
        td { color: var(--text-sub); }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-admin { background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3); }
        .badge-etudiant { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }

        .btn-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            text-decoration: none;
        }

        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 6px; font-weight: 500; }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px 14px;
            background: var(--input-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            color: var(--text-color);
            font-size: 14px;
        }
        textarea { resize: vertical; height: 90px; }
        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <navbar>
        <h1>UGTE IHEC - Administration</h1>
        <div class="nav-links">
            <a href="home.php">Accueil Site</a>
            <a href="dashboard.php" style="color: #818cf8; font-weight: 600;">Dashboard Admin</a>
            
            <button class="theme-toggle" onclick="toggleTheme()">
                <span id="theme-icon">🌙</span> <span id="theme-text">Mode Clair</span>
            </button>

            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </navbar>

    <div class="container">
        
        <div class="welcome-banner">
            <div>
                <h2>Espace Administrateur</h2>
                <p>Bienvenue, vous avez le contrôle total sur la plateforme.</p>
            </div>
            <div class="stat-card">
                <span><?php echo $total_etudiants; ?></span>
                <label>Étudiants Inscrits</label>
            </div>
        </div>

        <div class="card">
            <h3>Publier une Nouvelle Actualité</h3>
            <form action="dashboard.php" method="POST">
                <div class="form-group">
                    <label>Titre de l'annonce</label>
                    <input type="text" name="titre" required placeholder="Ex: Inscriptions universitaires 2026-2027">
                </div>
                <div class="form-group">
                    <label>Contenu de l'annonce</label>
                    <textarea name="contenu" required placeholder="Détails de l'actualité..."></textarea>
                </div>
                <button type="submit" name="ajouter_actu" class="btn-submit">Publier l'actualité</button>
            </form>
        </div>

        <div class="card">
            <h3>Actualités Publiées</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($all_actus)): ?>
                        <?php foreach($all_actus as $actu): ?>
                        <tr>
                            <td>#<?php echo $actu['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($actu['titre']); ?></strong></td>
                            <td style="color: var(--text-muted); font-size: 13px;"><?php echo $actu['date_publication']; ?></td>
                            <td>
                                <a href="dashboard.php?delete_actu=<?php echo $actu['id']; ?>" class="btn-delete" onclick="return confirm('Voulez-vous vraiment supprimer cette actualité ?');">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Aucune actualité publiée pour le moment.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Liste des Utilisateurs Inscrits</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom et Prénom</th>
                        <th>Email</th>
                        <th>Niveau</th>
                        <th>Filière</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($all_users)): ?>
                        <?php foreach($all_users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['nom'] ?? ''); ?></strong></td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($u['niveau'] ?? ''); ?></td>
                            <td style="font-size: 12px;"><?php echo htmlspecialchars($u['filiere'] ?? ''); ?></td>
                            <td>
                                <?php if(isset($u['role']) && $u['role'] == 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-etudiant">Étudiant</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="dashboard.php?delete_id=<?php echo $u['id']; ?>" class="btn-delete" onclick="return confirm('Es-tu sûr de vouloir supprimer cet utilisateur ?');">Supprimer</a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: var(--text-muted);">(Vous)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">Aucun utilisateur trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        const body = document.body;
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.getElementById('theme-text');

        if (localStorage.getItem('theme') === 'light') {
            body.classList.add('light-mode');
            themeIcon.textContent = '☀️';
            themeText.textContent = 'Mode Sombre';
        }

        function toggleTheme() {
            body.classList.toggle('light-mode');
            if (body.classList.contains('light-mode')) {
                localStorage.setItem('theme', 'light');
                themeIcon.textContent = '☀️';
                themeText.textContent = 'Mode Sombre';
            } else {
                localStorage.setItem('theme', 'dark');
                themeIcon.textContent = '🌙';
                themeText.textContent = 'Mode Clair';
            }
        }
    </script>
</body>
</html>