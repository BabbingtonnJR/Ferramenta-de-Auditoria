<?php
require 'conexao.php';
$conn = conecta_db();

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
    <link rel="stylesheet" href="css/lista_checklist.css">
</head>
<body>
    <header class="header">
        <h1>📋 Checklists - PUCPR</h1>
    </header>

    <main class="main-content">
        <section class="card">
            <h2>Checklists Criadas</h2>

            <?php if ($result->num_rows > 0): ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Data de Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['nome']) ?></td>
                                <td><?= htmlspecialchars($row['descricao']) ?></td>
                                <td><?= $row['data_criacao'] ?></td>
                                <td>
                                    <a href="itens.php?id_checklist=<?= $row['id'] ?>">Editar</a> |
                                    <a href="excluir_checklist.php?id_checklist=<?= $row['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta checklist?');">Excluir</a> |
                                    <a href="acessar_checklist.php?id_checklist=<?= $row['id'] ?>">Acessar</a> |
                                    <a href="exportar_checklist.php?id_checklist=<?= $row['id'] ?>">Exportar</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma checklist encontrada.</p>
            <?php endif; ?>

            <div class="link-area">
                <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        PUCPR - Engenharia de Software © <?= date("Y") ?>
    </footer>
</body>
