<?php
session_start();
include("connexion.php");

// Vérification utilisateur connecté
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

// Vérification panier
if (empty($_SESSION["panier"])) {
    $_SESSION['error'] = "Votre panier est vide";
    header("Location: panier.php");
    exit();
}

$id_utilisateur = $_SESSION["id"];

try {
    // Commencer une transaction
    $mysqli->begin_transaction();

    // Étape 1 : Vider les anciennes données du panier
    $stmt = $mysqli->prepare("DELETE FROM panier WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();

    // Étape 2 : Vérifier les stocks avant insertion
    foreach ($_SESSION["panier"] as $id_item => $quantite) {
        // Vérifier que le stock est suffisant
        $check = $mysqli->prepare("SELECT stock FROM items WHERE id = ?");
        $check->bind_param("i", $id_item);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if ($result['stock'] < $quantite) {
            throw new Exception("Stock insuffisant pour l'article ID $id_item");
        }

        // Insérer dans le panier
        $stmt = $mysqli->prepare("INSERT INTO panier (id_utilisateur, id_item, quantite) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_utilisateur, $id_item, $quantite);
        $stmt->execute();
    }

    // Étape 3 : Appeler la procédure stockée
    $stmt = $mysqli->prepare("CALL proc_finaliser_commande(?)");
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    $stmt->close();

    // Valider la transaction
    $mysqli->commit();

    // Étape 4 : Vider le panier en session
    $_SESSION["panier"] = [];
    $_SESSION['success'] = "Commande validée avec succès !";

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $mysqli->rollback();
    $_SESSION['error'] = "Erreur lors de la commande : " . $e->getMessage();
    header("Location: panier.php");
    exit();
}
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
