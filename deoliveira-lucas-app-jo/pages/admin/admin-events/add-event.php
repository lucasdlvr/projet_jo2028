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
    $nomEvent = filter_input(INPUT_POST, 'nomEvent', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEvent = filter_input(INPUT_POST, 'dateEvent', FILTER_SANITIZE_SPECIAL_CHARS);
    $heureEvent = filter_input(INPUT_POST, 'heureEvent', FILTER_SANITIZE_SPECIAL_CHARS);
    $idLieu = filter_input(INPUT_POST, 'idLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $idSport = filter_input(INPUT_POST, 'idSport', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-event.php");
        exit();
    }

    // Vérifiez si le nom de l'épreuve est vide
    if (empty($nomEvent)) {
        $_SESSION['error'] = "Le nom de l'épreuve ne peut pas être vide.";
        header("Location: add-event.php");
        exit();
    }

    try {
        // Vérifiez si l'épreuve existe déjà
        $queryCheck = "SELECT id_epreuve FROM EPREUVE WHERE nom_epreuve = :nomEvent";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomEvent", $nomEvent, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'épreuve existe déjà.";
            header("Location: add-event.php");
            exit();
        } else {
            // Requête pour ajouter une épreuve
            $query = "INSERT INTO EPREUVE (nom_epreuve, date_epreuve, heure_epreuve, id_lieu, id_sport) VALUES (:nomEvent, :dateEvent, :heureEvent, :idLieu, :idSport)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomEvent", $nomEvent, PDO::PARAM_STR);
            $statement->bindParam(":dateEvent", $dateEvent, PDO::PARAM_STR);
            $statement->bindParam(":heureEvent", $heureEvent, PDO::PARAM_STR);
            $statement->bindParam(":idLieu", $idLieu, PDO::PARAM_STR);
            $statement->bindParam(":idSport", $idSport, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'épreuve a été ajouté avec succès.";
                header("Location: manage-events.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'épreuve.";
                header("Location: add-event.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-event.php");
        exit();
    }
}

// Récupération de la liste des lieux pour le menu déroulant
$query_lieux = "SELECT id_lieu, nom_lieu FROM LIEU";
$statement_lieux = $connexion->prepare($query_lieux);
$statement_lieux->execute();
$lieux = $statement_lieux->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste des sports pour le menu déroulant
$query_sports = "SELECT id_sport, nom_sport FROM SPORT";
$statement_sports = $connexion->prepare($query_sports);
$statement_sports->execute();
$sports = $statement_sports->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Ajouter une Epreuve - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Ajouter une Epreuve</h1>
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
        <form action="add-event.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cette épreuve ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nomEvent">Nom de l'Epreuve :</label>
            <input type="text" name="nomEvent" id="nomEvent" required>
            <label for="dateEvent">Date :</label>
            <input type="date" name="dateEvent" id="dateEvent" required>
            <label for="heureEvent">Heure :</label>
            <input type="time" name="heureEvent" id="heureEvent" required>
            <label for="idLieu">Lieu :</label>
            <select name="idLieu" id="idLieu" required>
            <option value="">Sélectionner un lieu</option>
            <?php foreach ($lieux as $lieu): ?>
                    <option value="<?= $lieu['id_lieu'] ?>"><?= htmlspecialchars($lieu['nom_lieu']) ?></option>
            <?php endforeach; ?>
            </select>
            <label for="idSport">Sport :</label>
            <select name="idSport" id="idSport" required>
            <option value="">Sélectionner un sport</option>
            <?php foreach ($sports as $sport): ?>
                    <option value="<?= $sport['id_sport'] ?>"><?= htmlspecialchars($sport['nom_sport']) ?></option>
            <?php endforeach; ?>
            </select>
            <input type="submit" value="Ajouter l'épreuve">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion des épreuves</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>
