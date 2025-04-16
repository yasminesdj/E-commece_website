<?php 
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mysqli = new mysqli("localhost", "root", "", "ecommerce_db");
    if ($mysqli->connect_errno) {
        die("Erreur de connexion : " . $mysqli->connect_error);
    }

    $email = $_POST["email"];
    $mdp = $_POST["password"];

    // Stocker l'email dans un cookie pendant 7 jours
    setcookie("email", $email, time() + (86400 * 7), "/");

    $stmt = $mysqli->prepare("SELECT * FROM utilisateurs WHERE email=? AND mdp=?");
    $stmt->bind_param("ss", $email, $mdp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $_SESSION["id"] = $user["id"];
        $_SESSION["nom"] = $user["nom"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] == "admin") {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion - Shopora</title>
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
      <h1>Welcome to sophora — Explore More. Pay Less!</h1>
      <img src="images/login-welcome.jpg" alt="Image de bienvenue">
    </div>
    <div class="right">
      <h2>Login to your account</h2>
      <form method="POST">
        <input type="email" name="email" placeholder="Email" value="<?= $_COOKIE['email'] ?? '' ?>" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login now</button>
      </form>
      <?php if ($error) echo "<div class='error'>$error</div>"; ?>
      <a href="register.php">Don't have an account? Sign up</a>
    </div>
  </div>
</body>
</html>
