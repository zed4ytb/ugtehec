<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Vérifier si le compte existe par email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Le compte n'existe pas du tout
        $error = "Aucun compte n'existe avec cet email !";
    } else {
        // 2. Si le compte existe, vérifier le mot de passe
        if (password_verify($password, $user['password'])) {
            // Mot de passe correct, ouverture de session et redirection
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: home.php");
            exit();
        } else {
            // Le compte existe mais le mot de passe est incorrect
            $error = "Mot de passe incorrect, veuillez vérifier votre mot de passe !";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - UGTE IHEC Carthage</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; }
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);
            width: 100%;
            max-width: 420px;
        }
        h2 { color: #a5b4fc; margin-bottom: 8px; font-size: 24px; font-weight: 700; text-align: center; }
        .subtitle { color: #94a3b8; font-size: 14px; text-align: center; margin-bottom: 30px; }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; color: #cbd5e1; margin-bottom: 8px; font-weight: 500; }
        input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            transition: all 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
            transition: all 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
        .footer-text { text-align: center; margin-top: 25px; font-size: 13px; color: #94a3b8; }
        .footer-text a { color: #818cf8; text-decoration: none; font-weight: 600; }
        .footer-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>Connexion</h2>
        <p class="subtitle">UGTE IHEC Carthage</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Email institutionnel</label>
                <input type="email" name="email" required placeholder="nom@ihec.ucar.tn">
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-submit">Se connecter</button>
        </form>

        <div class="footer-text">
            Vous n'avez pas de compte ? <a href="signup.php">Inscrivez-vous ici</a>
        </div>
    </div>

</body>
</html>