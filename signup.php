<?php
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $niveau = $_POST['niveau'];
    $filiere = $_POST['filiere'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $message = "Cet email est déjà utilisé !";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (nom, email, password, niveau, filiere, role) VALUES (?, ?, ?, ?, ?, 'etudiant')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nom, $email, $hashed_password, $niveau, $filiere])) {
            header("Location: login.php?success=1");
            exit();
        } else {
            $message = "Erreur lors de l'inscription.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - UGTE IHEC Carthage</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; }
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #fff;
            padding: 30px 0;
        }
        .card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            width: 450px;
        }
        h2 { text-align: center; color: #a5b4fc; margin-bottom: 25px; font-size: 26px; font-weight: 700; letter-spacing: -0.5px; }
        .input-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8; font-size: 13px; }
        input, select {
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
        input:focus, select:focus { border-color: #818cf8; box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.15); }
        input::placeholder { color: #475569; }
        
        button {
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
            margin-top: 10px;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.6); }
        button:active { transform: translateY(0); }
        .alert { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 12px; border-radius: 10px; font-size: 13px; text-align: center; margin-bottom: 20px; }
        .link { text-align: center; margin-top: 20px; font-size: 13px; color: #64748b; }
        .link a { color: #818cf8; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .link a:hover { color: #a5b4fc; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Créer un Compte</h2>
        
        <?php if(!empty($message)): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div class="input-group">
                <label>Nom et Prénom</label>
                <input type="text" name="nom" required placeholder="Ex: Mohamed Ali">
            </div>
            <div class="input-group">
                <label>Email Universitaire</label>
                <input type="email" name="email" required placeholder="name@ihec.ucar.tn">
            </div>
            <div class="input-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="input-group">
                <label>Niveau Universitaire</label>
                <select name="niveau" id="niveau" required onchange="updateFiliereOptions()">
                    <option value="" disabled selected>Sélectionnez votre niveau</option>
                    <option value="1ère Année Licence">1ère Année Licence</option>
                    <option value="2ème Année Licence">2ème Année Licence</option>
                    <option value="3ème Année Licence">3ème Année Licence</option>
                    <option value="1ère Année Mastère">1ère Année Mastère (M1)</option>
                    <option value="2ème Année Mastère">2ème Année Mastère (M2)</option>
                </select>
            </div>
            <div class="input-group">
                <label>Filière / Spécialité (IHEC Carthage)</label>
                <select name="filiere" id="filiere" required>
                    <option value="" disabled selected>D'abord, choisissez le niveau</option>
                </select>
            </div>
            <button type="submit">S'inscrire</button>
        </form>
        <div class="link">
            Déjà un compte ? <a href="login.php">Se connecter</a>
        </div>
    </div>

    <script>
        function updateFiliereOptions(selectedFiliere = "") {
            const niveau = document.getElementById('niveau').value;
            const filiereSelect = document.getElementById('filiere');
            
            filiereSelect.innerHTML = '<option value="" disabled selected>Sélectionnez votre filière</option>';
            
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
    </script>
</body>
</html>