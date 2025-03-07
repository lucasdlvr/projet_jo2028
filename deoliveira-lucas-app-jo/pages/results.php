<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/normalize.css">
    <link rel="stylesheet" href="../css/styles-computer.css">
    <link rel="stylesheet" href="../css/styles-responsive.css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <title>Liste des Sports - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="sports.php">Sports</a></li>
                <li><a href="events.php">Calendrier des épreuves</a></li>
                <li><a href="results.php">Résultats</a></li>
                <li><a href="login.php">Accès administrateur</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Liste des Resultats</h1>

        <?php
        require_once("../database/database.php");

        try {
            // Requête pour récupérer la liste des sports depuis la base de données
            $query = "SELECT a.nom_athlete, a.prenom_athlete, e.nom_epreuve, p.resultat, g.nom_genre, pa.nom_pays, pa.id_pays
            FROM PARTICIPER p 
            INNER JOIN EPREUVE e ON p.id_epreuve = e.id_epreuve
            INNER JOIN ATHLETE a ON p.id_athlete = a.id_athlete
            INNER JOIN PAYS pa ON a.id_pays = pa.id_pays
            INNER JOIN GENRE g ON a.id_genre = g.id_genre
            ORDER BY nom_athlete";
            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table>";
                echo "<tr><th class='color'>Classement</th><th>Pays</th><th>Athlete</th><th>Genre</th><th>Epreuve</th><th>Resultat</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id_pays'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_pays'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_athlete'] .' '. $row['prenom_athlete'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_genre'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_epreuve'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['resultat'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun sport trouvé.</p>";
            }
        } catch (PDOException $e) {
            // Gestion d'erreur améliorée
            echo "<p style='color: red;'>Erreur : Impossible de récupérer les sports. Veuillez réessayer plus tard.</p>";
            error_log("Erreur PDO : " . $e->getMessage()); // Log de l'erreur dans un fichier serveur pour débogage
        }

        // Définir le niveau d'affichage des erreurs (utile en phase de développement)
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        ?>
        
        <p class="paragraph-link">
            <a class="link-home" href="../index.php">Retour Accueil</a>
        </p>

    </main>
    <footer>
        <figure>
            <img src="../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>