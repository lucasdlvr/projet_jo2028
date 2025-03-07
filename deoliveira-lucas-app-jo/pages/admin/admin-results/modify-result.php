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

// Vérifiez si l'ID de l'athlète et de l'épreuve sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve manquant.";
    header("Location: manage-results.php");
    exit();
} else {
    $id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

    if ($id_athlete === false || $id_epreuve === false) {
        $_SESSION['error'] = "ID invalide.";
        header("Location: manage-results.php");
        exit();
    }

    // Récupérer les informations actuelles du résultat
    try {
        $query = "SELECT p.resultat, a.id_pays, a.id_genre, e.id_epreuve, a.nom_athlete, a.prenom_athlete, e.nom_epreuve, pa.nom_pays, g.nom_genre
                  FROM PARTICIPER p
                  INNER JOIN ATHLETE a ON p.id_athlete = a.id_athlete
                  INNER JOIN EPREUVE e ON p.id_epreuve = e.id_epreuve
                  INNER JOIN PAYS pa ON a.id_pays = pa.id_pays
                  INNER JOIN GENRE g ON a.id_genre = g.id_genre
                  WHERE p.id_athlete = :id_athlete AND p.id_epreuve = :id_epreuve";
        $statement = $connexion->prepare($query);
        $statement->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
        $statement->bindParam(':id_epreuve', $id_epreuve, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $_SESSION['error'] = "Aucun résultat trouvé pour cet athlète et cette épreuve.";
            header('Location: manage-results.php');
            exit();
        }

        // Récupérer les données des pays, athlètes, genres et épreuves pour les menus déroulants
        $query_pays = "SELECT id_pays, nom_pays FROM PAYS";
        $statement_pays = $connexion->prepare($query_pays);
        $statement_pays->execute();
        $payss = $statement_pays->fetchAll(PDO::FETCH_ASSOC);

        $query_athletes = "SELECT id_athlete, nom_athlete, prenom_athlete FROM ATHLETE";
        $statement_athletes = $connexion->prepare($query_athletes);
        $statement_athletes->execute();
        $athletes = $statement_athletes->fetchAll(PDO::FETCH_ASSOC);

        $query_genres = "SELECT id_genre, nom_genre FROM GENRE";
        $statement_genres = $connexion->prepare($query_genres);
        $statement_genres->execute();
        $genres = $statement_genres->fetchAll(PDO::FETCH_ASSOC);

        $query_epreuves = "SELECT id_epreuve, nom_epreuve FROM EPREUVE";
        $statement_epreuves = $connexion->prepare($query_epreuves);
        $statement_epreuves->execute();
        $epreuves = $statement_epreuves->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: manage-results.php");
        exit();
    }
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idPays = filter_input(INPUT_POST, 'idPays', FILTER_VALIDATE_INT);
    $idAthlete = filter_input(INPUT_POST, 'idAthlete', FILTER_VALIDATE_INT);
    $idGenre = filter_input(INPUT_POST, 'idGenre', FILTER_VALIDATE_INT);
    $idEpreuve = filter_input(INPUT_POST, 'idEpreuve', FILTER_VALIDATE_INT);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Mise à jour du résultat dans la table PARTICIPER
    try {
        // Mettre à jour la table PARTICIPER pour le résultat et l'épreuve
        $updateParticiperQuery = "UPDATE PARTICIPER 
                                  SET id_epreuve = :idEpreuve, resultat = :resultat 
                                  WHERE id_athlete = :idAthlete AND id_epreuve = :idEpreuve";
        $statementUpdateParticiper = $connexion->prepare($updateParticiperQuery);
        $statementUpdateParticiper->bindParam(':idEpreuve', $idEpreuve, PDO::PARAM_INT);
        $statementUpdateParticiper->bindParam(':resultat', $resultat, PDO::PARAM_STR);
        $statementUpdateParticiper->bindParam(':idAthlete', $idAthlete, PDO::PARAM_INT);
        $statementUpdateParticiper->execute();

        // Mettre à jour la table ATHLETE pour le pays et le genre
        $updateAthleteQuery = "UPDATE ATHLETE 
                               SET id_pays = :idPays, id_genre = :idGenre 
                               WHERE id_athlete = :idAthlete";
        $statementUpdateAthlete = $connexion->prepare($updateAthleteQuery);
        $statementUpdateAthlete->bindParam(':idPays', $idPays, PDO::PARAM_INT);
        $statementUpdateAthlete->bindParam(':idGenre', $idGenre, PDO::PARAM_INT);
        $statementUpdateAthlete->bindParam(':idAthlete', $idAthlete, PDO::PARAM_INT);
        $statementUpdateAthlete->execute();

        $_SESSION['success'] = "Le résultat a été modifié avec succès.";
        header('Location: manage-results.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour du résultat : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header('Location: modify-result.php?id_athlete=' . $id_athlete . '&id_epreuve=' . $id_epreuve);
        exit();
    }
}

// Afficher les erreurs en PHP
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
    <title>Modifier un Résultat - Jeux Olympiques - Los Angeles 2028</title>
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
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Résultat</h1>
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

        <form action="modify-result.php?id_athlete=<?= $id_athlete ?>&id_epreuve=<?= $id_epreuve ?>" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce résultat ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="idPays">Pays :</label>
            <select name="idPays" id="idPays" required>
                <option value="">Sélectionner un pays</option>
                <?php foreach ($payss as $pays): ?>
                    <option value="<?= $pays['id_pays'] ?>" <?= $pays['id_pays'] == $result['id_pays'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pays['nom_pays']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="idAthlete">Athlète :</label>
            <select name="idAthlete" id="idAthlete" required>
                <option value="">Sélectionner un athlète</option>
                <?php foreach ($athletes as $athlete): ?>
                    <option value="<?= $athlete['id_athlete'] ?>" <?= $athlete['id_athlete'] == $id_athlete ? 'selected' : '' ?>>
                        <?= htmlspecialchars($athlete['nom_athlete'] . ' ' . $athlete['prenom_athlete']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="idGenre">Genre :</label>
            <select name="idGenre" id="idGenre" required>
                <option value="">Sélectionner un genre</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?= $genre['id_genre'] ?>" <?= $genre['id_genre'] == $result['id_genre'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($genre['nom_genre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="idEpreuve">Épreuve :</label>
            <select name="idEpreuve" id="idEpreuve" required>
                <option value="">Sélectionner une épreuve</option>
                <?php foreach ($epreuves as $epreuve): ?>
                    <option value="<?= $epreuve['id_epreuve'] ?>" <?= $epreuve['id_epreuve'] == $result['id_epreuve'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($epreuve['nom_epreuve']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" value="<?= htmlspecialchars($result['resultat']) ?>" required>

            <input type="submit" value="Modifier le résultat">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
