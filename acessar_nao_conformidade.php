<?php
require 'conexao.php';
$conn = conecta_db();

$msg = "";
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// SQL para buscar todas as não conformidades com detalhes do item e checklist
$sql = "
    SELECT 
        nc.id AS id_nao_conformidade,
        nc.descricao AS descricao_nao_conformidade,
        nc.estado,
        nc.prioridade,
        nc.data_criacao,
        i.descricao AS descricao_item,
        c.nome AS nome_checklist
    FROM 
        naoConformidade nc
    INNER JOIN 
        Item i ON nc.id_item = i.id
    INNER JOIN 
        Item_checklist ic ON i.id = ic.id_item
    INNER JOIN 
        Checklist c ON ic.id_checklist = c.id
    ORDER BY 
        nc.data_criacao DESC";

$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Não Conformidades</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #004080; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #e0e0e0; }
        .msg { text-align: center; font-weight: bold; margin: 10px 0; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #004080; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Não Conformidades Registradas</h1>

        <?php if (!empty($msg)): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID da NC</th>
                    <th>Checklist</th>
                    <th>Item</th>
                    <th>Descrição</th>
                    <th>Estado</th>
                    <th>Prioridade</th>
                    <th>Data de Criação</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id_nao_conformidade'] ?></td>
                        <td><?= htmlspecialchars($row['nome_checklist']) ?></td>
                        <td><?= htmlspecialchars($row['descricao_item']) ?></td>
                        <td><?= htmlspecialchars($row['descricao_nao_conformidade']) ?></td>
                        <td><?= htmlspecialchars($row['estado']) ?></td>
                        <td><?= htmlspecialchars($row['prioridade']) ?></td>
                        <td><?= $row['data_criacao'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="text-align: center;">Nenhuma não conformidade encontrada.</p>
        <?php endif; ?>

        <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
    </div>
</body>
</html>