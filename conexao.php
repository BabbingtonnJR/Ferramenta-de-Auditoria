<?php


function conecta_db(){
    $host = "localhost:3306";
    $user = "root"; 
    $pass = "";
    $db   = "ChecklistAuditoria";
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    return $conn;
}


?>