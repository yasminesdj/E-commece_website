<?php
session_start();
include("connexion.php");

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$id_utilisateur = $_SESSION['id'];
$commandes = [];

// Récupérer toutes les commandes de l'utilisateur
$stmt = $mysqli->prepare("
    SELECT c.id AS id_commande, c.date_commande, c.statut, 
           SUM(dc.quantite * dc.prix_unitaire) AS total
    FROM commandes c
    LEFT JOIN details_commande dc ON c.id = dc.id_commande
    WHERE c.id_utilisateur = ?
    GROUP BY c.id
    ORDER BY c.date_commande DESC
");
$stmt->bind_param("i", $id_utilisateur);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $commandes[] = $row;
}

// Gestion de l'annulation de commande
if (isset($_GET['annuler'])) {
    $id_commande = intval($_GET['annuler']);
    
    try {
        // Démarrer une transaction
        $mysqli->begin_transaction();

        // 1. D'abord marquer la commande comme annulée
        $stmt = $mysqli->prepare("UPDATE commandes SET statut = 'annulée' WHERE id = ? AND id_utilisateur = ?");
        $stmt->bind_param("ii", $id_commande, $id_utilisateur);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Commande introuvable ou ne vous appartenant pas");
        }

        // 2. Ensuite supprimer les détails (le trigger vérifiera le statut)
        $stmt = $mysqli->prepare("DELETE FROM details_commande WHERE id_commande = ?");
        $stmt->bind_param("i", $id_commande);
        $stmt->execute();

        // Valider la transaction
        $mysqli->commit();
        
        $_SESSION['success'] = "Commande #$id_commande annulée avec succès";
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error'] = "Erreur lors de l'annulation : " . $e->getMessage();
    }
    
    header("Location: historique.php");
    exit();
}

// Gestion de la suppression visuelle
if (isset($_GET['supprimer'])) {
    if (!isset($_SESSION['commandes_supprimees'])) {
        $_SESSION['commandes_supprimees'] = [];
    }
    $_SESSION['commandes_supprimees'][] = intval($_GET['supprimer']);
    $_SESSION['success'] = "Commande masquée de votre historique";
    header("Location: historique.php");
    exit();
}

// Filtrer les commandes supprimées visuellement
if (isset($_SESSION['commandes_supprimees'])) {
    $commandes = array_filter($commandes, function($cmd) {
        return !in_array($cmd['id_commande'], $_SESSION['commandes_supprimees']);
    });
}
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
        .action-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .action-btn img {
            width: 22px;
            height: 22px;
            transition: 0.2s ease;
        }
        .action-btn img:hover {
            transform: scale(1.2);
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
        .status-validée {
            color: #28a745;
        }
        .status-annulée {
            color: #dc3545;
        }
        .success-msg, .error-msg {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success-msg {
            background-color: #d4edda;
            color: #155724;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📜 Historique de vos commandes</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-msg"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-msg"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (count($commandes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $commande): ?>
                    <tr>
                        <td><?= $commande['id_commande'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></td>
                        <td><?= number_format($commande['total'], 2) ?> DA</td>
                        <td class="status-<?= $commande['statut'] ?>"><?= ucfirst($commande['statut']) ?></td>
                        <td>
                            <form method="get" onsubmit="
                                if('<?= $commande['statut'] ?>' === 'validée') {
                                    return confirm('Voulez-vous annuler cette commande ? Le stock sera réapprovisionné.');
                                } else {
                                    return confirm('Supprimer cette commande de votre historique ?');
                                }
                            ">
                                <?php if ($commande['statut'] === 'validée'): ?>
                                    <input type="hidden" name="annuler" value="<?= $commande['id_commande'] ?>">
                                <?php else: ?>
                                    <input type="hidden" name="supprimer" value="<?= $commande['id_commande'] ?>">
                                <?php endif; ?>
                                <button type="submit" class="action-btn" title="<?= $commande['statut'] === 'validée' ? 'Annuler la commande' : 'Supprimer de l\'historique' ?>">
                                    <img src="images/delete-icon.png" alt="Supprimer">
                                </button>
                            </form>
                        </td>
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