<?php
require 'conexao.php';
$conn = conecta_db();

$sql = "
    SELECT 
        i.id AS id_item,
        i.numero_item,
        i.descricao AS descricao_item,
        i.conformidade,
        c.id AS id_checklist,
        c.nome AS nome_checklist,
        c.data_criacao
    FROM Item i
    INNER JOIN Item_checklist ic ON i.id = ic.id_item
    INNER JOIN Checklist c ON ic.id_checklist = c.id
    WHERE i.conformidade = 'Nao Aplicavel'
    ORDER BY c.data_criacao DESC, i.numero_item ASC
";
$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Itens Não Aplicáveis</title>
<style>
    body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; padding:20px; }
    .container { max-width:1000px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.1); }
    h1 { text-align:center; color:#004080; }
    table { width:100%; border-collapse: collapse; margin-top:20px; }
    th, td { border:1px solid #ccc; padding:10px; text-align:center; }
    th { background:#e0e0e0; }
    a.btn { background:#004080; color:#fff; padding:6px 12px; border-radius:6px; text-decoration:none; }
    a.btn:hover { background:#0066cc; }
</style>
</head>
<body>
<div class="container">
    <h1>Itens Marcados como Não Aplicáveis</h1>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID Item</th>
                <th>Nº Item</th>
                <th>Descrição</th>
                <th>Checklist</th>
                <th>Data Criação</th>
                <th>Ação</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id_item'] ?></td>
                    <td><?= $row['numero_item'] ?></td>
                    <td><?= htmlspecialchars($row['descricao_item']) ?></td>
                    <td><?= htmlspecialchars($row['nome_checklist']) ?></td>
                    <td><?= $row['data_criacao'] ?></td>
                    <td>
                        <a href="enviar_email.php?id_item=<?= $row['id_item'] ?>&id_checklist=<?= $row['id_checklist'] ?>" class="btn">Enviar Email</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Nenhum item marcado como Não Aplicável.</p>
    <?php endif; ?>
    <p style="text-align:center; margin-top:20px;">
        <a href="index.php" class="btn">⬅ Voltar ao Menu</a>
    </p>
</div>
</body>
</html>
