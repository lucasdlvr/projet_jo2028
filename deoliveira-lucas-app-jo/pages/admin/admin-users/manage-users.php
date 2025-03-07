<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

$login = $_SESSION['login'];
$nom_utilisateur = $_SESSION['prenom_utilisateur'];
$prenom_utilisateur = $_SESSION['nom_utilisateur'];

// Fonction pour vérifier le token CSRF
function checkCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Token CSRF invalide.');
        }
    }
}

// Générer un token CSRF si ce n'est pas déjà fait
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF
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
    <title>Liste des Utilisateurs - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
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
        <h1>Liste des Utilisateurs</h1>
        <div class="action-buttons">
            <button onclick="openAddUserForm()">Ajouter un Utilisateur</button>
        </div>
        
        <!-- Tableau des utilisateurs -->
        <?php
        require_once("../../../database/database.php");

        try {
            // Requête pour récupérer la liste des utilisateurs depuis la base de données
            $query = "SELECT id_utilisateur, nom_utilisateur, prenom_utilisateur, login FROM UTILISATEUR ORDER BY id_utilisateur";
            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table><tr><th>N°</th><th>Utilisateur</th><th>Login</th><th>Modifier</th><th>Supprimer</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id_utilisateur'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_utilisateur'] .' '. $row['prenom_utilisateur'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . $row['login'] . "</td>";
                    echo "<td><button onclick='openModifyUserForm({$row['id_utilisateur']})'>Modifier</button></td>";
                    echo "<td><button onclick='deleteUserConfirmation({$row['id_utilisateur']})'>Supprimer</button></td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun utilisateur trouvé.</p>";
            }
        } catch (PDOException $e) {
            echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
        ?>
        
        <p class="paragraph-link">
            <a class="link-home" href="../admin.php">Accueil administration</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

    <script>
        function openAddUserForm() {
            window.location.href = 'add-user.php';
        }

        function openModifyUserForm(id_utilisateur) {
            window.location.href = 'modify-user.php?id_utilisateur=' + id_utilisateur;
        }

        function deleteUserConfirmation(id_utilisateur) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur?")) {
                window.location.href = 'delete-user.php?id_utilisateur=' + id_utilisateur;
            }
        }
    </script>
</body>

</html>