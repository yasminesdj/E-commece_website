<?php
session_start();
include("connexion.php");

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION["panier"])) {
    $_SESSION["panier"] = [];
}

if (isset($_GET["add"])) {
    $id = intval($_GET["add"]);
    $_SESSION["panier"][$id] = 1;

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

    header("Location: index.php");
    exit();
}

$search = $_GET["search"] ?? "";
$sort = $_GET["sort"] ?? "";

$query = "SELECT * FROM items WHERE nom LIKE ?";
$param = "%" . $search . "%";

if ($sort == "alpha") {
    $query .= " ORDER BY nom ASC";
} elseif ($sort == "prix_asc") {
    $query .= " ORDER BY prix ASC";
} elseif ($sort == "prix_desc") {
    $query .= " ORDER BY prix DESC";
}

// Cookie pour préférence de tri
if (isset($_GET["sort"])) {
  setcookie("sort_pref", $_GET["sort"], [
      'expires' => time() + (86400 * 30),
      'path' => '/',
      'secure' => true,
      'httponly' => true,
      'samesite' => 'Strict'
  ]);
}

$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $param);
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Boutique Shopora</title>
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
    }
    .topbar {
      background: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
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
      width: 28px;
      height: 28px;
    }
    .carousel {
      background: linear-gradient(90deg, #8341f0, #1a093f);
      border-radius: 15px;
      padding: 40px;
      margin: 30px auto;
      width: 60%;
      color: white;
      position: relative;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .carousel-text h3 {
      font-size: 26px;
      margin-bottom: 10px;
    }
    .carousel-text h1 {
      font-size: 36px;
      font-weight: 700;
    }
    .carousel-text p {
      margin-top: 8px;
      font-size: 16px;
    }
    .carousel img.product-img {
      height: 300px;
      witdh: 80px;
      transition: opacity 0.5s ease;
    }
    .nav-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: white;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 6px rgba(0,0,0,0.2);
      cursor: pointer;
    }
    .nav-btn img {
      width: 24px;
    }
    .nav-left {
      left: -30px;
    }
    .nav-right {
      right: -30px;
    }
    .header {
      display: flex;
      justify-content: center;
      margin: 30px 0 20px;
    }
    form {
      display: flex;
      gap: 10px;
      background: white;
      padding: 15px 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    input, select, button {
      padding: 10px;
      font-size: 14px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    button {
      background: #7b2ff7;
      color: white;
      border: none;
      cursor: pointer;
      transition: 0.2s ease;
    }
    button:hover {
      background: #692be3;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 7fr));
      gap: 25px;
      padding: 0 60px 50px;
    }
    .card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      padding: 30px;
      text-align: center;
      transition: 0.3s ease;
      cursor: pointer;
      position: relative;
    }
    .card:hover {
      transform: translateY(-6px);
    }
    .card img {
      width: 100%;
      height: 300px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 12px;
    }
    .card h3 {
      font-size: 18px;
      color: #333;
      margin-bottom: 6px;
    }
    .card .price {
      color: #27ae60;
      font-weight: 600;
      margin-bottom: 12px;
    }
    .card .add-to-cart {
      background: #7b2ff7;
      color: white;
      padding: 10px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      transition: 0.2s ease;
      display: inline-block;
      border: none;
      cursor: pointer;
    }
    .card .add-to-cart:hover {
      background: #692be3;
    }
    .logout-btn {
      background: #692be3;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: 0.3s ease;
    }
    .logout-btn:hover {
      background: #692be3;
    }
    .card-content {
      pointer-events: none;
    }
    .clear-btn {
  background: #7b2ff7;
  color: white;
  padding: 10px;
  font-size: 14px;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.2s ease;
}

.clear-btn:hover {
  background: #7b2ff7;
}

form {
  display: flex;
  gap: 10px;
  background: white;
  padding: 15px 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  align-items: center;
}
  </style>
</head>
<body>

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

<div class="carousel">
  <div class="carousel-text">
    <h3 id="carouselSubtitle">Meilleure offre en ligne sur les montres connectées</h3>
    <h1 id="carouselTitle">ACCESSOIRES INTELLIGENTS</h1>
    <p id="carouselPromo">Jusqu’à 80 % de réduction</p>
  </div>
  <img id="carouselImage" class="product-img" src="images/smartwatch.jpg" alt="Produit Vedette">
  <div class="nav-btn nav-left" onclick="changeImage(-1)">
    <img src="images/Arrow - Right 3.png" alt="Left">
  </div>
  <div class="nav-btn nav-right" onclick="changeImage(1)">
    <img src="images/Arrow - Right 3 (2).png" alt="Right">
  </div>
</div>

<div class="header">
  <form method="GET">
    <input type="text" name="search" placeholder="Recherche par nom..." value="<?= htmlspecialchars($search) ?>">
    <select name="sort">
      <option value="">Trier</option>
      <option value="alpha" <?= $sort=="alpha"?"selected":"" ?>>A → Z</option>
      <option value="prix_asc" <?= $sort=="prix_asc"?"selected":"" ?>>Prix croissant</option>
      <option value="prix_desc" <?= $sort=="prix_desc"?"selected":"" ?>>Prix décroissant</option>
    </select>
    <button type="submit">Filtrer</button>
    <button type="button" onclick="clearFilters()" class="clear-btn">Effacer les filtres</button>
  </form>
</div>

<div class="grid">
  <?php while ($item = $result->fetch_assoc()) : ?>
    <div class="card" onclick="window.location.href='detail.php?id=<?= $item['id'] ?>'">
      <div class="card-content">
        <img src="images/<?= $item['image'] ?>" alt="<?= $item['nom'] ?>">
        <h3><?= htmlspecialchars($item['nom']) ?></h3>
        <div class="price"><?= $item['prix'] ?> DA</div>
      </div>
      <button class="add-to-cart" onclick="event.stopPropagation(); showAddEffect(this, <?= $item['id'] ?>)">Ajouter au panier</button>
    </div>
  <?php endwhile; ?>
</div>

<script>
const carouselData = [
  {
    img: 'images/smartwatch.jpg',
    title: 'ACCESSOIRES INTELLIGENTS',
    subtitle: 'Meilleure offre en ligne sur les montres connectées',
    promo: 'Jusqu’à 80 % de réduction'
  },
  {
    img: 'images/headphones.jpg',
    title: 'SON SANS FIL.',
    subtitle: 'Casques de haute qualité rien que pour vous',
    promo: 'Jusqu’à 50 % de réduction'
  },
  {
    img: 'images/shoes.jpg',
    title: 'BASKETS NIKE',
    subtitle: 'Courez plus vite, ayez plus de style.',
    promo: 'Jusqu’à 50 % de réduction'
  }
];

let currentIndex = 0;

function updateCarousel() {
  const data = carouselData[currentIndex];
  document.getElementById('carouselImage').src = data.img;
  document.getElementById('carouselTitle').innerText = data.title;
  document.getElementById('carouselSubtitle').innerText = data.subtitle;
  document.getElementById('carouselPromo').innerText = data.promo;
}

function changeImage(direction) {
  currentIndex = (currentIndex + direction + carouselData.length) % carouselData.length;
  updateCarousel();
}

setInterval(() => changeImage(1), 5000);

function showAddEffect(btn, id) {
  btn.innerText = " Ajouté !";
  btn.style.backgroundColor = "#28a745";
  btn.style.transform = "scale(1.1)";
  btn.style.transition = "all 0.3s ease";
  
  // Envoyer une requête pour ajouter au panier sans recharger la page
  fetch(`index.php?add=${id}`)
    .then(() => {
      setTimeout(() => {
        btn.innerText = "Ajouter au panier";
        btn.style.backgroundColor = "#7b2ff7";
        btn.style.transform = "scale(1)";
      }, 1000);
    });
  
  // Empêcher la navigation vers la page de détail
  event.preventDefault();
  event.stopPropagation();
}

function clearFilters() {
  // Rediriger vers la page sans paramètres de filtre
  window.location.href = window.location.pathname;
}
window.onload = updateCarousel;
</script>

</body>
</html>