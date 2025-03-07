<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du pays est fourni dans l'URL
if (!isset($_GET['id_pays'])) {
    $_SESSION['error'] = "ID du pays manquant.";
    header("Location: manage-countries.php");
    exit();
}

$id_pays = filter_input(INPUT_GET, 'id_pays', FILTER_VALIDATE_INT);

// Vérifiez si l'ID du pays est un entier valide
if (!$id_pays && $id_pays !== 0) {
    $_SESSION['error'] = "ID du pays invalide.";
    header("Location: manage-countries.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations du pays pour affichage dans le formulaire
try {
    $queryCountrie = "SELECT nom_pays FROM PAYS WHERE id_pays = :idCountrie";
    $statementCountrie = $connexion->prepare($queryCountrie);
    $statementCountrie->bindParam(":idCountrie", $id_pays, PDO::PARAM_INT);
    $statementCountrie->execute();

    if ($statementCountrie->rowCount() > 0) {
        $countrie = $statementCountrie->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Pays non trouvé.";
        header("Location: manage-countries.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-countries.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomCountrie = filter_input(INPUT_POST, 'nomCountrie', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le nom du pays est vide
    if (empty($nomCountrie)) {
        $_SESSION['error'] = "Le nom du pays ne peut pas être vide.";
        header("Location: modify-countrie.php?id_pays=$id_pays");
        exit();
    }

    try {
        // Vérifiez si le pays existe déjà
        $queryCheck = "SELECT id_pays FROM PAYS WHERE nom_pays = :nomCountrie AND id_pays <> :idCountrie";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomCountrie", $nomCountrie, PDO::PARAM_STR);
        $statementCheck->bindParam(":idCountrie", $id_pays, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le pays existe déjà.";
            header("Location: modify-countrie.php?id_pays=$id_pays");
            exit();
        }
        // Requête pour mettre à jour le pays
        $query = "UPDATE PAYS SET nom_pays = :nomCountrie WHERE id_pays = :idCountrie";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomCountrie", $nomCountrie, PDO::PARAM_STR);
        $statement->bindParam(":idCountrie", $id_pays, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le pays a été modifié avec succès.";
            header("Location: manage-countries.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du pays.";
            header("Location: modify-countrie.php?id_pays=$id_pays");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-countrie.php?id_pays=$id_pays");
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
    <title>Modifier un Pays - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Pays</h1>
        
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

        <form action="modify-countrie.php?id_pays=<?php echo $id_pays; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce pays ?')">
            <label for="nomCountrie">Nom du Pays :</label>
            <input type="text" name="nomCountrie" id="nomCountrie"
                value="<?php echo htmlspecialchars($countrie['nom_pays']); ?>" required>
            <input type="submit" value="Modifier le Pays">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-countries.php">Retour à la gestion des pays</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
