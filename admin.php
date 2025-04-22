<?php
// Données à modifier
$new_username = "admin"; // Remplacez par le nom d'utilisateur souhaité
$new_password = "admin123"; // Remplacez par le mot de passe souhaité

// Connexion à la base de données
require_once 'includes/config.php';
require_once 'includes/db.php';

// Hacher le nouveau mot de passe
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Mettre à jour l'utilisateur admin
$db = new Database();
$db->query("UPDATE users SET username = :username, password = :password WHERE id = 1"); // Supposant que l'admin a l'ID 1
$db->bind(':username', $new_username);
$db->bind(':password', $hashed_password);

if($db->execute()) {
    echo "Les informations d'identification de l'administrateur ont été mises à jour avec succès !<br>";
    echo "Nouveau nom d'utilisateur : " . $new_username . "<br>";
    echo "Nouveau mot de passe : " . $new_password . "<br>";
    echo "Vous pouvez maintenant vous <a href='login.php'>connecter</a>.";
} else {
    echo "Une erreur s'est produite lors de la mise à jour des informations d'identification.";
}

// Supprimer ce fichier après utilisation (pour des raisons de sécurité)
// unlink(__FILE__);
?>