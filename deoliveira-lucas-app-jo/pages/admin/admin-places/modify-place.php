<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du lieu est fourni dans l'URL
if (!isset($_GET['id_lieu'])) {
    $_SESSION['error'] = "ID du lieu manquant.";
    header("Location: manage-places.php");
    exit();
}

$id_lieu = filter_input(INPUT_GET, 'id_lieu', FILTER_VALIDATE_INT);

// Vérifiez si l'ID du lieu est un entier valide
if (!$id_lieu && $id_lieu !== 0) {
    $_SESSION['error'] = "ID du lieu invalide.";
    header("Location: manage-places.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations du lieu pour affichage dans le formulaire
try {
    $queryPlace = "SELECT nom_lieu, adresse_lieu, cp_lieu, ville_lieu FROM LIEU WHERE id_lieu = :idPlace";
    $statementPlace = $connexion->prepare($queryPlace);
    $statementPlace->bindParam(":idPlace", $id_lieu, PDO::PARAM_INT);
    $statementPlace->execute();

    if ($statementPlace->rowCount() > 0) {
        $place = $statementPlace->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Lieu non trouvé.";
        header("Location: manage-places.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-places.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomPlace = filter_input(INPUT_POST, 'nomPlace', FILTER_SANITIZE_SPECIAL_CHARS);
    $adressePlace = filter_input(INPUT_POST, 'adressePlace', FILTER_SANITIZE_SPECIAL_CHARS);
    $cpPlace = filter_input(INPUT_POST, 'cpPlace', FILTER_SANITIZE_SPECIAL_CHARS);
    $villePlace = filter_input(INPUT_POST, 'villePlace', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le nom du lieu est vide
    if (empty($nomPlace)) {
        $_SESSION['error'] = "Le nom du lieu ne peut pas être vide.";
        header("Location: modify-place.php?id_lieu=$id_lieu");
        exit();
    }
    // Vérifiez si l'adresse du lieu est vide
    if (empty($adressePlace)) {
        $_SESSION['error'] = "L'adresse du lieu ne peut pas être vide.";
        header("Location: modify-place.php?id_lieu=$id_lieu");
        exit();
    }
    // Vérifiez si le code postal est vide
    if (empty($cpPlace)) {
        $_SESSION['error'] = "Le code postal ne peut pas être vide.";
        header("Location: modify-place.php?id_lieu=$id_lieu");
        exit();
    }
    // Vérifiez si le nom de la ville est vide
    if (empty($villePlace)) {
        $_SESSION['error'] = "Le nom de la ville ne peut pas être vide.";
        header("Location: modify-place.php?id_lieu=$id_lieu");
        exit();
    }

    try {
        // Vérifiez si le lieu existe déjà
        $queryCheck = "SELECT id_lieu FROM LIEU WHERE nom_lieu = :nomPlace AND id_lieu <> :idPlace";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomPlace", $nomPlace, PDO::PARAM_STR);
        $statementCheck->bindParam(":idPlace", $id_lieu, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le lieu existe déjà.";
            header("Location: modify-place.php?id_lieu=$id_lieu");
            exit();
        }
        // Requête pour mettre à jour le lieu
        $query = "UPDATE LIEU SET nom_lieu = :nomPlace, adresse_lieu = :adressePlace, cp_lieu = :cpPlace, ville_lieu = :villePlace WHERE id_lieu = :idPlace";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomPlace", $nomPlace, PDO::PARAM_STR);
        $statement->bindParam(":adressePlace", $adressePlace, PDO::PARAM_STR);
        $statement->bindParam(":cpPlace", $cpPlace, PDO::PARAM_STR);
        $statement->bindParam(":villePlace", $villePlace, PDO::PARAM_STR);
        $statement->bindParam(":idPlace", $id_lieu, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le lieu a été modifié avec succès.";
            header("Location: manage-places.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du lieu.";
            header("Location: modify-place.php?id_lieu=$id_lieu");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-place.php?id_lieu=$id_lieu");
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
    <title>Modifier un Lieu - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Lieu</h1>
        
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

        <form action="modify-place.php?id_lieu=<?php echo $id_lieu; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce lieu ?')">
            <label for="nomPlace">Nom du Lieu :</label>
            <input type="text" name="nomPlace" id="nomPlace"
                value="<?php echo htmlspecialchars($place['nom_lieu']); ?>" required>
            <label for="adressePlace">Nom de l'Adresse :</label>
            <input type="text" name="adressePlace" id="adressePlace"
                value="<?php echo htmlspecialchars($place['adresse_lieu']); ?>" required>
            <label for="cpPlace">Code Postal :</label>
            <input type="text" name="cpPlace" id="cpPlace"
                value="<?php echo htmlspecialchars($place['cp_lieu']); ?>" required>
            <label for="villePlace">Nom de la Ville :</label>
            <input type="text" name="villePlace" id="villePlace"
                value="<?php echo htmlspecialchars($place['ville_lieu']); ?>" required>
            <input type="submit" value="Modifier le Lieu">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-places.php">Retour à la gestion des lieux</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
