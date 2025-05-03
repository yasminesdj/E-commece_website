<?php
session_start();
include("connexion.php");

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$id_utilisateur = $_SESSION['id'];
$commandes = [];
$annulees = [];

// Récupérer commandes valides
$stmt = $mysqli->prepare("SELECT id AS id_commande, date_commande, 'validée' AS statut FROM commandes WHERE id_utilisateur = ? ORDER BY date_commande DESC");
$stmt->bind_param("i", $id_utilisateur);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stmt_total = $mysqli->prepare("SELECT SUM(quantite * prix_unitaire) AS total FROM details_commande WHERE id_commande = ?");
    $stmt_total->bind_param("i", $row['id_commande']);
    $stmt_total->execute();
    $res_total = $stmt_total->get_result()->fetch_assoc();
    $row['total'] = $res_total['total'] ?? 0;
    $commandes[] = $row;
}

// Récupérer commandes annulées
$query = "
SELECT ha.id_commande, MAX(ha.date_annulation) as date_commande, 'annulée' AS statut, SUM(ha.quantite * i.prix) AS total
FROM historique_annulation ha
JOIN items i ON ha.id_item = i.id
JOIN commandes c ON c.id = ha.id_commande
WHERE c.id_utilisateur = ?
GROUP BY ha.id_commande
ORDER BY date_commande DESC
";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id_utilisateur);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $annulees[] = $row;
}

// Fusionner les deux tableaux
$toutes_commandes = array_merge($commandes, $annulees);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des commandes - Shopora</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0; padding: 0; box-sizing: border-box;
        }
        body {
            background: #f4f6f9;
            padding: 40px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        h1 {
            text-align: center;
            color: #7b2ff7;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background-color: #7b2ff7;
            color: white;
            font-weight: 600;
        }
        td:last-child {
            text-align: center;
        }
        .empty-msg {
            text-align: center;
            color: #555;
            margin-top: 20px;
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
<div class="container">
    <h1>📜 Historique de vos commandes</h1>

    <?php if (count($toutes_commandes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($toutes_commandes as $commande): ?>
                    <tr>
                        <td><?= $commande['id_commande'] ?></td>
                        <td><?= $commande['date_commande'] ?></td>
                        <td><?= number_format($commande['total'], 2) ?> DA</td>
                        <td><?= ucfirst($commande['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-msg">Vous n'avez encore passé aucune commande.</p>
    <?php endif; ?>

    <a href="index.php" class="back-link">← Retour à la boutique</a>
</div>
</body>
</html>
