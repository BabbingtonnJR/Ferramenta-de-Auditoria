<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Concluir escalabilidade
    if (isset($_POST['concluir'], $_POST['id_esc'])) {
        $stmt = $conn->prepare("UPDATE Escalonamento SET estado = 'Concluida' WHERE id = ?");
        $stmt->bind_param("i", $_POST['id_esc']);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ Escalabilidade concluída!";
    }

    // Estender prazo
    if (isset($_POST['estender'], $_POST['id_esc'], $_POST['novo_prazo'])) {
        $stmt = $conn->prepare("UPDATE Escalonamento SET prazo = ? WHERE id = ?");
        $stmt->bind_param("si", $_POST['novo_prazo'], $_POST['id_esc']);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ Prazo da escalabilidade atualizado!";
    }

    // Redirecionar para evitar reenvio do formulário
    header("Location: acessar_escalabilidade.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
    exit();
}


// Verifica se checklist existe
$sql_checklist = "SELECT * FROM Checklist WHERE id = ?";
$stmt = $conn->prepare($sql_checklist);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();
$stmt->close();

if (!$checklist) {
    die("❌ Checklist não encontrada.");
}

// Criar nova escalabilidade
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prazo'], $_POST['responsavel'], $_POST['itens'])) {
    $prazo = $_POST['prazo'];
    $responsavel = $_POST['responsavel'];
    $itens = $_POST['itens']; // array de ids de não conformidade

    foreach ($itens as $id_nc) {
        $sql_insert = "INSERT INTO Escalonamento (id_nc, prazo, estado, responsavel) VALUES (?, ?, 'Em Andamento', ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iss", $id_nc, $prazo, $responsavel);
        $stmt_insert->execute();
        $stmt_insert->close();
    }

    $msg = "✅ Escalonamento(s) criado(s) com sucesso!";
}

// Buscar itens não conformes do checklist
$sql_nc = "
SELECT nc.id, i.descricao AS item_desc, nc.descricao AS descricao_nc
FROM naoConformidade nc
INNER JOIN Item i ON nc.id_item = i.id
INNER JOIN Item_checklist ic ON i.id = ic.id_item
WHERE ic.id_checklist = ?";
$stmt = $conn->prepare($sql_nc);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$naoConformes = $stmt->get_result();
$stmt->close();

// Buscar escalabilidades já criadas do checklist
$sql_escal = "
SELECT e.id, nc.id AS nc_id, i.descricao AS item_desc, nc.descricao AS descricao_nc, e.prazo, e.estado, e.responsavel
FROM Escalonamento e
INNER JOIN naoConformidade nc ON e.id_nc = nc.id
INNER JOIN Item i ON nc.id_item = i.id
INNER JOIN Item_checklist ic ON i.id = ic.id_item
WHERE ic.id_checklist = ?
ORDER BY e.data_criacao DESC";
$stmt = $conn->prepare($sql_escal);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$escalabilidades = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Escalabilidade - Checklist <?= htmlspecialchars($checklist['nome']) ?></title>
<style>
body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; padding:0; }
.container { max-width: 900px; margin: 40px auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
h1 { text-align:center; color:#004080; margin-top:0; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:10px; text-align:center; }
th { background:#e0e0e0; }
input, select { padding:5px; border:1px solid #ccc; border-radius:6px; }
button { background:#004080; color:white; padding:8px 15px; border:none; border-radius:6px; cursor:pointer; }
button:hover { background:#0066cc; }
.msg { text-align:center; font-weight:bold; margin:10px 0; }
.back-link { display:block; text-align:center; margin-top:20px; color:#004080; text-decoration:none; }
</style>
</head>
<body>
<div class="container">
<h1>Escalabilidade - Checklist: <?= htmlspecialchars($checklist['nome']) ?></h1>

<?php if($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<h2>Criar Nova Escalabilidade</h2>
<form method="POST" action="">
    <label>Prazo:</label>
    <input type="datetime-local" name="prazo" required>
    
    <label>Responsável:</label>
    <input type="text" name="responsavel" required>
    
    <label>Selecionar Itens Não Conformes:</label>
    <?php if($naoConformes->num_rows > 0): ?>
        <?php while($nc = $naoConformes->fetch_assoc()): ?>
            <div>
                <input type="checkbox" name="itens[]" value="<?= $nc['id'] ?>" id="nc-<?= $nc['id'] ?>">
                <label for="nc-<?= $nc['id'] ?>"><?= htmlspecialchars($nc['item_desc']) ?> - <?= htmlspecialchars($nc['descricao_nc']) ?></label>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Nenhum item não conforme encontrado neste checklist.</p>
    <?php endif; ?>
    
    <button type="submit" style="margin-top:10px;">Criar Escalabilidade</button>
</form>

<h2>Escalabilidades Existentes</h2>
<?php if($escalabilidades->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Não Conformidade</th>
            <th>Prazo</th>
            <th>Responsável</th>
            <th>Estado</th>
        </tr>
        <?php while($esc = $escalabilidades->fetch_assoc()): ?>
            <tr>
                <td><?= $esc['id'] ?></td>
                <td><?= htmlspecialchars($esc['item_desc']) ?></td>
                <td><?= htmlspecialchars($esc['descricao_nc']) ?></td>
                <td><?= $esc['prazo'] ?></td>
                <td><?= htmlspecialchars($esc['responsavel']) ?></td>
                <td>
                    <?= htmlspecialchars($esc['estado']) ?>
                    <?php if($esc['estado'] != 'Concluida'): ?>
                        <!-- Formulário para estender prazo -->
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="id_esc" value="<?= $esc['id'] ?>">
                            <input type="datetime-local" name="novo_prazo" required>
                            <button type="submit" name="estender">Estender Prazo</button>
                        </form>

                        <!-- Formulário para concluir -->
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="id_esc" value="<?= $esc['id'] ?>">
                            <button type="submit" name="concluir">Concluir</button>
                        </form>
                    <?php endif; ?>
                </td>

            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>Nenhuma escalabilidade encontrada para este checklist.</p>
<?php endif; ?>

<a href="lista_checklist.php" class="back-link">⬅ Voltar à Lista de Checklists</a>
</div>
</body>
</html>
