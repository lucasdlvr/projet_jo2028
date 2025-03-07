<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomUser = filter_input(INPUT_POST, 'nomUser', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomUser = filter_input(INPUT_POST, 'prenomUser', FILTER_SANITIZE_SPECIAL_CHARS);
    $loginUser = filter_input(INPUT_POST, 'loginUser', FILTER_SANITIZE_SPECIAL_CHARS);
    $passwordUser = filter_input(INPUT_POST, 'passwordUser', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-user.php");
        exit();
    }

    // Vérifiez si le nom de l'utilisateur est vide
    if (empty($nomUser)) {
        $_SESSION['error'] = "Le nom de l'utilisateur ne peut pas être vide.";
        header("Location: add-user.php");
        exit();
    }

    try {
        // Vérifiez si l'utilisateur existe déjà
        $queryCheck = "SELECT id_utilisateur FROM UTILISATEUR WHERE nom_utilisateur = :nomUser";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomUser", $nomUser, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'utilisateur existe déjà.";
            header("Location: add-user.php");
            exit();
        } else {
            // Hachage du mot de passe pour la base de donnée
            $hashedPassword = password_hash($passwordUser, PASSWORD_BCRYPT);
            // Requête pour ajouter un utilisateur
            $query = "INSERT INTO UTILISATEUR (nom_utilisateur, prenom_utilisateur, login, password) VALUES (:nomUser, :prenomUser, :loginUser, :passwordUser)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomUser", $nomUser, PDO::PARAM_STR);
            $statement->bindParam(":prenomUser", $prenomUser, PDO::PARAM_STR);
            $statement->bindParam(":loginUser", $loginUser, PDO::PARAM_STR);
            $statement->bindParam(":passwordUser", $hashedPassword, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'utilisateur a été ajouté avec succès.";
                header("Location: manage-users.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur.";
                header("Location: add-user.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-user.php");
        exit();
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Ajouter un Utilisateur - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
    </style>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-genders/manage-genders.php">Gestion Genres</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Utilisateur</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="add-user.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet utilisateur ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomUser">Nom de l'Utilisateur :</label>
            <input type="text" name="nomUser" id="nomUser" required>
            <label for="prenomUser">Prénom de l'Utilisateur :</label>
            <input type="text" name="prenomUser" id="prenomUser" required>
            <label for="loginUser">Login de l'Utilisateur :</label>
            <input type="text" name="loginUser" id="loginUser" required>
            <label for="passwordUser">Mot de passe de l'Utilisateur :</label>
            <input type="password" name="passwordUser" id="passwordUser" required>
            <input type="submit" value="Ajouter l'Utilisateur">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion des utilisateurs</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>
