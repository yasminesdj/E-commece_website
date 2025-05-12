<?php
session_start();

// Suppression de toutes les variables de session
$_SESSION = array();

// Destruction du cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruction de la session
session_destroy();

// Suppression des cookies personnalisés
setcookie("email", "", time() - 3600, "/", "", true, true);       // Cookie email
setcookie("nom_utilisateur", "", time() - 3600, "/", "", true, true); // Cookie nom (optionnel)

// Redirection vers la page de login avec header() + exit()
header("Location: login.php");
exit();
?>