<?php
session_start();
include("connexion.php");

// Vérification admin
if (!isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

// Initialisation du tableau des commandes supprimées visuellement
if (!isset($_SESSION['commandes_supprimees'])) {
    $_SESSION['commandes_supprimees'] = [];
}

// Gestion de la suppression visuelle
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    
    if (!in_array($id, $_SESSION['commandes_supprimees'])) {
        $_SESSION['commandes_supprimees'][] = $id;
    }
    
    header("Location: admin_commandes.php");
    exit();
}

// Charger les commandes (en excluant celles marquées comme supprimées)
$query = "
SELECT c.id AS id_commande, u.nom AS client, c.date_commande, c.statut,
       i.nom AS produit, dc.quantite, dc.prix_unitaire
FROM commandes c
JOIN utilisateurs u ON c.id_utilisateur = u.id
JOIN details_commande dc ON dc.id_commande = c.id
JOIN items i ON dc.id_item = i.id
ORDER BY c.date_commande DESC
";

$result = $mysqli->query($query);

// Organiser les résultats par commande
$commandes = [];
while ($row = $result->fetch_assoc()) {
    $id = $row["id_commande"];
    
    // Sauter les commandes marquées comme supprimées
    if (in_array($id, $_SESSION['commandes_supprimees'])) {
        continue;
    }
    
    if (!isset($commandes[$id])) {
        $commandes[$id] = [
            "client" => $row["client"],
            "date" => $row["date_commande"],
            "statut" => $row["statut"],
            "produits" => [],
            "total" => 0
        ];
    }
    $commandes[$id]["produits"][] = $row["produit"] . " (x" . $row["quantite"] . ")";
    $commandes[$id]["total"] += $row["quantite"] * $row["prix_unitaire"];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Commandes - Admin</title>
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
      padding: 20px;
    }
    .header {
      background: white;
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .header h1 {
      font-size: 24px;
      font-weight: 600;
      color: #3b3b3b;
    }
    .header a {
      text-decoration: none;
      color: #7b2ff7;
      font-weight: 500;
    }
    .table-container {
      margin-top: 30px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      padding: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 12px 15px;
      text-align: left;
    }
    th {
      background: #f0f2f5;
      font-weight: 600;
      color: #333;
    }
    tr:nth-child(even) td {
      background: #f9f9f9;
    }
    .produits {
      font-size: 14px;
      color: #333;
    }
    .delete-icon {
      width: 20px;
      height: 20px;
      cursor: pointer;
      transition: 0.2s ease;
      opacity: 0.7;
    }
    .delete-icon:hover {
      opacity: 1;
    }
    .actions {
      text-align: right;
    }
  </style>
</head>
<body>
  <div class="header">
    <h1>Liste des commandes</h1>
    <a href="admin.php">Retour admin</a>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
      <?= $_SESSION['success'] ?>
      <?php unset($_SESSION['success']); ?>
    </div>
  <?php endif; ?>

  <div class="table-container">
    <?php if (empty($commandes)): ?>
      <p style="text-align:center; padding: 20px;">Aucune commande à afficher.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Client</th>
            <th>Date</th>
            <th>Produits</th>
            <th>Total (DA)</th>
            <th>Statut</th>
            <th class="actions">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commandes as $id => $commande): ?>
            <tr>
              <td><?= htmlspecialchars($commande["client"]) ?></td>
              <td><?= $commande["date"] ?></td>
              <td class="produits"><?= implode(", ", $commande["produits"]) ?></td>
              <td><?= number_format($commande["total"], 2, ',', ' ') ?></td>
              <td><?= ucfirst($commande["statut"]) ?></td>
              <td class="actions">
                <a href="admin_commandes.php?supprimer=<?= $id ?>" onclick="return confirm('Masquer cette commande de l\'affichage ?')">
                  <img src="images/delete-icon.png" alt="Supprimer visuellement" class="delete-icon">
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>