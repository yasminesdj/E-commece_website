<?php 
session_start();
include("connexion.php");

if (!isset($_SESSION["panier"])) {
    $_SESSION["panier"] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['quantite'] as $id => $quantite) {
        $stmt = $mysqli->prepare("SELECT stock FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($quantite > $result['stock']) {
            $_SESSION['error'] = "❌ La quantité demandée pour l'article ID $id dépasse le stock disponible.";
            header("Location: panier.php");
            exit();
        } else {
            $_SESSION["panier"]["$id"] = intval($quantite);
        }
    }
}

if (isset($_GET["remove"])) {
    $id = $_GET["remove"];
    unset($_SESSION["panier"]["$id"]);
    header("Location: panier.php");
    exit();
}

$ids = array_keys($_SESSION["panier"]);
$articles = [];
$total = 0;

if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $mysqli->prepare("SELECT * FROM items WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['quantite'] = $_SESSION["panier"][$row['id']];
        $articles[] = $row;
        $total += $row["prix"] * $row['quantite'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mon Panier - Shopora</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #f4f6f9; padding: 30px; }
    .container { max-width: 900px; margin: auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    h2 { text-align: center; color: #7b2ff7; margin-bottom: 30px; }
    .item {
      display: flex;
      align-items: center;
      background: #fafafa;
      margin-bottom: 20px;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .item img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 8px;
      margin-right: 20px;
    }
    .item .info { flex: 1; }
    .item .info h3 { margin: 0; color: #333; font-size: 18px; }
    .item .info p { font-size: 14px; color: #666; margin: 8px 0; }
    .item .info .price { color: #27ae60; font-weight: bold; font-size: 15px; }
    select {
      padding: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    .remove-btn {
      background: #eee;
      color: #333;
      padding: 8px 14px;
      text-decoration: none;
      border-radius: 6px;
      font-size: 14px;
      border: 1px solid #ccc;
      transition: 0.2s;
      margin-left: 10px;
    }
    .remove-btn:hover {
      background: #f8d7da;
      color: #dc3545;
      border-color: #dc3545;
    }
    .total {
      text-align: right;
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin-top: 20px;
    }
    .validate-btn {
      display: block;
      width: fit-content;
      margin: 30px auto 0;
      padding: 12px 24px;
      background: #7b2ff7;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }
    .validate-btn:hover {
      background: #692be3;
    }
    .back {
      display: block;
      margin: 40px auto 0;
      text-align: center;
      text-decoration: none;
      color: #7b2ff7;
      font-weight: 600;
    }
    .error-msg {
      text-align: center;
      color: red;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
  <h2>
  <img src="images/Buy.png" alt="Panier" style="width: 36px; height: 36px; vertical-align: middle; margin-right: 8px;">
  Mon panier
</h2>

  <?php if (isset($_SESSION['error'])) { echo "<div class='error-msg'>" . $_SESSION['error'] . "</div>"; unset($_SESSION['error']); } ?>

  <?php if (empty($articles)) : ?>
      <p style="text-align:center; color: #555;">Votre panier est vide.</p>
  <?php else: ?>
    <form method="POST">
      <?php foreach ($articles as $a): ?>
        <div class="item">
          <img src="images/<?= $a['image'] ?>" alt="<?= $a['nom'] ?>">
          <div class="info">
            <h3><?= $a['nom'] ?></h3>
            <p><?= $a['description'] ?></p>
            <div class="price"><?= $a['prix'] ?> DA</div>
          </div>
          <select name="quantite[<?= $a['id'] ?>]">
            <?php for ($i = 1; $i <= $a['stock']; $i++): ?>
              <option value="<?= $i ?>" <?= ($i == $a['quantite']) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
          <a class="remove-btn" href="panier.php?remove=<?= $a['id'] ?>">Retirer</a>
        </div>
      <?php endforeach; ?>

      <div class="total">
        Total : <?= number_format($total, 2) ?> DA
      </div>

      <button type="submit" class="validate-btn"> Mettre à jour les quantités</button>
    </form>

    <form method="POST" action="valider_commande.php">
      <button type="submit" class="validate-btn">✅ Valider la commande</button>
    </form>
  <?php endif; ?>

  <a class="back" href="index.php">← Retour à la boutique</a>
  </div>
</body>
</html>
