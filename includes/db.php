


<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;
    private $stmt;
    private $error;
    
    public function __construct() {
        // Configuration DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Création d'une instance PDO
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Erreur de connexion: ' . $this->error;
        }
    }
    
    // Préparation des requêtes
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }
    
    // Binding des valeurs
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    // Exécution de la requête
    public function execute() {
        return $this->stmt->execute();
    }
    
    // Récupérer tous les enregistrements
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    // Récupérer un seul enregistrement
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    // Récupérer le nombre de lignes
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    // Récupérer le dernier ID inséré
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
    
    // Transactions
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    public function endTransaction() {
        return $this->dbh->commit();
    }
    
    public function cancelTransaction() {
        return $this->dbh->rollBack();
    }
}

