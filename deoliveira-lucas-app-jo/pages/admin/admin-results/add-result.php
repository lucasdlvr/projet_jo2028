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
    $idAthlete = filter_input(INPUT_POST, 'idAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $idEpreuve = filter_input(INPUT_POST, 'idEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-result.php");
        exit();
    }

    // Vérifiez si le nom de l'athlète est vide
    if (empty($idAthlete)) {
        $_SESSION['error'] = "Le nom de l'athlète ne peut pas être vide.";
        header("Location: add-result.php");
        exit();
    }

    try {
        // Vérifiez si l'athlète existe déjà
        $queryCheck = "SELECT id_athlete FROM ATHLETE WHERE nom_athlete = :idAthlete";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":idAthlete", $idAthlete, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le résultat existe déjà.";
            header("Location: add-result.php");
            exit();
        } else {
            // Requête pour ajouter un résultat
            $query = "INSERT INTO PARTICIPER (id_athlete, id_epreuve, resultat) VALUES (:idAthlete, :idEpreuve, :resultat)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":idAthlete", $idAthlete, PDO::PARAM_STR);
            $statement->bindParam(":idEpreuve", $idEpreuve, PDO::PARAM_STR);
            $statement->bindParam(":resultat", $resultat, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "Le résultat a été ajouté avec succès.";
                header("Location: manage-results.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout du résultat.";
                header("Location: add-result.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-result.php");
        exit();
    }
}

// Récupération de la liste des pays pour le menu déroulant
$query_payss = "SELECT id_pays, nom_pays FROM PAYS";
$statement_payss = $connexion->prepare($query_payss);
$statement_payss->execute();
$payss = $statement_payss->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des athlètes pour le menu déroulant
$query_athletes = "SELECT id_athlete, nom_athlete, prenom_athlete FROM ATHLETE";
$statement_athletes = $connexion->prepare($query_athletes);
$statement_athletes->execute();
$athletes = $statement_athletes->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des genres pour le menu déroulant
$query_genres = "SELECT id_genre, nom_genre FROM GENRE";
$statement_genres = $connexion->prepare($query_genres);
$statement_genres->execute();
$genres = $statement_genres->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des épreuves pour le menu déroulant
$query_epreuves = "SELECT id_epreuve, nom_epreuve FROM EPREUVE";
$statement_epreuves = $connexion->prepare($query_epreuves);
$statement_epreuves->execute();
$epreuves = $statement_epreuves->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Ajouter un Résultat - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Ajouter un Résultat</h1>
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
        <form action="add-result.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter ce résultat ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="idPays">Pays :</label>
            <select name="idPays" id="idPays" required>
            <option value="">Sélectionner un pays</option>
            <?php foreach ($payss as $pays): ?>
                    <option value="<?= $pays['id_pays'] ?>"><?= htmlspecialchars($pays['nom_pays']) ?></option>
            <?php endforeach; ?>
            </select>
            <label for="idAthlete">Athlète :</label>
            <select name="idAthlete" id="idAthlete" required>
            <option value="">Sélectionner un athlète</option>
            <?php foreach ($athletes as $athlete): ?>
                    <option value="<?= $athlete['id_athlete'] ?>"><?= htmlspecialchars($athlete['nom_athlete'] . ' ' . $athlete['prenom_athlete']) ?></option>
            <?php endforeach; ?>
            </select>
            <label for="idGenre">Genre :</label>
            <select name="idGenre" id="idGenre" required>
            <option value="">Sélectionner un genre</option>
            <?php foreach ($genres as $genre): ?>
                    <option value="<?= $genre['id_genre'] ?>"><?= htmlspecialchars($genre['nom_genre']) ?></option>
            <?php endforeach; ?>
            </select>
            <label for="idEpreuve">Epreuve :</label>
            <select name="idEpreuve" id="idEpreuve" required>
            <option value="">Sélectionner une épreuve</option>
            <?php foreach ($epreuves as $epreuve): ?>
                    <option value="<?= $epreuve['id_epreuve'] ?>"><?= htmlspecialchars($epreuve['nom_epreuve']) ?></option>
            <?php endforeach; ?>
            </select>
            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" required>
            <input type="submit" value="Ajouter le résultat">
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
