<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;

$sql_checklist = "SELECT id, nome, descricao, data_criacao FROM Checklist WHERE id = ?";
$stmt = $conn->prepare($sql_checklist);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();

if (!$checklist) {
    die("❌ Checklist não encontrada.");
}

$sql_itens = "
    SELECT i.numero_item, i.descricao 
    FROM Item i
    INNER JOIN Item_checklist ic ON i.id = ic.id_item
    WHERE ic.id_checklist = ?
    ORDER BY i.numero_item ASC";
$stmt = $conn->prepare($sql_itens);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$dados = [
    "checklist" => [
        "nome" => $checklist['nome'],
        "descricao" => $checklist['descricao'],
        "data_criacao" => $checklist['data_criacao']
    ],
    "itens" => $itens
];

$json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="checklist_'.$id_checklist.'.json"');
echo $json;
exit;
