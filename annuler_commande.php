<?php
session_start();
include("connexion.php");

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id_commande'])) {
    header('Location: historique.php');
    exit();
}

$id_commande = intval($_GET['id_commande']);
$id_utilisateur = $_SESSION['id'];

try {
    // Vérifier que la commande appartient bien à l'utilisateur
    $stmt = $mysqli->prepare("SELECT id FROM commandes WHERE id = ? AND id_utilisateur = ?");
    $stmt->bind_param("ii", $id_commande, $id_utilisateur);
    $stmt->execute();
    
    if (!$stmt->get_result()->num_rows) {
        throw new Exception("Commande introuvable ou ne vous appartenant pas");
    }

    // Démarrer la transaction
    $mysqli->begin_transaction();

    // 1. Supprimer les détails de commande (ce qui déclenchera le trigger)
    $stmt = $mysqli->prepare("DELETE FROM details_commande WHERE id_commande = ?");
    $stmt->bind_param("i", $id_commande);
    $stmt->execute();

    // 2. Marquer la commande comme annulée
    $stmt = $mysqli->prepare("UPDATE commandes SET statut = 'annulée' WHERE id = ?");
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
?>