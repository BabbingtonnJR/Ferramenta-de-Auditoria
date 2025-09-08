<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// --- TRATAMENTO DO POST (CRIAR / ESTENDER / CONCLUIR) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Criar nova escalabilidade
    if (!empty($_POST['prazo']) && !empty($_POST['responsavel']) && !empty($_POST['itens']) && is_array($_POST['itens'])) {
        $prazo_input = $_POST['prazo'];
        // converte datetime-local (ex: 2025-09-08T15:30) para formato MySQL 'Y-m-d H:i:s'
        $prazo = date('Y-m-d H:i:s', strtotime($prazo_input));
        $responsavel = trim($_POST['responsavel']);
        $itens = $_POST['itens']; // array de ids de não conformidade

        foreach ($itens as $id_nc_raw) {
            $id_nc = intval($id_nc_raw);
            if ($id_nc <= 0) continue;

            $sql_insert = "INSERT INTO Escalonamento (id_nc, prazo, estado, responsavel) VALUES (?, ?, 'Em Andamento', ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert === false) {
                $msg = "❌ Erro interno (prepare): " . $conn->error;
                break;
            }
            $stmt_insert->bind_param("iss", $id_nc, $prazo, $responsavel);
            if (!$stmt_insert->execute()) {
                $msg = "❌ Erro ao criar escalonamento: " . $stmt_insert->error;
                $stmt_insert->close();
                break;
            }
            $stmt_insert->close();
        }

        if ($msg === '') $msg = "✅ Escalonamento(s) criado(s) com sucesso!";
    }

    // Estender prazo
    if (isset($_POST['estender'], $_POST['id_esc'], $_POST['novo_prazo'])) {
        $id_esc = intval($_POST['id_esc']);
        $novo_prazo_input = $_POST['novo_prazo'];
        $novo_prazo = date('Y-m-d H:i:s', strtotime($novo_prazo_input));

        $stmt = $conn->prepare("UPDATE Escalonamento SET prazo = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $novo_prazo, $id_esc);
            if ($stmt->execute()) {
                $msg = "✅ Prazo da escalabilidade atualizado!";
            } else {
                $msg = "❌ Erro ao atualizar prazo: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $msg = "❌ Erro interno (prepare update prazo): " . $conn->error;
        }
    }

    // Concluir escalabilidade
    if (isset($_POST['concluir'], $_POST['id_esc'])) {
        $id_esc = intval($_POST['id_esc']);
        $stmt = $conn->prepare("UPDATE Escalonamento SET estado = 'Concluida', data_conclusao = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_esc);
            if ($stmt->execute()) {
                $msg = "✅ Escalabilidade concluída!";
            } else {
                $msg = "❌ Erro ao concluir escalabilidade: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $msg = "❌ Erro interno (prepare update concluir): " . $conn->error;
        }
    }

    // Redireciona com mensagem (PRG)
    header("Location: acessar_escalabilidade.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
    exit();
}

// --- BUSCAR A CHECKLIST (IMPORTANTE: evita os avisos que você estava tendo) ---
$sql_checklist = "SELECT id, nome, descricao FROM Checklist WHERE id = ?";
$stmt = $conn->prepare($sql_checklist);
if ($stmt) {
    $stmt->bind_param("i", $id_checklist);
    $stmt->execute();
    $result = $stmt->get_result();
    $checklist = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Erro ao preparar consulta da checklist: " . $conn->error);
}

if (!$checklist) {
    // Checklist não encontrada — exibe mensagem simples e encerra
    $conn->close();
    die("<p>❌ Checklist não encontrada. <a href='lista_checklist.php'>Voltar</a></p>");
}

// --- Buscar itens não conformes do checklist ---
$sql_nc = "
SELECT nc.id, i.descricao AS item_desc, nc.descricao AS descricao_nc
FROM naoConformidade nc
INNER JOIN Item i ON nc.id_item = i.id
INNER JOIN Item_checklist ic ON i.id = ic.id_item
WHERE ic.id_checklist = ?";
$stmt = $conn->prepare($sql_nc);
if ($stmt) {
    $stmt->bind_param("i", $id_checklist);
    $stmt->execute();
    $naoConformes = $stmt->get_result();
    $stmt->close();
} else {
    die("Erro ao preparar consulta de não conformidades: " . $conn->error);
}

// --- Buscar escalabilidades já criadas do checklist ---
$sql_escal = "
SELECT e.id, nc.id AS nc_id, i.descricao AS item_desc, nc.descricao AS descricao_nc, e.prazo, e.estado, e.responsavel
FROM Escalonamento e
INNER JOIN naoConformidade nc ON e.id_nc = nc.id
INNER JOIN Item i ON nc.id_item = i.id
INNER JOIN Item_checklist ic ON i.id = ic.id_item
WHERE ic.id_checklist = ?
ORDER BY e.data_criacao DESC";
$stmt = $conn->prepare($sql_escal);
if ($stmt) {
    $stmt->bind_param("i", $id_checklist);
    $stmt->execute();
    $escalabilidades = $stmt->get_result();
    $stmt->close();
} else {
    die("Erro ao preparar consulta de escalabilidades: " . $conn->error);
}

$conn->close();

?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Escalabilidade - <?= htmlspecialchars($checklist['nome']) ?></title>
<link rel="stylesheet" href="css/escalabilidade.css">
</head>
<body>
<header class="header">
    <h1>📊 Escalabilidade - <?= htmlspecialchars($checklist['nome']) ?></h1>
</header>

<main class="main-content">
    <div class="container">
        <?php if ($msg): ?>
            <div class="msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <section class="card">
            <h2>Criar Nova Escalabilidade</h2>
            <form method="POST">
                <label>Prazo:</label>
                <input type="datetime-local" name="prazo" required>
                <label>Responsável:</label>
                <input type="text" name="responsavel" required>
                <label>Itens Não Conformes:</label>
                <?php if ($naoConformes->num_rows > 0): ?>
                    <?php while ($nc = $naoConformes->fetch_assoc()): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" name="itens[]" value="<?= $nc['id'] ?>" id="nc-<?= $nc['id'] ?>">
                            <label for="nc-<?= $nc['id'] ?>"><?= htmlspecialchars($nc['item_desc']) ?> - <?= htmlspecialchars($nc['descricao_nc']) ?></label>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Nenhum item não conforme.</p>
                <?php endif; ?>
                <button type="submit">Criar Escalabilidade</button>
            </form>
        </section>

        <section class="card">
            <h2>Escalabilidades Existentes</h2>
            <?php if ($escalabilidades->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Não Conformidade</th>
                        <th>Prazo</th>
                        <th>Responsável</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($esc = $escalabilidades->fetch_assoc()): ?>
                    <tr>
                        <td><?= $esc['id'] ?></td>
                        <td><?= htmlspecialchars($esc['item_desc']) ?></td>
                        <td><?= htmlspecialchars($esc['descricao_nc']) ?></td>
                        <td><?= htmlspecialchars($esc['prazo']) ?></td>
                        <td><?= htmlspecialchars($esc['responsavel']) ?></td>
                        <td>
                            <?= htmlspecialchars($esc['estado']) ?>
                            <?php if ($esc['estado'] !== 'Concluida'): ?>
                                <form method="POST" style="display:inline-block">
                                    <input type="hidden" name="id_esc" value="<?= $esc['id'] ?>">
                                    <input type="datetime-local" name="novo_prazo" required>
                                    <button type="submit" name="estender">Estender</button>
                                </form>
                                <form method="POST" style="display:inline-block">
                                    <input type="hidden" name="id_esc" value="<?= $esc['id'] ?>">
                                    <button type="submit" name="concluir">Concluir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Nenhuma escalabilidade registrada.</p>
            <?php endif; ?>
        </section>

        <a href="acessar_checklist.php?id_checklist=<?= $id_checklist?>" class="back-link">⬅ Voltar a Checklist</a>
    </div>
</main>

<footer class="footer">
    PUCPR - Engenharia de Software © <?= date("Y") ?>
</footer>
</body>
</html>
