<?php
require 'conexao.php';
$conn = conecta_db();


$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

if ($id_checklist > 0) {
    $sql_relacao = "DELETE FROM Item_checklist WHERE id_checklist = ?";
    $stmt_relacao = $conn->prepare($sql_relacao);
    $stmt_relacao->bind_param("i", $id_checklist);

    if ($stmt_relacao->execute()) {
        $sql_item = "DELETE FROM Checklist WHERE id = ?";
        $stmt_item = $conn->prepare($sql_item);
        $stmt_item->bind_param("i", $id_checklist);

        if ($stmt_item->execute()) {
            $msg = "✅ Checklist excluído com sucesso!";
        } else {
            $msg = "❌ Erro ao excluir o checklist: " . $conn->error;
        }

        $stmt_item->close();
    } else {
        $msg = "❌ Erro ao desvincular o checklist: " . $conn->error;
    }

    $stmt_relacao->close();
} else {
    $msg = "❌ ID do checklist não fornecido.";
}

$conn->close();

header("Location: lista_checklist.php?msg=" . urlencode($msg));
exit();
?>