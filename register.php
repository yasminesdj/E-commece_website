<?php
session_start();
$error = "";
$success = "";

// Sécurité : Régénération de l'ID de session
session_regenerate_id(true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mysqli = new mysqli("localhost", "root", "", "ecommerce_db");
    if ($mysqli->connect_errno) {
        die("Erreur de connexion : " . $mysqli->connect_error);
    }

    $nom = trim($_POST["nom"]);
    $email = trim($_POST["email"]);
    $mdp = $_POST["password"];
    $role = $_POST["role"] ?? 'client'; // Récupération du rôle avec valeur par défaut

    // Validation basique de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } elseif (empty($nom)) {
        $error = "Le nom est requis.";
    } else {
        // Vérifie si l'email existe déjà
        $stmt = $mysqli->prepare("SELECT * FROM utilisateurs WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Supprime le cookie email existant pour éviter les conflits
            setcookie("email", "", time() - 3600, "/", "", true, true);
            $error = "Cet email est déjà utilisé.";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO utilisateurs (nom, email, mdp, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nom, $email, $mdp, $role);
            
            if ($stmt->execute()) {
                // Connexion automatique après inscription
                $_SESSION["id"] = $mysqli->insert_id;
                $_SESSION["nom"] = $nom;
                $_SESSION["role"] = $role;
                $_SESSION["last_login"] = time();

                // Cookie sécurisé pour l'email (7 jours)
                setcookie("email", $email, [
                    'expires' => time() + (86400 * 7),
                    'path' => '/',
                    'httponly' => true,
                    'secure' => true, // Activez si HTTPS
                    'samesite' => 'Strict'
                ]);

                // Cookie pour le nom (optionnel)
                setcookie("nom_utilisateur", $nom, [
                    'expires' => time() + (86400 * 7),
                    'path' => '/',
                    'httponly' => true,
                    'secure' => true,
                    'samesite' => 'Strict'
                ]);

                header("Location: index.php"); // Redirection immédiate
                exit();
            } else {
                $error = "Erreur lors de la création du compte.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscription - Shopora</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
    }
    body {
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      display: flex;
      width: 1090px;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      background: white;
    }
    .left {
      flex: 1;
      background: linear-gradient(to right, #8341f0, #1a093f);
      color: white;
      padding: 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .left h1 {
      font-size: 32px;
      line-height: 1.5;
      max-width: 350px;
    }
    .left img {
      margin-top: 40px;
      max-width: 100%;
    }
    .right {
      flex: 1;
      padding: 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .right h2 {
      font-size: 24px;
      margin-bottom: 25px;
      text-align: center;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    input {
      padding: 14px;
      border-radius: 50px;
      border: 1px solid #ccc;
      font-size: 15px;
    }
    input:focus {
      border-color: #7b2ff7;
      outline: none;
    }
    button {
      padding: 14px;
      border-radius: 50px;
      border: none;
      background: #7b2ff7;
      color: white;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #692be3;
    }
    .error {
      color: red;
      text-align: center;
    }
    .success {
      color: green;
      text-align: center;
    }
    a {
      text-align: center;
      color: #7b2ff7;
      font-weight: 500;
      text-decoration: none;
      margin-top: 20px;
      display: block;
    }
    a:hover {
      text-decoration: underline;
    }
    .role-selection {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin: 10px 0;
    }
    .role-option {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .role-option input[type="radio"] {
      width: 18px;
      height: 18px;
    }
    @media (max-width: 900px) {
      .container {
        flex-direction: column;
        width: 95%;
      }
      .left, .right {
        padding: 30px;
        text-align: center;
      }
      .left h1 {
        margin: auto;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <h1>Rejoignez Shopora — Explore More. Pay Less!</h1>
      <img src="images/login-welcome.jpg" alt="Image de bienvenue">
    </div>
    <div class="right">
      <h2>Créer un compte</h2>
      <form method="POST">
        <input type="text" name="nom" placeholder="Nom" required>
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_COOKIE['email'] ?? '') ?>" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        
        <div class="role-selection">
          <div class="role-option">
            <input type="radio" id="client" name="role" value="client" checked>
            <label for="client">Client</label>
          </div>
          <div class="role-option">
            <input type="radio" id="admin" name="role" value="admin">
            <label for="admin">Admin</label>
          </div>
        </div>
        
        <button type="submit">Créer un compte</button>
      </form>
      <?php 
        if ($error) echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; 
        if ($success) echo "<div class='success'>" . htmlspecialchars($success) . "</div>"; 
      ?>
      <a href="login.php">Vous avez déjà un compte ? Se connecter</a>
    </div>
  </div>
</body>
</html>