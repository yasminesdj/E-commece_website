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

    // Vérification des stocks
    foreach ($_SESSION["panier"] as $id_item => $quantite) {
        $stmt = $mysqli->prepare("SELECT stock FROM items WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $id_item);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($quantite > $result['stock']) {
            throw new Exception("Stock insuffisant pour l'article ID $id_item (Demandé: $quantite, Disponible: ".$result['stock'].")");
        }
    }

    // Vider le panier existant
    $stmt = $mysqli->prepare("DELETE FROM panier WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();

    // Insérer les articles dans le panier
    foreach ($_SESSION["panier"] as $id_item => $quantite) {
        $stmt = $mysqli->prepare("INSERT INTO panier (id_utilisateur, id_item, quantite) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_utilisateur, $id_item, $quantite);
        $stmt->execute();
    }

    // Appeler la procédure stockée
    $stmt = $mysqli->prepare("CALL proc_finaliser_commande(?)");
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    $stmt->close();

    // Valider la transaction
    $mysqli->commit();

    // Vider le panier en session
    $_SESSION["panier"] = [];
    $_SESSION['success'] = "Commande validée avec succès !";

} catch (mysqli_sql_exception $e) {
    $mysqli->rollback();
    
    // Gestion spécifique des erreurs de trigger
    if (strpos($e->getMessage(), 'Stock insuffisant') !== false) {
        $_SESSION['error'] = "❌ Erreur : ".$e->getMessage();
    } else {
        $_SESSION['error'] = "❌ Erreur technique lors de la validation de la commande";
    }
    header("Location: panier.php");
    exit();
} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['error'] = "❌ Erreur : ".$e->getMessage();
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
      color: #28a745;
      font-size: 24px;
      margin-bottom: 10px;
    }
    .confirmation-box p {
      font-size: 16px;
      color: #555;
      margin-bottom: 20px;
    }
    .confirmation-box a {
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
    .icon-success {
      font-size: 48px;
      color: #28a745;
      margin-bottom: 15px;
    }
    .order-details {
      margin: 20px 0;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      text-align: left;
    }
  </style>
</head>
<body>
  <div class="confirmation-box">
    <div class="icon-success">✓</div>
    <h2>Commande validée avec succès !</h2>
    <p>Merci pour votre achat chez <strong>Shopora</strong>.</p>
    
    <div class="order-details">
      <p>Nous espérons vous revoir bientôt 😊</p>
    </div>
    
    <a href="index.php">← Retour à la boutique</a>
  </div>
</body>
</html>