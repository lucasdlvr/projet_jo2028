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

// Vérifiez si l'ID de l'utilisateur est fourni dans l'URL
if (!isset($_GET['id_utilisateur'])) {
    $_SESSION['error'] = "ID de l'utilisateur manquant.";
    header("Location: manage-users.php");
    exit();
} else {
    $id_utilisateur = filter_input(INPUT_GET, 'id_utilisateur', FILTER_VALIDATE_INT);

    // Vérifiez si l'ID de l'utilisateur est un entier valide
    if ($id_utilisateur === false) {
        $_SESSION['error'] = "ID de l'utilisateur invalide.";
        header("Location: manage-users.php");
        exit();
    } else {
        try {
            $id_utilisateur = $_GET['id_utilisateur'];
            // Préparez la requête SQL pour supprimer l'utilisateur
            $sql = "DELETE FROM UTILISATEUR WHERE id_utilisateur = :id_utilisateur";
            // Exécutez la requête SQL avec le paramètre
            $statement = $connexion->prepare($sql);
            $statement->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
            $statement->execute();

            // Message de succès
            $_SESSION['success'] = "L'utilisateur a été supprimé avec succès.";

            // Redirigez vers la page précédente après la suppression
            header('Location: manage-users.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-users.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>