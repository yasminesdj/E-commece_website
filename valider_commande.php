<?php
session_start();
include("connexion.php");

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

// Vérifie que le panier contient des articles
if (empty($_SESSION["panier"])) {
    echo "<h2>Votre panier est vide.</h2>";
    echo "<a href='index.php'>Retour à la boutique</a>";
    exit();
}

// Insérer la commande
$id_user = $_SESSION["id"];
$mysqli->query("INSERT INTO commandes (id_utilisateur) VALUES ($id_user)");
$id_commande = $mysqli->insert_id;

// Insérer les détails de la commande
foreach ($_SESSION["panier"] as $id_item => $val) {
    $stmt = $mysqli->prepare("INSERT INTO details_commande (id_commande, id_item) VALUES (?, ?)");
    $stmt->bind_param("ii", $id_commande, $id_item);
    $stmt->execute();
}

// Vider le panier
$_SESSION["panier"] = [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Commande Validée - Shopora</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .confirmation-box {
      background: white;
      padding: 40px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
      max-width: 500px;
    }
    .confirmation-box h2 {
      color: #27ae60;
      font-size: 24px;
      margin-bottom: 10px;
    }
    .confirmation-box p {
      font-size: 16px;
      color: #555;
    }
    .confirmation-box a {
      margin-top: 30px;
      display: inline-block;
      padding: 12px 24px;
      background: #7b2ff7;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: background 0.3s ease;
    }
    .confirmation-box a:hover {
      background: #692be3;
    }
    .icon {
      font-size: 48px;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

  <div class="confirmation-box">
    <div class="icon">🎉</div>
    <h2>Commande validée avec succès !</h2>
    <p>Merci pour votre achat chez <strong>Shopora</strong>.<br>Nous espérons vous revoir bientôt 😊</p>
    <a href="index.php">← Retour à la boutique</a>
  </div>

</body>
</html>
