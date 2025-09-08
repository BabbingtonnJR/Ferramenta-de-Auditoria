<?php
require 'conexao.php';
$conn = conecta_db();


$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
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
<link rel="stylesheet" href="css/nao_conformidade.css">
<script>
function toggleEmailForm(id) {
    const form = document.getElementById('email-form-' + id);
    form.style.display = (form.style.display === 'block') ? 'none' : 'block';
}
</script>
</head>
<body>

    <header class="header">
        <h1>📋 Não Conformidades Registradas</h1>
    </header>
<div class="container">

    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID NC</th>
                    <th>Checklist</th>
                    <th>Item</th>
                    <th>Descrição</th>
                    <th>Estado</th>
                    <th>Prioridade</th>
                    <th>Data de Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id_nao_conformidade'] ?></td>
                    <td><?= htmlspecialchars($row['nome_checklist']) ?></td>
                    <td><?= htmlspecialchars($row['descricao_item']) ?></td>
                    <td><?= htmlspecialchars($row['descricao_nao_conformidade']) ?></td>
                    <td><?= htmlspecialchars($row['estado']) ?></td>
                    <td><?= htmlspecialchars($row['prioridade']) ?></td>
                    <td><?= $row['data_criacao'] ?></td>
                    <td>
                        <button type="button" onclick="toggleEmailForm(<?= $row['id_nao_conformidade'] ?>)">Enviar Email</button>
                        <div class="email-form" id="email-form-<?= $row['id_nao_conformidade'] ?>">
                            <form method="POST" action="enviar_email_nc.php">
                                <input type="hidden" name="id_nc" value="<?= $row['id_nao_conformidade'] ?>">

                                <label>Destinatário:</label>
                                <input type="email" name="destinatario" required>

                                <label>Assunto:</label>
                                <input type="text" name="assunto" value="Não Conformidade Auditoria" required>

                                <label>Mensagem:</label>
                                <textarea name="mensagem" rows="6"><?= 
                                    "Checklist: " . htmlspecialchars($row['nome_checklist']) . "\n" .
                                    "Item: " . htmlspecialchars($row['descricao_item']) . "\n" .
                                    "Estado: " . htmlspecialchars($row['estado']) . "\n" .
                                    "Prioridade: " . htmlspecialchars($row['prioridade']) . "\n" .
                                    "Data de Criação: " . $row['data_criacao'] . "\n\n" .
                                    "Descrição da NC:\n" . htmlspecialchars($row['descricao_nao_conformidade']);
                                ?></textarea>

                                <button type="submit">Enviar Email</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">Nenhuma não conformidade encontrada.</p>
    <?php endif; ?>

    <a href="acessar_checklist.php?id_checklist=<?= $id_checklist?>" class="back-link">⬅ Voltar a checklist</a>
</div>

    <footer class="footer">
        PUCPR - Engenharia de Software © <?= date("Y") ?>
    </footer>
</body>


</html>

