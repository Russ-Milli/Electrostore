<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'electrostore';
    private $username = 'root';
    private $password = '';

    
    private $conn;

    

    public function connect() {
        $this->conn = null;


        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }

        return $this->conn;
    }

    
}
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'ryanthuku64@gmail.com';
$smtp_password = 'oxyw ugwr xabn skln';
$from_email = 'ryanthuku64@gmail.com';
$from_name = 'ElectroStore';


 







?>