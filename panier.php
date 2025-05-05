<?php 
session_start();
include("connexion.php");

if (!isset($_SESSION["panier"])) {
    $_SESSION["panier"] = [];
}

$id_user = $_SESSION["id"] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantite'])) {
    foreach ($_POST['quantite'] as $id => $quantite) {
        $quantite = intval($quantite);
        if ($quantite < 1) $quantite = 1;

        $stmt = $mysqli->prepare("SELECT stock FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($quantite > $result['stock']) {
            $_SESSION['error'] = "❌ La quantité demandée pour l'article ID $id dépasse le stock disponible (Stock: ".$result['stock'].")";
            header("Location: panier.php");
            exit();
        } else {
            $_SESSION["panier"][$id] = $quantite;

            if ($id_user) {
                $stmt = $mysqli->prepare("UPDATE panier SET quantite = ? WHERE id_utilisateur = ? AND id_item = ?");
                $stmt->bind_param("iii", $quantite, $id_user, $id);
                $stmt->execute();
            }
        }
    }
    header("Location: panier.php");
    exit();
}

if (isset($_GET["remove"])) {
    $id = $_GET["remove"];
    unset($_SESSION["panier"]["$id"]);

    if ($id_user) {
        $stmt = $mysqli->prepare("DELETE FROM panier WHERE id_utilisateur = ? AND id_item = ?");
        $stmt->bind_param("ii", $id_user, $id);
        $stmt->execute();
    }

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
    .quantity-input {
      padding: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
      width: 60px;
      text-align: center;
    }
    .invalid-qty { border-color: #dc3545 !important; }
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
    .validate-btn:disabled {
      background: #cccccc;
      cursor: not-allowed;
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
      color: #dc3545;
      margin-bottom: 15px;
      padding: 10px;
      background-color: #f8d7da;
      border-radius: 6px;
    }
    .item-total {
      margin-left: 15px;
      font-weight: bold;
      color: #333;
      min-width: 100px;
      text-align: right;
    }
    .stock-info {
      font-size: 12px;
      color: #6c757d;
      margin-top: 3px;
    }
    /* Styles pour l'alerte */
    .alert-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      display: none;
    }
    .alert-box {
      background: white;
      padding: 25px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      text-align: center;
    }
    .alert-title {
      color:  #7b2ff7;
      font-size: 20px;
      margin-bottom: 15px;
      font-weight: 600;
    }
    .alert-message {
      margin-bottom: 20px;
      color: #333;
      white-space: pre-line;
    }
    .alert-close {
      background: #7b2ff7;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>
      <img src="images/Buy.png" alt="Panier" style="width: 36px; height: 36px; vertical-align: middle; margin-right: 8px;">
      Mon panier
    </h2>

    <?php if (isset($_SESSION['error'])): ?>
      <div class='error-msg'><?= $_SESSION['error'] ?></div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (empty($articles)): ?>
      <p style="text-align:center; color: #555;">Votre panier est vide.</p>
    <?php else: ?>
      <form id="cartForm" method="POST">
        <?php foreach ($articles as $a): ?>
          <div class="item">
            <img src="images/<?= $a['image'] ?>" alt="<?= $a['nom'] ?>">
            <div class="info">
              <h3><?= htmlspecialchars($a['nom']) ?></h3>
              <p><?= htmlspecialchars($a['description']) ?></p>
              <div class="price"><?= $a['prix'] ?> DA</div>
              <div class="stock-info">Stock disponible: <?= $a['stock'] ?></div>
            </div>
            <input type="number" 
                   name="quantite[<?= $a['id'] ?>]" 
                   min="1" 
                   max="<?= $a['stock'] ?>"
                   value="<?= $a['quantite'] ?>"
                   onchange="updateCart(this)"
                   class="quantity-input"
                   required>
            <div class="item-total">
              <?= $a['prix'] * $a['quantite'] ?> DA
            </div>
            <a class="remove-btn" href="panier.php?remove=<?= $a['id'] ?>">Retirer</a>
          </div>
        <?php endforeach; ?>

        <div class="total" id="grandTotal">
          Total : <?= $total ?> DA
        </div>
      </form>

      <form method="POST" action="valider_commande.php" onsubmit="return validateCart()">
        <button type="submit" class="validate-btn" id="validateBtn">Valider la commande</button>
      </form>
    <?php endif; ?>

    <a class="back" href="index.php">← Retour à la boutique</a>
  </div>

  <!-- Nouvelle alerte stylisée -->
  <div class="alert-overlay" id="stockAlert">
    <div class="alert-box">
    <div class="alert-title">
        <img src="images/Icon.png" alt="Attention"> Attention
    </div>
      <div class="alert-message" id="alertMessage"></div>
      <button class="alert-close" onclick="document.getElementById('stockAlert').style.display='none'">OK</button>
    </div>
  </div>

  <script>
    // Fonction pour afficher l'alerte
    function showAlert(message) {
      document.getElementById('alertMessage').textContent = message;
      document.getElementById('stockAlert').style.display = 'flex';
    }

    function updateCart(input) {
      const max = parseInt(input.max);
      const value = parseInt(input.value);
      const productName = input.closest('.item').querySelector('h3').textContent;
      
      if (value > max) {
        input.classList.add('invalid-qty');
        document.getElementById('validateBtn').disabled = true;
        showAlert(`"${productName}" :\nQuantité demandée (${value}) dépasse le stock disponible (${max})`);
      } else {
        input.classList.remove('invalid-qty');
        document.getElementById('validateBtn').disabled = false;
      }
      
      // Calcul du total
      const items = document.querySelectorAll('.item');
      let grandTotal = 0;
      
      items.forEach(item => {
        const input = item.querySelector('.quantity-input');
        const price = parseFloat(item.querySelector('.price').textContent);
        const quantity = parseInt(input.value);
        const itemTotal = price * quantity;
        
        item.querySelector('.item-total').textContent = itemTotal + ' DA';
        grandTotal += itemTotal;
      });
      
      document.getElementById('grandTotal').textContent = 'Total : ' + grandTotal + ' DA';
    }

    function validateCart() {
      const inputs = document.querySelectorAll('.quantity-input');
      let isValid = true;
      let errorMessage = 'Certains articles dépassent le stock disponible:\n\n';
      
      inputs.forEach(input => {
        const max = parseInt(input.max);
        const value = parseInt(input.value);
        const productName = input.closest('.item').querySelector('h3').textContent;
        
        if (value > max) {
          input.classList.add('invalid-qty');
          isValid = false;
          errorMessage += `• ${productName} : ${value} > stock (${max})\n`;
        }
      });
      
      if (!isValid) {
        showAlert(errorMessage);
        return false;
      }
      return true;
    }

    // Vérification initiale au chargement
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('.quantity-input');
      inputs.forEach(input => {
        updateCart(input);
      });
    });
  </script>
</body>
</html>