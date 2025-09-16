<?php
require 'conexao.php';
$conn = conecta_db();

$id_item = isset($_GET['id_item']) ? intval($_GET['id_item']) : 0;
$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

if ($id_item > 0 && $id_checklist > 0) {
    $sql_relacao = "DELETE FROM Item_checklist WHERE id_item = ? AND id_checklist = ?";
    $stmt_relacao = $conn->prepare($sql_relacao);
    $stmt_relacao->bind_param("ii", $id_item, $id_checklist);

    if ($stmt_relacao->execute()) {
        $sql_item = "DELETE FROM Item WHERE id = ?";
        $stmt_item = $conn->prepare($sql_item);
        $stmt_item->bind_param("i", $id_item);

        if ($stmt_item->execute()) {
            $msg = "✅ Item excluído com sucesso!";
        } else {
            $msg = "❌ Erro ao excluir o item: " . $conn->error;
        }

        $stmt_item->close();
    } else {
        $msg = "❌ Erro ao desvincular o item do checklist: " . $conn->error;
    }

    $stmt_relacao->close();
} else {
    $msg = "❌ ID do item ou do checklist não fornecido.";
}

$conn->close();

header("Location: itens.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
exit();
?>