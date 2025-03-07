<?php
session_start();
require_once("../../../database/database.php");

// Protection CSRF
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header('Location: ../../../index.php');
        exit();
    }
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Génération du token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si l'ID de l'épreuve est fourni dans l'URL
if (!isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'épreuve manquant.";
    header("Location: manage-events.php");
    exit();
} else {
    $id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de l'épreuve est un entier valide
    if ($id_epreuve === false) {
        $_SESSION['error'] = "ID de l'épreuve invalide.";
        header("Location: manage-events.php");
        exit();
    } else {
        try {
            $id_epreuve = $_GET['id_epreuve'];
            // Préparez la requête SQL pour supprimer l'épreuve
            $sql = "DELETE FROM EPREUVE WHERE id_epreuve = :id_epreuve";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_epreuve', $id_epreuve, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "L'épreuve a été supprimé avec succès.";

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-events.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'épreuve : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-events.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>