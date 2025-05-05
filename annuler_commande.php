<?php
session_start();
include("connexion.php");

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id_commande']) || !ctype_digit($_GET['id_commande'])) {
    $_SESSION['error'] = "Identifiant de commande invalide";
    header('Location: historique.php');
    exit();
}

$id_commande = intval($_GET['id_commande']);
$id_utilisateur = $_SESSION['id'];

try {
    // Vérification que la commande existe et appartient à l'utilisateur
    $stmt = $mysqli->prepare("SELECT id, statut FROM commandes WHERE id = ? AND id_utilisateur = ?");
    $stmt->bind_param("ii", $id_commande, $id_utilisateur);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Commande introuvable ou ne vous appartenant pas");
    }
    
    $commande = $result->fetch_assoc();
    if ($commande['statut'] === 'annulée') {
        throw new Exception("Cette commande a déjà été annulée");
    }

    // Démarrer la transaction
    $mysqli->begin_transaction();

    // 1. Restaurer les stocks
    $stmt = $mysqli->prepare("
        UPDATE items i
        JOIN details_commande dc ON i.id = dc.id_item
        SET i.stock = i.stock + dc.quantite
        WHERE dc.id_commande = ?
    ");
    $stmt->bind_param("i", $id_commande);
    $stmt->execute();

    // 2. Mettre à jour le statut (ceci déclenchera le trigger AFTER UPDATE)
    $stmt = $mysqli->prepare("UPDATE commandes SET statut = 'annulée', date_annulation = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id_commande);
    $stmt->execute();

    // Valider la transaction
    $mysqli->commit();

    $_SESSION['success'] = "Commande #$id_commande annulée avec succès";
    
} catch (mysqli_sql_exception $e) {
    $mysqli->rollback();
    $_SESSION['error'] = "Erreur technique lors de l'annulation : " . $e->getMessage();
} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: historique.php");
exit();
?>