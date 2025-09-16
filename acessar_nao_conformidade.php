<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

if ($id_checklist <= 0) {
    die("❌ Checklist não especificada. <a href='lista_checklist.php'>Voltar</a>");
}

// Buscar nome do checklist
$stmt_checklist = $conn->prepare("SELECT nome FROM Checklist WHERE id = ?");
$stmt_checklist->bind_param("i", $id_checklist);
$stmt_checklist->execute();
$result_checklist = $stmt_checklist->get_result();
$checklist = $result_checklist->fetch_assoc();
$stmt_checklist->close();

if (!$checklist) {
    die("❌ Checklist não encontrada. <a href='lista_checklist.php'>Voltar</a>");
}

// Buscar NCs com classificação
$sql = "
    SELECT 
    nc.id AS id_nc,
    nc.descricao AS descricao_nc,
    nc.estado,
    nc.prioridade,
    nc.data_criacao,
    i.descricao AS descricao_item,
    p.nome AS classificacao,
    p.dias AS prazo_dias
FROM naoConformidade nc
INNER JOIN Item i ON nc.id_item = i.id
INNER JOIN Item_checklist ic ON ic.id_item = i.id
LEFT JOIN Prazo p ON nc.id_prazo = p.id
WHERE ic.id_checklist = ?
ORDER BY nc.data_criacao DESC;
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$result = $stmt->get_result();
// Buscar as prioridades definidas (Prazos)
$prazos = [];
$result_prazos = $conn->query("SELECT id, nome, dias FROM Prazo");

if ($result_prazos && $result_prazos->num_rows > 0) {
    while ($prazo = $result_prazos->fetch_assoc()) {
        $prazos[] = $prazo;
    }
}


$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Solicitação de Resolução de Não Conformidade</title>
<style>
body { font-family: Arial, sans-serif; background:#F4F4F4; margin:0; padding:0; }
.header { background:#800000; color:#fff; text-align:center; padding:15px; }
.container { max-width:1000px; margin:20px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.2); }
h2 { color:#800000; }
.table-form { width:100%; border-collapse:collapse; margin-bottom:20px; }
.table-form td, .table-form th { border:1px solid #ccc; padding:8px; vertical-align:top; }
.table-form th { background:#eee; text-align:left; }
.label { font-weight:bold; }
textarea, input, select { width:100%; padding:6px; border:1px solid #ccc; border-radius:4px; }
button { background:#800000; color:#fff; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; }
button:hover { background:#a00000; }
.msg { text-align:center; color:#800000; margin:10px 0; font-weight:bold; }
.footer { background:#800000; color:#fff; text-align:center; padding:10px; margin-top:20px; }
</style>
</head>
<body>
<header class="header">
    <h1>📋 Solicitações de Resolução de Não Conformidade</h1>
</header>

<div class="container">
    <h2>Checklist: <?= htmlspecialchars($checklist['nome']) ?></h2>

    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <form method="POST" action="enviar_email_nc.php">
                <input type="hidden" name="id_nc" value="<?= $row['id_nc'] ?>">

                <table class="table-form">
                    <tr>
                        <th colspan="4">Solicitação de Resolução de Não Conformidade</th>
                    </tr>
                    <tr>
                        <td class="label">Projeto:</td>
                        <td><?= htmlspecialchars($checklist['nome']) ?></td>
                        
                        <td class="label">Data da Solicitação:</td>
                        <td><?= $row['data_criacao'] ?></td>
                    </tr>
                    <tr>
                        <td class="label">Responsável:</td>
                        <td><input type="text" name="responsavel" value=""></td>
                        <td class="label">RQA Responsável:</td>
                        <td><input type="text" name="rqa" value=""></td>
                    </tr>
                    <tr>
                        <td class="label">Prazo de Resolução:</td>
                        <td><input type="date" name="prazo"></td>
                        <td class="label">Estado:</td>
                        <td>
                            <select name="estado">
                                <option value="Aberta" <?= ($row['estado']=="Aberta")?"selected":"" ?>>Aberta</option>
                                <option value="Em Andamento" <?= ($row['estado']=="Em Andamento")?"selected":"" ?>>Em Andamento</option>
                                <option value="Resolvida" <?= ($row['estado']=="Resolvida")?"selected":"" ?>>Resolvida</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Item de Checklist:</td>
                        <td colspan="3"><?= htmlspecialchars($row['descricao_item']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Descrição:</td>
                        <td colspan="3"><textarea name="descricao_nc"><?= htmlspecialchars($row['descricao_nc']) ?></textarea></td>
                    </tr>
                    <tr>
                        <td class="label">Classificação:</td>
                        <td colspan="3">
                            <?= htmlspecialchars($row['prioridade']) ?> 
                            (<?= htmlspecialchars($row['prazo_dias']) ?> dias)
                            <input type="hidden" name="prioridade_nc[<?= $row['id_nc'] ?>]" value="<?= htmlspecialchars($row['prioridade']) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Ação Corretiva Indicada:</td>
                        <td colspan="3"><textarea name="acao"></textarea></td>
                    </tr>
                    <tr>
                        <td class="label">Email do Destinatário:</td>
                        <td colspan="3"><input type="email" name="destinatario" required></td>
                    </tr>
                </table>
                <button type="submit">✉ Enviar Email</button>
            </form>
            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Nenhuma não conformidade encontrada neste checklist.</p>
    <?php endif; ?>

    <a href="acessar_checklist.php?id_checklist=<?= $id_checklist?>" style="display:block;margin-top:15px;">⬅ Voltar à Checklist</a>
</div>

<footer class="footer">
    PUCPR - Engenharia de Software © <?= date("Y") ?>
</footer>
</body>
</html>
