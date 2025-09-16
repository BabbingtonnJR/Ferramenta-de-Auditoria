<?php


function conecta_db(){
    $host = "localhost:3307";
    $user = "root"; // coloque seu usuário do MySQL
    $pass = "";     // coloque sua senha do MySQL
    $db   = "ChecklistAuditoria";
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    return $conn;
}


?>