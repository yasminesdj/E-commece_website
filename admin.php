<?php
session_start();
include("connexion.php");

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

$showForm = false;

if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);
    $res = $mysqli->prepare("SELECT image FROM items WHERE id = ?");
    $res->bind_param("i", $id);
    $res->execute();
    $row = $res->get_result()->fetch_assoc();
    if ($row && file_exists("images/" . $row["image"])) {
        unlink("images/" . $row["image"]);
    }
    $stmt = $mysqli->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin.php?success=1");
        exit();
    } else {
        die("❌ Erreur suppression: " . $stmt->error);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST["nom"];
    $description = $_POST["description"];
    $prix = $_POST["prix"];
    $stock = $_POST["stock"];
    $image_name = basename($_FILES["image"]["name"]);
    $target = "images/" . $image_name;
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
        $stmt = $mysqli->prepare("INSERT INTO items (nom, description, prix, stock, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $nom, $description, $prix, $stock, $image_name);
        $stmt->execute();
        header("Location: admin.php");
        exit();
    } else {
        echo "<script>alert('Erreur lors de l\'upload de l\'image');</script>";
        $showForm = true;
    }
}

if (isset($_GET['ajouter'])) {
    $showForm = true;
}

$items = $mysqli->query("SELECT * FROM items");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Admin Shopora</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #f9f9f9; }
    .navbar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
    .navbar h2 { color: #333; font-weight: 600; }
    .navbar a { margin-left: 20px; text-decoration: none; color: #333; font-weight: 500; transition: 0.2s; }
    .navbar a:hover { color: #7b2ff7; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 7fr)); gap: 25px; padding: 30px; }
    .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 20px; text-align: center; transition: 0.2s ease; }
    .card:hover { transform: translateY(-5px); }
    .card img { width: 100%; height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
    .card h3 { font-size: 18px; margin-bottom: 6px; color: #333; }
    .card p { font-size: 14px; color: #666; margin-bottom: 10px; }
    .card .price { color: #27ae60; font-weight: 600; margin-bottom: 8px; }
    .btn-supprimer { background: #692be3; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-size: 14px; transition: 0.2s ease; }
    .btn-supprimer:hover { background: #692be3; }
    .modal, .form-overlay {
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .modal-content, .form-popup {
      background: white;
      padding: 30px;
      border-radius: 12px;
      text-align: left;
      width: 90%;
      max-width: 500px;
      position: relative;
    }
    .modal-content h3, .form-popup h3 {
      margin-bottom: 20px;
      color: #333;
      text-align: center;
    }
    .form-popup label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    .form-popup input, .form-popup textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    .form-popup button[type="submit"] {
      padding: 10px 20px;
      background: #7b2ff7;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      display: block;
      margin: 0 auto;
    }
    .actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 20px;
}
.confirm {
  background: #7b2ff7;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}
.cancel {
  background: #eee;
  color: #333;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

    .close-btn {
      position: absolute;
      top: 12px;
      right: 30px;
      background: none;
      border: none;
      font-size: 40px;
      color:rgb(0, 0, 0);
      cursor: pointer;
    }
    .titre-principal {
  color: #7b2ff7;
  font-size: 36px;
  font-weight: bold;
}
.logout-btn {
  background: #692be3;
  color: white  !important;
  padding: 8px 16px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: 0.3s ease;
}

.logout-btn:hover {
  background: #692be3;
}

  </style>
</head>
<body>

<div class="navbar">
<h1 class="titre-principal">Shopora</h1>
  <div>
    <a href="admin.php">Accueil</a>
    <a href="admin.php?ajouter=1">Ajouter un produit</a>
    <a href="admin_commandes.php">Commandes</a>
    <a href="logout.php" class="logout-btn">Déconnexion</a>
  </div>
</div>

<div class="grid">
  <?php while ($item = $items->fetch_assoc()) : ?>
    <div class="card" data-id="<?= $item['id'] ?>">
      <img src="images/<?= $item['image'] ?>" alt="<?= $item['nom'] ?>">
      <h3><?= htmlspecialchars($item['nom']) ?></h3>
      <p><?= htmlspecialchars($item['description']) ?></p>
      <div class="price"><?= $item['prix'] ?> DA</div>
      <button class="btn-supprimer" onclick="confirmDelete(<?= $item['id'] ?>)">Supprimer</button>
    </div>
  <?php endwhile; ?>
</div>

<div class="modal" id="confirmModal" style="display: none;">
  <div class="modal-content">
    <h3>Voulez-vous vraiment supprimer ce produit ?</h3>
    <div class="actions">
      <button class="confirm" id="confirmBtn">Oui, supprimer</button>
      <button class="cancel" onclick="closeModal()">Annuler</button>
    </div>
  </div>
</div>

<?php if ($showForm): ?>
  <div class="form-overlay" id="formOverlay">
    <form class="form-popup" method="POST" enctype="multipart/form-data">
      <button type="button" class="close-btn" onclick="window.location.href='admin.php'">&times;</button>
      <h3>Ajouter un produit</h3>
      <label>Nom</label>
      <input type="text" name="nom" required>
      <label>Description</label>
      <textarea name="description" rows="3" required></textarea>
      <label>Prix (DA)</label>
      <input type="number" name="prix" step="0.01" required>
      <label>Stock</label>
      <input type="number" name="stock" required>
      <label>Image</label>
      <input type="file" name="image" accept="image/*" required>
      <button type="submit">Ajouter</button>
    </form>
  </div>
<?php endif; ?>

<script>
  let deleteId = null;

  function confirmDelete(id) {
    deleteId = id;
    document.getElementById('confirmModal').style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('confirmModal').style.display = 'none';
  }

  window.onload = () => {
    closeModal();
  };

  document.getElementById("confirmBtn").onclick = function () {
    window.location.href = "admin.php?delete=" + deleteId;
  };
</script>

</body>
</html>
