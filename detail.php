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
    body {
      background: #f4f6f9;
      padding: 40px;
    }
    .topbar {
      background: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      margin-bottom: 40px;
    }
    .topbar h2 {
      color: #7b2ff7;
      font-size: 36px;
      font-weight: bold;
    }
    .container {
      display: flex;
      gap: 50px;
      max-width: 1000px;
      margin: auto;
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
  </style>
</head>
<body>
<div class="topbar">
  <h2>Shopora</h2>
  <a href="index.php" style="color: #7b2ff7; text-decoration: none; font-weight: 500;">← Retour à la boutique</a>
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
      <div class="stock-info">Stock disponible: <?=  $item['stock'] ?></div>
    </div>
    <div class="btn-container">
      <a href="?id=<?= $item['id'] ?>&add=<?= $item['id'] ?>" id="addBtn" class="<?= $ajoute ? 'added' : '' ?>">Ajouter au panier</a>
    </div>
  </div>
</div>

<?php if ($ajoute): ?>
<script>
  const btn = document.getElementById("addBtn");
  btn.innerText = "✔️ Ajouté !";
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
