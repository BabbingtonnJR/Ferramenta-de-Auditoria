<?php
require 'conexao.php';
$conn = conecta_db();

// Buscar todas as checklists
$sql = "SELECT id, nome, descricao, data_criacao FROM Checklist ORDER BY data_criacao DESC";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Checklists</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; padding:0; }
        .container { max-width: 900px; margin: 40px auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        h1 { text-align:center; color:#004080; margin-top:0; }
        table { width:100%; border-collapse: collapse; margin-top:20px; }
        th, td { border:1px solid #ccc; padding:10px; text-align:center; }
        th { background:#e0e0e0; }
        a { color:#004080; text-decoration:none; }
        a:hover { text-decoration:underline; }
        .back-link { display:block; text-align:center; margin-top:20px; color:#004080; text-decoration:none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Checklists Criadas</h1>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Data de Criação</th>
                    <th>Ações</th>
                </tr>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nome']) ?></td>
                        <td><?= htmlspecialchars($row['descricao']) ?></td>
                        <td><?= $row['data_criacao'] ?></td>
                        <td>
                            <a href="itens.php?id_checklist=<?= $row['id'] ?>">Editar</a>
                            <a href="excluir_checklist.php?id_checklist=<?= $row['id'] ?>">Excluir</a>
                            <a href="acessar_checklist.php?id_checklist=<?= $row['id'] ?>">Acessar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Nenhuma checklist encontrada.</p>
        <?php endif; ?>

        <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
    </div>
</body>
</html>
