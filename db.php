<?php
// db.php : Connexion sécurisée à la base de données MySQL sous XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'facturation_reseau'); // Le nom exact de votre base
define('DB_USER', 'root');              // Identifiant XAMPP par défaut
define('DB_PASS', '');                  // Mot de passe vide par défaut sous XAMPP
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        // Active les exceptions en cas d'erreur SQL (très utile pour déboguer)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Retourne automatiquement les résultats sous forme de tableaux associatifs
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Utilise les vraies requêtes préparées pour maximiser la sécurité contre les injections SQL
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Création de l'instance PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Message clair en cas d'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?> 