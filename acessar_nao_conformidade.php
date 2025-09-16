<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

if ($id_checklist <= 0) {
    header("Location: acessar_checklist.php?msg=" . urlencode("❌ Checklist não especificada."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['concluir'], $_POST['id_nc'])) {
    $id_nc = intval($_POST['id_nc']);
    $email_superior = $_POST['email_superior'] ?? '';

    $stmt = $conn->prepare("UPDATE naoConformidade SET estado = 'Resolvida', data_conclusao = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id_nc);
    $stmt->execute();
    $stmt->close();

    $stmt_item = $conn->prepare("
        UPDATE Item i
        INNER JOIN naoConformidade nc ON nc.id_item = i.id
        SET i.conformidade = 'Sim'
        WHERE nc.id = ?
    ");
    $stmt_item->bind_param("i", $id_nc);
    $stmt_item->execute();
    $stmt_item->close();

    $stmt_check = $conn->prepare("
        SELECT nc.descricao AS nc_desc, i.descricao AS item_desc, p.dias, nc.data_criacao
        FROM naoConformidade nc
        INNER JOIN Item i ON nc.id_item = i.id
        LEFT JOIN Prazo p ON nc.id_prazo = p.id
        WHERE nc.id = ?
    ");
    $stmt_check->bind_param("i", $id_nc);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $nc_info = $res_check->fetch_assoc();
    $stmt_check->close();

    $prazo_vencido = false;
    if (!empty($nc_info['dias'])) {
        $data_prazo = date('Y-m-d H:i:s', strtotime($nc_info['data_criacao'] . " +{$nc_info['dias']} days"));
        if (new DateTime() > new DateTime($data_prazo)) {
            $prazo_vencido = true;
        }
    }

    if ($prazo_vencido && !empty($email_superior)) {
        $stmt_escal = $conn->prepare("
            INSERT INTO Escalonamento (id_nc, prazo, superior_responsavel, email_superior, estado, responsavel)
            VALUES (?, NOW(), ?, ?, 'Em Andamento', ?)
        ");
        $stmt_escal->bind_param("isss", $id_nc, $nc_info['item_desc'], $email_superior, $nc_info['item_desc']);
        $stmt_escal->execute();
        $stmt_escal->close();

        $assunto = "Escalonamento de Não Conformidade: {$nc_info['nc_desc']}";
        $mensagem = "A não conformidade '{$nc_info['nc_desc']}' não foi concluída dentro do prazo. Itens:\n- {$nc_info['item_desc']}\n\nEscalonamento criado automaticamente.";
        $headers = "From: no-reply@empresa.com\r\n";
        mail($email_superior, $assunto, $mensagem, $headers);

        $msg = "✅ Não conformidade concluída! Escalonamento criado e e-mail enviado ao superior.";
    } else {
        $msg = "✅ Não conformidade concluída com sucesso!";
    }

    header("Location: acessar_nao_conformidade.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
    exit();
}

$stmt_checklist = $conn->prepare("SELECT nome FROM Checklist WHERE id = ?");
$stmt_checklist->bind_param("i", $id_checklist);
$stmt_checklist->execute();
$result_checklist = $stmt_checklist->get_result();
$checklist = $result_checklist->fetch_assoc();
$stmt_checklist->close();

if (!$checklist) {
    header("Location: acessar_checklist.php?msg=" . urlencode("❌ Checklist não encontrada."));
    exit;
}

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
  AND nc.estado != 'Resolvida'
ORDER BY nc.data_criacao DESC;
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$result = $stmt->get_result();

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
button { background:#800000; color:#fff; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; margin-right:5px; }
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
            <form method="POST" action="enviar_email_nc.php" style="display:inline-block; vertical-align:top;">
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
                    <tr>
                        <td class="label">Email do Superior:</td>
                        <td colspan="3"><input type="email" name="email_superior" required></td>
                    </tr>
                </table>
                <button type="submit">✉ Enviar Email</button>
            </form>

            <form method="POST" style="display:inline-block; margin-top:5px;">
                <input type="hidden" name="id_nc" value="<?= $row['id_nc'] ?>">
                <input type="hidden" name="email_superior" value="">
                <button type="submit" name="concluir">✅ Concluir</button>
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
