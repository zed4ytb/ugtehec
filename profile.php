<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$success = "";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom']);
    $niveau = $_POST['niveau'];
    $filiere = $_POST['filiere'];
    $current_password = $_POST['current_password'];
    $photo_name = $current_user['photo'];

    if (empty($current_password) || !password_verify($current_password, $current_user['password'])) {
        $message = "Mot de passe actuel incorrect ! Modifications refusées.";
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $filetmp = $_FILES['photo']['tmp_name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
                $upload_dir = "uploads/";
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                if (move_uploaded_file($filetmp, $upload_dir . $new_filename)) {
                    $photo_name = $new_filename;
                } else {
                    $message = "Erreur lors du téléchargement de l'image.";
                }
            } else {
                $message = "Format d'image non autorisé.";
            }
        }

        if (empty($message)) {
            $sql = "UPDATE users SET nom = ?, niveau = ?, filiere = ?, photo = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nom, $niveau, $filiere, $photo_name, $user_id])) {
                $_SESSION['nom'] = $nom;
                $success = "Profil mis à jour avec succès !";
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_user = $stmt->fetch();
            } else {
                $message = "Erreur lors de la mise à jour.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - UGTE IHEC Carthage</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; }
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            color: #fff;
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
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        navbar h1 { font-size: 20px; color: #a5b4fc; font-weight: 700; letter-spacing: -0.5px; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover { color: #818cf8; }
        .btn-logout { background-color: rgba(239, 68, 68, 0.15) !important; border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5 !important; padding: 8px 16px; border-radius: 8px; transition: all 0.2s; }
        .btn-logout:hover { background-color: rgba(239, 68, 68, 0.25) !important; }
        
        .container {
            max-width: 480px;
            margin: 40px auto;
            padding: 0 20px;
            width: 100%;
        }
        .card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);
            text-align: center;
        }
        h2 { color: #a5b4fc; margin-bottom: 25px; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        
        .avatar-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 25px auto;
        }
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #818cf8;
            box-shadow: 0 10px 20px -5px rgba(129, 140, 248, 0.4);
        }
        
        .input-group { margin-bottom: 18px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8; font-size: 13px; }
        input[type="text"], input[type="password"], input[type="file"], select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        select option { background: #0f172a; color: white; padding: 10px; }
        input[type="file"] { color: #64748b; cursor: pointer; padding: 10px; }
        input:focus, select:focus { border-color: #818cf8; box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.15); }
        
        .security-section {
            background: rgba(239, 68, 68, 0.04);
            border: 1px dashed rgba(239, 68, 68, 0.25);
            padding: 16px;
            border-radius: 14px;
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        button[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.5);
            transition: all 0.2s ease;
            margin-top: 5px;
        }
        button[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.6); }
        button[type="submit"]:active { transform: translateY(0); }
        
        .alert { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 12px; border-radius: 10px; font-size: 13px; text-align: center; margin-bottom: 20px; }
        .success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #86efac; padding: 12px; border-radius: 10px; font-size: 13px; text-align: center; margin-bottom: 20px; }
        .email-display { color: #475569; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>

    <navbar>
        <h1>UGTE IHEC</h1>
        <div class="nav-links">
            <a href="home.php">Accueil</a>
            <a href="profile.php" style="color: #818cf8; font-weight: 600;">Mon Profil</a>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </navbar>

    <div class="container">
        <div class="card">
            <h2>Mon Profil</h2>
            
            <?php if(!empty($message)): ?><div class="alert"><?php echo $message; ?></div><?php endif; ?>
            <?php if(!empty($success)): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>

            <div class="avatar-container">
                <?php 
                    $photo_path = (!empty($current_user['photo']) && file_exists("uploads/" . $current_user['photo'])) 
                        ? "uploads/" . $current_user['photo'] 
                        : "https://via.placeholder.com/100/6366f1/ffffff?text=" . strtoupper(substr($current_user['nom'], 0, 1));
                ?>
                <img src="<?php echo $photo_path; ?>" alt="Avatar" class="avatar">
            </div>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                
                <div class="input-group">
                    <label>Nom et Prénom</label>
                    <input type="text" name="nom" required value="<?php echo htmlspecialchars($current_user['nom']); ?>">
                </div>

                <div class="input-group">
                    <label>Email Universitaire (Fixe)</label>
                    <input type="text" disabled value="<?php echo htmlspecialchars($current_user['email']); ?>" style="background: rgba(255,255,255,0.01); color: #475569; cursor: not-allowed;">
                    <div class="email-display">L'email ne peut pas être modifié.</div>
                </div>

                <div class="input-group">
                    <label>Niveau Universitaire</label>
                    <select name="niveau" id="niveau" required onchange="updateFiliereOptions()">
                        <option value="1ère Année Licence" <?php if($current_user['niveau'] == '1ère Année Licence') echo 'selected'; ?>>1ère Année Licence</option>
                        <option value="2ème Année Licence" <?php if($current_user['niveau'] == '2ème Année Licence') echo 'selected'; ?>>2ème Année Licence</option>
                        <option value="3ème Année Licence" <?php if($current_user['niveau'] == '3ème Année Licence') echo 'selected'; ?>>3ème Année Licence</option>
                        <option value="1ère Année Mastère" <?php if($current_user['niveau'] == '1ère Année Mastère') echo 'selected'; ?>>1ère Année Mastère (M1)</option>
                        <option value="2ème Année Mastère" <?php if($current_user['niveau'] == '2ème Année Mastère') echo 'selected'; ?>>2ème Année Mastère (M2)</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Filière / Spécialité</label>
                    <select name="filiere" id="filiere" required>
                        <!-- Les filières s'affichent automatiquement via JavaScript selon le niveau actuel -->
                    </select>
                </div>

                <div class="input-group">
                    <label>Changer la photo de profil</label>
                    <input type="file" name="photo" accept="image/*">
                </div>

                <div class="security-section">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="color: #fca5a5;">Confirmer par votre Mot de Passe (Obligatoire)</label>
                        <input type="password" name="current_password" required placeholder="Entrez votre mot de passe actuel">
                    </div>
                </div>

                <button type="submit">Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <script>
        const savedFiliere = "<?php echo htmlspecialchars($current_user['filiere']); ?>";

        function updateFiliereOptions(selectedFiliere = savedFiliere) {
            const niveau = document.getElementById('niveau').value;
            const filiereSelect = document.getElementById('filiere');
            
            filiereSelect.innerHTML = '';
            
            let options = [];

            if (niveau === '1ère Année Licence') {
                options = [
                    "1ère Année - Sciences de Gestion (Tronc Commun)",
                    "1ère Année - Informatique de Gestion"
                ];
            } else if (niveau === '2ème Année Licence') {
                options = [
                    "2ème Année - Sciences de Gestion (Tronc Commun)",
                    "2ème Année - Comptabilité",
                    "2ème Année - Informatique de Gestion"
                ];
            } else if (niveau === '3ème Année Licence') {
                options = [
                    "3ème Année - Comptabilité",
                    "3ème Année - Finance",
                    "3ème Année - Marketing",
                    "3ème Année - Management",
                    "3ème Année - Ressources Humaines (RH)",
                    "3ème Année - Informatique de Gestion"
                ];
            } else if (niveau === '1ère Année Mastère' || niveau === '2ème Année Mastère') {
                options = [
                    "Master Pro - Analyste Financier",
                    "Master Pro - Comptabilité",
                    "Master Pro - Marketing Moderne et Veille Stratégique",
                    "Master Pro - Management for Hospitality and Tourism",
                    "Master Pro - Entrepreneuriat",
                    "Master Pro - Big Data & E-Commerce",
                    "Master Recherche - Management, Stratégie et Conseil",
                    "Master Recherche - Marketing",
                    "Master Recherche - Finance",
                    "Master Recherche - Ingénierie Économique et Financière",
                    "Master Recherche - Comptabilité"
                ];
            }

            options.forEach(opt => {
                const el = document.createElement('option');
                el.value = opt;
                el.textContent = opt;
                if (opt === selectedFiliere) {
                    el.selected = true;
                }
                filiereSelect.appendChild(el);
            });
        }

        // Lancer au chargement de la page pour afficher directement la filière enregistrée de l'utilisateur
        window.onload = function() {
            updateFiliereOptions();
        };
    </script>
</body>
</html>