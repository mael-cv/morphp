<?php
// Connexion à la base de données MySQL
function getDBConnection() {
    $host = 'localhost';  // Changez en fonction de votre environnement
    $db   = 'morpion_db';
    $user = 'root';       // Changez avec votre utilisateur MySQL
    $pass = '';   // Changez avec votre mot de passe MySQL
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>
