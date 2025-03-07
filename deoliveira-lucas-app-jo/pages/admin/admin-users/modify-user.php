<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'utilisateur est fourni dans l'URL
if (!isset($_GET['id_utilisateur'])) {
    $_SESSION['error'] = "ID de l'utilisateur manquant.";
    header("Location: manage-users.php");
    exit();
}

$id_utilisateur = filter_input(INPUT_GET, 'id_utilisateur', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'utilisateur est un entier valide
if (!$id_utilisateur && $id_utilisateur !== 0) {
    $_SESSION['error'] = "ID de l'utilisateur invalide.";
    header("Location: manage-users.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'utilisateur pour affichage dans le formulaire
try {
    $queryUser = "SELECT nom_utilisateur, prenom_utilisateur, login, password FROM UTILISATEUR WHERE id_utilisateur = :idUser";
    $statementUser = $connexion->prepare($queryUser);
    $statementUser->bindParam(":idUser", $id_utilisateur, PDO::PARAM_INT);
    $statementUser->execute();

    if ($statementUser->rowCount() > 0) {
        $user = $statementUser->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header("Location: manage-users.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-users.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomUser = filter_input(INPUT_POST, 'nomUser', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomUser = filter_input(INPUT_POST, 'prenomUser', FILTER_SANITIZE_SPECIAL_CHARS);
    $loginUser = filter_input(INPUT_POST, 'loginUser', FILTER_SANITIZE_SPECIAL_CHARS);
    $passwordUser = filter_input(INPUT_POST, 'passwordUser', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le nom de l'utilisateur est vide
    if (empty($nomUser)) {
        $_SESSION['error'] = "Le nom de l'utilisateur ne peut pas être vide.";
        header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
        exit();
    }
    // Vérifiez si le prenom de l'utilisateur est vide
    if (empty($prenomUser)) {
        $_SESSION['error'] = "Le prenom de l'utilisateur ne peut pas être vide.";
        header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
        exit();
    }
    // Vérifiez si le login de l'utilisateur est vide
    if (empty($loginUser)) {
        $_SESSION['error'] = "Le login de l'utilisateur ne peut pas être vide.";
        header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
        exit();
    }
    // Vérifiez si le mot de passe de l'utilisateur est vide
    if (empty($passwordUser)) {
        $_SESSION['error'] = "Le mot de passe de l'utilisateur ne peut pas être vide.";
        header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
        exit();
    }

    try {
        // Vérifiez si l'utilisateur existe déjà
        $queryCheck = "SELECT id_utilisateur FROM UTILISATEUR WHERE nom_utilisateur = :nomUser AND id_utilisateur <> :idUser";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomUser", $nomUser, PDO::PARAM_STR);
        $statementCheck->bindParam(":idUser", $id_utilisateur, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'utilisateur existe déjà.";
            header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
            exit();
        }
        // Hachage du mot de passe pour la base de donnée
        $hashedPassword = password_hash($passwordUser, PASSWORD_BCRYPT);
        // Requête pour mettre à jour l'utilisateur
        $query = "UPDATE UTILISATEUR SET nom_utilisateur = :nomUser, prenom_utilisateur = :prenomUser, login = :loginUser, password = :passwordUser WHERE id_utilisateur = :idUser";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomUser", $nomUser, PDO::PARAM_STR);
        $statement->bindParam(":prenomUser", $prenomUser, PDO::PARAM_STR);
        $statement->bindParam(":loginUser", $loginUser, PDO::PARAM_STR);
        $statement->bindParam(":passwordUser", $passwordUser, PDO::PARAM_STR);
        $statement->bindParam(":idUser", $id_utilisateur, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'utilisateur a été modifié avec succès.";
            header("Location: manage-users.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur.";
            header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-user.php?id_utilisateur=$id_utilisateur");
        exit();
    }
}
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
    <title>Modifier un Utilisateur - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Utilisateur</h1>
        
        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-user.php?id_utilisateur=<?php echo $id_utilisateur; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet utilisateur?')">
            <label for="nomUser">Nom de l'Utilisateur :</label>
            <input type="text" name="nomUser" id="nomUser"
                value="<?php echo htmlspecialchars($user['nom_utilisateur']); ?>" required>
            <label for="prenomUser">Prénom de l'Utilisateur :</label>
            <input type="text" name="prenomUser" id="prenomUser"
                value="<?php echo htmlspecialchars($user['prenom_utilisateur']); ?>" required>
            <label for="loginUser">Login de l'Utilisateur :</label>
            <input type="text" name="loginUser" id="loginUser"
                value="<?php echo htmlspecialchars($user['login']); ?>" required>
            <label for="passwordUser">Mot de passe de l'Utilisateur :</label>
            <input type="password" name="passwordUser" id="passwordUser"
                value="<?php echo htmlspecialchars($user['password']); ?>" required>
            <input type="submit" value="Modifier l'Utilisateur">
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
