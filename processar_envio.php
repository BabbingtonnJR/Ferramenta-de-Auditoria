<?php
require 'conexao.php';
$conn = conecta_db();

$id_item = intval($_POST['id_item']);
$id_checklist = intval($_POST['id_checklist']);
$destinatario = $_POST['email_destinatario'];
$remetente = $_POST['email_remetente'];
$descricao = nl2br(htmlspecialchars($_POST['descricao']));

// Buscar nome do checklist e item
$sql = "
    SELECT i.numero_item, i.descricao AS descricao_item, c.nome AS nome_checklist
    FROM Item i
    INNER JOIN Item_checklist ic ON i.id = ic.id_item
    INNER JOIN Checklist c ON ic.id_checklist = c.id
    WHERE i.id = ? AND c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_item, $id_checklist);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    die("❌ Item não encontrado.");
}

$assunto = "Auditoria - Item Não Aplicável";
$mensagem = "
<html>
<body>
<p>Prezado(a),</p>
<p>Durante a auditoria foi identificado que o seguinte item foi marcado como <b>NÃO APLICÁVEL</b>:</p>
<ul>
    <li><b>Checklist:</b> {$item['nome_checklist']}</li>
    <li><b>Item:</b> Nº {$item['numero_item']} - {$item['descricao_item']}</li>
</ul>
<p><b>Descrição/Justificativa informada pelo auditor:</b></p>
<p>$descricao</p>
<p>Atenciosamente,<br>Equipe de Auditoria</p>
</body>
</html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: $remetente";

// Enviar email
if (mail($destinatario, $assunto, $mensagem, $headers)) {
    $sql_email = "INSERT INTO Email (id_nc, email_destinatario, email_remetente) VALUES (NULL, ?, ?)";
    $stmt = $conn->prepare($sql_email);
    $stmt->bind_param("ss", $destinatario, $remetente);
    $stmt->execute();
    $stmt->close();

    header("Location: nao_aplicavel.php?msg=" . urlencode("✅ Email enviado com sucesso!"));
} else {
    header("Location: nao_aplicavel.php?msg=" . urlencode("❌ Erro ao enviar email."));
}

$conn->close();
