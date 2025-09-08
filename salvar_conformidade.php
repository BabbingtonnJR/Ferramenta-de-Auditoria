<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['conformidade'])) {
    foreach ($_POST['conformidade'] as $id_item => $status) {
        $sql_update = "UPDATE Item SET conformidade = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $status, $id_item);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $msg = "✅ Status de conformidade atualizados com sucesso!";
}

$conn->close();

header("Location: acessar_checklist.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
exit();
?>