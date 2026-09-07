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

// Traitement envoi d'un nouveau ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_ticket'])) {
    $sujet = trim($_POST['sujet']);
    $message = trim($_POST['message']);
    
    if (!empty($sujet) && !empty($message)) {
        $ins = $pdo->prepare("INSERT INTO support_tickets (user_id, sujet, message, date_creation) VALUES (?, ?, ?, NOW())");
        $ins->execute([$user['id'], $sujet, $message]);
        header("Location: support.php?success=1");
        exit();
    }
}

// Récupération des tickets de l'utilisateur (ou tous si admin)
if ($user['role'] === 'admin') {
    // Si admin, il peut voir tous les tickets
    $tickets_stmt = $pdo->query("SELECT t.*, u.nom as user_nom, u.email as user_email FROM support_tickets t JOIN users u ON t.user_id = u.id ORDER BY t.id DESC");
} else {
    // Étudiant voit uniquement ses tickets
    $tickets_stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY id DESC");
    $tickets_stmt->execute([$user['id']]);
}
$tickets = $tickets_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Support & Réclamations - UGTE IHEC Carthage</title>
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
        .nav-links { display: flex; align-items: center; gap: 15px; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; }
        .nav-links a:hover { color: var(--ihec-blue); }
        
        .dropdown { position: relative; display: inline-block; }
        .dropbtn { background: var(--card-bg); border: 1px solid var(--card-border); color: var(--text-color); padding: 8px 14px; border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: var(--dropdown-bg); min-width: 200px; box-shadow: 0px 15px 35px rgba(0,0,0,0.2); border: 1px solid var(--card-border); border-radius: 14px; z-index: 1001; margin-top: 8px; overflow: hidden; }
        .dropdown-content a { color: var(--text-color) !important; padding: 12px 16px; display: block; font-size: 14px; border-bottom: 1px solid var(--card-border); }
        .dropdown-content a:hover { background-color: rgba(59, 130, 246, 0.1); color: var(--ihec-blue) !important; }
        .dropdown.active .dropdown-content { display: block; }

        .btn-admin-special { background: linear-gradient(135deg, #ef4444, #dc2626); color: white !important; border: 1px solid rgba(239, 68, 68, 0.4); padding: 8px 16px; border-radius: 10px; font-size: 14px; font-weight: 600 !important; text-decoration: none; }
        .btn-logout { background-color: rgba(239, 68, 68, 0.15) !important; border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5 !important; padding: 8px 16px; border-radius: 8px; }
        .theme-toggle { background: var(--card-bg); border: 1px solid var(--card-border); color: var(--text-color); padding: 8px 12px; border-radius: 10px; cursor: pointer; font-weight: 600; }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; }
        .section-title { color: var(--text-color); font-size: 24px; margin-bottom: 25px; font-weight: 700; }

        .form-card, .ticket-card { background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid var(--card-border); padding: 25px; border-radius: 18px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 6px; font-weight: 500; }
        input[type="text"], textarea { width: 100%; padding: 10px 14px; background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; color: var(--text-color); font-size: 14px; }
        textarea { resize: vertical; height: 100px; }
        .btn-submit { background: var(--btn-gradient); color: white; padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }

        .ticket-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .ticket-title { font-size: 18px; color: var(--text-color); font-weight: 600; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-attente { background: rgba(234, 179, 8, 0.15); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.3); }
        .badge-resolu { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        
        .ticket-msg { color: var(--text-sub); font-size: 14px; margin-bottom: 15px; white-space: pre-line; }
        .ticket-reply { background: rgba(59, 130, 246, 0.1); border-left: 3px solid var(--ihec-blue); padding: 12px 15px; border-radius: 0 10px 10px 0; margin-top: 10px; font-size: 13px; color: var(--text-color); }
        
        .admin-reply-form { margin-top: 15px; border-top: 1px solid var(--card-border); padding-top: 15px; }
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
                    <a href="support.php">🛠️ Support & Réclamations</a>
                </div>
            </div>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="dashboard.php" class="btn-admin-special">⚡ Espace Admin</a>
            <?php endif; ?>
            <button class="theme-toggle" onclick="toggleTheme()"><span id="theme-icon">🌙</span></button>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </navbar>

    <div class="container">
        <h2 class="section-title">🛠️ Support et Réclamations</h2>

        <?php if ($user['role'] !== 'admin'): ?>
            <!-- Formulaire d'envoi pour l'étudiant -->
            <div class="form-card">
                <h4 style="color: var(--text-color); margin-bottom: 15px;">➕ Ouvrir un nouveau ticket de support</h4>
                <form action="support.php" method="POST">
                    <div class="form-group">
                        <label>Sujet de la réclamation / question</label>
                        <input type="text" name="sujet" required placeholder="Ex: Problème d'accès à un cours...">
                    </div>
                    <div class="form-group">
                        <label>Message détaillé</label>
                        <textarea name="message" required placeholder="Expliquez votre problème en détail..."></textarea>
                    </div>
                    <button type="submit" name="envoyer_ticket" class="btn-submit">Envoyer le Ticket</button>
                </form>
            </div>
        <?php endif; ?>

        <h3 style="color: var(--text-color); margin-bottom: 15px; font-size: 18px;">
            <?php echo ($user['role'] === 'admin') ? "📋 Tous les Tickets des Étudiants (Espace Admin)" : "📌 Vos Tickets Envoyés"; ?>
        </h3>

        <div class="tickets-list">
            <?php if (!empty($tickets)): ?>
                <?php foreach ($tickets as $t): ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <div>
                                <span class="ticket-title"><?php echo htmlspecialchars($t['sujet']); ?></span>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <div style="font-size: 12px; color: var(--ihec-blue); margin-top: 2px;">Par : <?php echo htmlspecialchars($t['user_nom']); ?> (<?php echo htmlspecialchars($t['user_email']); ?>)</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($t['statut'] === 'Résolu'): ?>
                                    <span class="badge badge-resolu">Résolu</span>
                                <?php else: ?>
                                    <span class="badge badge-attente">En attente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-bottom: 8px;">Date : <?php echo $t['date_creation']; ?></span>
                        <div class="ticket-msg"><?php echo htmlspecialchars($t['message']); ?></div>

                        <?php if (!empty($t['reponse'])): ?>
                            <div class="ticket-reply">
                                <strong>Réponse de l'Administration :</strong><br>
                                <?php echo htmlspecialchars($t['reponse']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Si l'admin veut répondre -->
                        <?php if ($user['role'] === 'admin'): ?>
                            <div class="admin-reply-form">
                                <form action="repondre_ticket.php" method="POST">
                                    <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                                    <div class="form-group" style="margin-bottom: 8px;">
                                        <label style="font-size: 12px;">Répondre ou modifier la réponse :</label>
                                        <textarea name="reponse" style="height: 60px;" required><?php echo htmlspecialchars($t['reponse'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn-submit" style="padding: 6px 14px; font-size: 13px;">Envoyer la réponse</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="ticket-card" style="text-align: center; color: var(--text-muted);">
                    Aucun ticket trouvé pour le moment.
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