<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'athlète est fourni dans l'URL
if (!isset($_GET['id_athlete'])) {
    $_SESSION['error'] = "ID de l'athlète manquant.";
    header("Location: manage-athletes.php");
    exit();
}

$id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'athlète est un entier valide
if (!$id_athlete && $id_athlete !== 0) {
    $_SESSION['error'] = "ID de l'athlète invalide.";
    header("Location: manage-athletes.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'athlète pour affichage dans le formulaire
try {
    $queryAthlete = "SELECT nom_athlete, prenom_athlete, id_pays, id_genre FROM ATHLETE WHERE id_athlete = :idAthlete";
    $statementAthlete = $connexion->prepare($queryAthlete);
    $statementAthlete->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
    $statementAthlete->execute();

    if ($statementAthlete->rowCount() > 0) {
        $athlete = $statementAthlete->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Athlète non trouvé.";
        header("Location: manage-athletes.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-athletes.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomAthlete = filter_input(INPUT_POST, 'nomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomAthlete = filter_input(INPUT_POST, 'prenomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $idPays = filter_input(INPUT_POST, 'idPays', FILTER_SANITIZE_SPECIAL_CHARS);
    $idGenre = filter_input(INPUT_POST, 'idGenre', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le nom de l'athlète est vide
    if (empty($nomAthlete)) {
        $_SESSION['error'] = "Le nom de l'athlète ne peut pas être vide.";
        header("Location: modify-athlete.php?id_athlete=$id_athlete");
        exit();
    }
    // Vérifiez si le prénom de l'athlète est vide
    if (empty($prenomAthlete)) {
        $_SESSION['error'] = "Le prénom de l'athlète ne peut pas être vide.";
        header("Location: modify-athlete.php?id_athlete=$id_athlete");
        exit();
    }
    // Vérifiez si le pays de l'athlète est vide
    if (empty($idPays)) {
        $_SESSION['error'] = "Le pays de l'athlète ne peut pas être vide.";
        header("Location: modify-athlete.php?id_athlete=$id_athlete");
        exit();
    }
    // Vérifiez si le genre de l'athlète est vide
    if (empty($idGenre)) {
        $_SESSION['error'] = "Le genre de l'athlète ne peut pas être vide.";
        header("Location: modify-athlete.php?id_athlete=$id_athlete");
        exit();
    }

    try {
        // Vérifiez si l'athlète existe déjà
        $queryCheck = "SELECT id_athlete FROM ATHLETE WHERE nom_athlete = :nomAthlete AND id_athlete <> :idAthlete";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'athlète existe déjà.";
            header("Location: modify-athlete.php?id_athlete=$id_athlete");
            exit();
        }
        // Requête pour mettre à jour l'athlète
        $query = "UPDATE ATHLETE SET nom_athlete = :nomAthlete, prenom_athlete = :prenomAthlete, id_pays = :idPays, id_genre = :idGenre WHERE id_athlete = :idAthlete";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
        $statement->bindParam(":prenomAthlete", $prenomAthlete, PDO::PARAM_STR);
        $statement->bindParam(":idPays", $idPays, PDO::PARAM_STR);
        $statement->bindParam(":idGenre", $idGenre, PDO::PARAM_STR);
        $statement->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'athlète a été modifié avec succès.";
            header("Location: manage-athletes.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'athlète.";
            header("Location: modify-athlete.php?id_athlete=$id_athlete");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-athlete.php?id_athlete=$id_athlete");
        exit();
    }
}
// Récupération de la liste des pays pour le menu déroulant
$query_payss = "SELECT id_pays, nom_pays FROM PAYS";
$statement_payss = $connexion->prepare($query_payss);
$statement_payss->execute();
$payss = $statement_payss->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des genre pour le menu déroulant
$query_genres = "SELECT id_genre, nom_genre FROM GENRE";
$statement_genres = $connexion->prepare($query_genres);
$statement_genres->execute();
$genres = $statement_genres->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Modifier un Athlète - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
        form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
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
        <h1>Modifier un Athlète</h1>
        
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

        <form action="modify-athlete.php?id_athlete=<?php echo $id_athlete; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cette athlète ?')">
            <label for="nomAthlete">Nom de l'Athlète :</label>
            <input type="text" name="nomAthlete" id="nomAthlete"
                value="<?php echo htmlspecialchars($athlete['nom_athlete']); ?>" required>
            <label for="prenomAthlete">Prénom de l'Athlète :</label>
            <input type="text" name="prenomAthlete" id="prenomAthlete"
                value="<?php echo htmlspecialchars($athlete['prenom_athlete']); ?>" required>
            <label for="idPays">Pays :</label>
            <select name="idPays" id="idPays" required>
            <?php foreach ($payss as $pays): ?>
            <option value="<?= $pays['id_pays'] ?>" <?php echo ($pays['id_pays'] == $athlete['id_pays']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($pays['nom_pays']) ?>
            </option>
            <?php endforeach; ?>
            </select>
            <label for="idGenre">Genre :</label>
            <select name="idGenre" id="idGenre" required>
            <?php foreach ($genres as $genre): ?>
            <option value="<?= $genre['id_genre'] ?>" <?php echo ($genre['id_genre'] == $athlete['id_genre']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($genre['nom_genre']) ?>
            </option>
            <?php endforeach; ?>
            </select>
            <input type="submit" value="Modifier l'Athlète">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-athletes.php">Retour à la gestion des athlètes</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
