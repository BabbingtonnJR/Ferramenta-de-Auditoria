<?php
require 'conexao.php';
$conn = conecta_db();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_item = intval($_POST['id_item']);
    $id_checklist = intval($_POST['id_checklist']);
    $descricao_nc = $_POST['descricao_nc'];
    $estado_nc = $_POST['estado_nc'];
    $prioridade_nc = $_POST['prioridade_nc'];
    
    // Insere os dados na tabela naoConformidade
    $sql_insert = "INSERT INTO naoConformidade (id_item, descricao, estado, prioridade) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isss", $id_item, $descricao_nc, $estado_nc, $prioridade_nc);

    $msg = "";
    if ($stmt_insert->execute()) {
        $msg = "✅ Não conformidade registrada com sucesso!";
    } else {
        $msg = "❌ Erro ao registrar não conformidade: " . $conn->error;
    }
    
    $stmt_insert->close();
    $conn->close();

    // Redireciona de volta para a página do checklist
    header("Location: itens.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
    exit();
}

// Se não for uma requisição POST, redirecione com uma mensagem de erro
header("Location: index.php?msg=" . urlencode("❌ Requisição inválida."));
exit();
?>