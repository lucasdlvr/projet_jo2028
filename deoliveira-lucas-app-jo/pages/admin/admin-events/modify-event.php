<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'épreuve est fourni dans l'URL
if (!isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'épreuve manquant.";
    header("Location: manage-events.php");
    exit();
}

$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'épreuve est un entier valide
if (!$id_epreuve && $id_epreuve !== 0) {
    $_SESSION['error'] = "ID de l'épreuve invalide.";
    header("Location: manage-events.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'épreuve pour affichage dans le formulaire
try {
    $queryEvent = "SELECT nom_epreuve, date_epreuve, heure_epreuve, id_lieu, id_sport FROM EPREUVE WHERE id_epreuve = :idEvent";
    $statementEvent = $connexion->prepare($queryEvent);
    $statementEvent->bindParam(":idEvent", $id_epreuve, PDO::PARAM_INT);
    $statementEvent->execute();

    if ($statementEvent->rowCount() > 0) {
        $event = $statementEvent->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Epreuve non trouvé.";
        header("Location: manage-events.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-events.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomEvent = filter_input(INPUT_POST, 'nomEvent', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEvent = filter_input(INPUT_POST, 'dateEvent', FILTER_SANITIZE_SPECIAL_CHARS);
    $heureEvent = filter_input(INPUT_POST, 'heureEvent', FILTER_SANITIZE_SPECIAL_CHARS);
    $idLieu = filter_input(INPUT_POST, 'idLieu', FILTER_SANITIZE_SPECIAL_CHARS);
    $idSport = filter_input(INPUT_POST, 'idSport', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le nom de l'épreuve est vide
    if (empty($nomEvent)) {
        $_SESSION['error'] = "Le nom de l'épreuve ne peut pas être vide.";
        header("Location: modify-event.php?id_epreuve=$id_epreuve");
        exit();
    }
    // Vérifiez si la date de l'épreuve est vide
    if (empty($dateEvent)) {
        $_SESSION['error'] = "La date de l'épreuve ne peut pas être vide.";
        header("Location: modify-event.php?id_epreuve=$id_epreuve");
        exit();
    }
    // Vérifiez si l'heure de l'épreuve est vide
    if (empty($heureEvent)) {
        $_SESSION['error'] = "L'heure de l'épreuve ne peut pas être vide.";
        header("Location: modify-event.php?id_epreuve=$id_epreuve");
        exit();
    }
    // Vérifiez si le lieu de l'épreuve est vide
    if (empty($idLieu)) {
        $_SESSION['error'] = "Le lieu de l'épreuve ne peut pas être vide.";
        header("Location: modify-event.php?id_epreuve=$id_epreuve");
        exit();
    }
    // Vérifiez si le sport de l'épreuve est vide
    if (empty($idSport)) {
        $_SESSION['error'] = "Le sport de l'épreuve ne peut pas être vide.";
        header("Location: modify-event.php?id_epreuve=$id_epreuve");
        exit();
    }

    try {
        // Vérifiez si l'épreuve existe déjà
        $queryCheck = "SELECT id_epreuve FROM EPREUVE WHERE nom_epreuve = :nomEvent AND id_epreuve <> :idEvent";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomEvent", $nomEvent, PDO::PARAM_STR);
        $statementCheck->bindParam(":idEvent", $id_epreuve, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'épreuve existe déjà.";
            header("Location: modify-event.php?id_epreuve=$id_epreuve");
            exit();
        }
        // Requête pour mettre à jour l'épreuve
        $query = "UPDATE EPREUVE SET nom_epreuve = :nomEvent, date_epreuve = :dateEvent, heure_epreuve = :heureEvent, id_lieu = :idLieu, id_sport = :idSport WHERE id_epreuve = :idEvent";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomEvent", $nomEvent, PDO::PARAM_STR);
        $statement->bindParam(":dateEvent", $dateEvent, PDO::PARAM_STR);
        $statement->bindParam(":heureEvent", $heureEvent, PDO::PARAM_STR);
        $statement->bindParam(":idLieu", $idLieu, PDO::PARAM_STR);
        $statement->bindParam(":idSport", $idSport, PDO::PARAM_STR);
        $statement->bindParam(":idEvent", $id_epreuve, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'épreuve a été modifié avec succès.";
            header("Location: manage-events.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'épreuve.";
            header("Location: modify-event.php?id_epreuve=$id_epreuve");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-event.php?id_epreuve=$id_epreuve");
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
    <title>Modifier une Epreuve - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier une Epreuve</h1>
        
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

        <form action="modify-event.php?id_epreuve=<?php echo $id_epreuve; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cette épreuve ?')">
            <label for="nomEvent">Nom de l'Epreuve :</label>
            <input type="text" name="nomEvent" id="nomEvent"
                value="<?php echo htmlspecialchars($event['nom_epreuve']); ?>" required>
            <label for="dateEvent">Date :</label>
            <input type="date" name="dateEvent" id="dateEvent"
                value="<?php echo htmlspecialchars($event['date_epreuve']); ?>" required>
            <label for="heureEvent">Heure :</label>
            <input type="time" name="heureEvent" id="heureEvent"
                value="<?php echo htmlspecialchars($event['heure_epreuve']); ?>" required>
            <label for="idLieu">Lieu :</label>
            <select name="idLieu" id="idLieu" required>
            <?php foreach ($lieux as $lieu): ?>
            <option value="<?= $lieu['id_lieu'] ?>" <?php echo ($lieu['id_lieu'] == $event['id_lieu']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($lieu['nom_lieu']) ?>
            </option>
            <?php endforeach; ?>
            </select>
            <label for="idSport">Sport :</label>
            <select name="idSport" id="idSport" required>
            <?php foreach ($sports as $sport): ?>
            <option value="<?= $sport['id_sport'] ?>" <?php echo ($sport['id_sport'] == $event['id_sport']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($sport['nom_sport']) ?>
            </option>
            <?php endforeach; ?>
            </select>
            <input type="submit" value="Modifier l'Epreuve">
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
