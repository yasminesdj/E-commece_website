<?php
session_start();
include("connexion.php");

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $mysqli->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Insérer dans la table panier uniquement si connecté et non déjà présent
if (isset($_SESSION["id"])) {
  $check = $mysqli->prepare("SELECT * FROM panier WHERE id_utilisateur = ? AND id_item = ?");
  $check->bind_param("ii", $_SESSION["id"], $id);
  $check->execute();
  $res = $check->get_result();
  if ($res->num_rows === 0) {
      $insert = $mysqli->prepare("INSERT INTO panier (id_utilisateur, id_item, quantite) VALUES (?, ?, 1)");
      $insert->bind_param("ii", $_SESSION["id"], $id);
      $insert->execute();
  }
}

if ($result->num_rows == 0) {
    echo "<h2>Article non trouvé.</h2>";
    exit();
}

$item = $result->fetch_assoc();

if (!isset($_SESSION["panier"])) {
    $_SESSION["panier"] = [];
}

$ajoute = false;
if (isset($_GET["add"])) {
    $idToAdd = intval($_GET["add"]);
    $_SESSION["panier"]["$idToAdd"] = true;
    $ajoute = true;
    echo "<script>history.replaceState(null, '', 'detail.php?id=$idToAdd');</script>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Détail - <?= htmlspecialchars($item['nom']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    /* Navbar identique aux autres pages */
    .topbar {
      background: white;
      padding: 8px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      height: 80px;
    }
    .topbar h2 {
      color: #7b2ff7;
      font-size: 36px;
      font-weight: bold;
    }
    .cart-link {
      display: flex;
      align-items: center;
      text-decoration: none;
      gap: 8px;
      color: #333;
      font-weight: 500;
      transition: 0.2s ease;
      font-size: 16px;
    }
    .cart-link:hover {
      color: #7b2ff7;
    }
    .cart-link img {
      width: 22px;
      height: 22px;
    }
    .logout-btn {
      background: #692be3;
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 500;
      transition: 0.3s ease;
      font-size: 16px;
    }
    
    /* Styles de la page détail */
    body {
      background: #f4f6f9;
      padding: 0 0 40px 0;
    }
    .container {
      display: flex;
      gap: 50px;
      max-width: 1000px;
      margin: 30px auto;
      background: white;
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
    }
    .image-box {
      width: 50%;
    }
    .image-box img {
      width: 100%;
      height: 360px;
      object-fit: contain;
      border-radius: 10px;
      background: white;
    }
    .info {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .info h1 {
      font-size: 28px;
      margin-bottom: 15px;
    }
    .info p {
      font-size: 16px;
      margin-bottom: 20px;
    }
    .info .price {
      color: #27ae60;
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 25px;
    }
    .info .btn-container {
      margin-top: 60px;
    }
    .info a {
      background: #7b2ff7;
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      display: inline-block;
      transition: all 0.3s ease;
    }
    .info a.added {
      background-color: #28a745;
    }
    .info a:hover {
      background: #692be3;
    }
    .stock-info {
      font-size: 14px;
      color: #666;
    }
    .back {
      display: block;
      margin: 40px auto 0;
      text-align: center;
      text-decoration: none;
      color: #7b2ff7;
      font-weight: 600;
    }
    .back-link {
            display: block;
            margin-top: 30px;
            text-align: center;
            text-decoration: none;
            color: #7b2ff7;
            font-weight: 600;
        }
  </style>
</head>
<body>
  <!-- Navbar identique aux autres pages -->
  <div class="topbar">
    <h2>Shopora</h2>
    <div style="display: flex; align-items: center; gap: 20px;">
      <a href="panier.php" class="cart-link">
        <img src="images/Buy.png" alt="Panier">
        Panier
      </a>
      <a href="historique.php" class="cart-link">
        Historique
      </a>
      <a href="logout.php" class="logout-btn">Déconnexion</a>
    </div>
  </div>

  <div class="container">
    <div class="image-box">
      <img src="images/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['nom']) ?>">
    </div>
    <div class="info">
      <div>
        <h1><?= htmlspecialchars($item['nom']) ?></h1>
        <p><?= htmlspecialchars($item['description']) ?></p>
        <div class="price"><?= $item['prix'] ?> DA</div>
        <div class="stock-info">Stock disponible: <?= $item['stock'] ?></div>
      </div>
      <div class="btn-container">
        <a href="?id=<?= $item['id'] ?>&add=<?= $item['id'] ?>" id="addBtn" class="<?= $ajoute ? 'added' : '' ?>">Ajouter au panier</a>
        
      </div>
      
    </div>
   
   
  </div>
  <a href="index.php" class="back-link">← Retour à la boutique</a>
 


  <?php if ($ajoute): ?>
  <script>
    const btn = document.getElementById("addBtn");
    btn.innerText = " Ajouté !";
    btn.style.backgroundColor = "#28a745";
    btn.classList.add("added");
    setTimeout(() => {
      btn.innerText = "Ajouter au panier";
      btn.style.backgroundColor = " #7b2ff7";
      btn.classList.remove("added");
    }, 1000);
  </script>
  <?php endif; ?>
</body>
</html>