<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

if ($id_checklist <= 0) {
    die("❌ Checklist não especificada. <a href='lista_checklist.php'>Voltar</a>");
}

// Buscar o nome do checklist
$stmt_checklist = $conn->prepare("SELECT nome FROM Checklist WHERE id = ?");
$stmt_checklist->bind_param("i", $id_checklist);
$stmt_checklist->execute();
$result_checklist = $stmt_checklist->get_result();
$checklist = $result_checklist->fetch_assoc();
$stmt_checklist->close();

if (!$checklist) {
    die("❌ Checklist não encontrada. <a href='lista_checklist.php'>Voltar</a>");
}

// Buscar NCs do checklist específico
$sql = "
    SELECT 
        nc.id AS id_nao_conformidade,
        nc.descricao AS descricao_nao_conformidade,
        nc.estado,
        nc.prioridade,
        nc.data_criacao,
        i.descricao AS descricao_item
    FROM naoConformidade nc
    INNER JOIN Item i ON nc.id_item = i.id
    INNER JOIN Item_checklist ic ON i.id = ic.id_item
    WHERE ic.id_checklist = ?
    ORDER BY nc.data_criacao DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Não Conformidades - <?= htmlspecialchars($checklist['nome']) ?></title>
<style>
html, body { height:100%; margin:0; padding:0; font-family:"Segoe UI",Tahoma,Verdana,sans-serif; display:flex; flex-direction:column; }
body { background:#F4F4F4; color:#333; }

.header { background: #800000; color:#fff; padding:1rem 20px; text-align:center; box-shadow:0 2px 6px rgba(0,0,0,0.2); }
.header h1 { font-size:1.5rem; margin:0; }

.container { max-width:1000px; margin:20px auto; padding:30px; background:#fff; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.1); flex:1; }

h1 { text-align:center; color:#800000; margin-bottom:20px; }

table { width:100%; border-collapse:collapse; }
th, td { border:1px solid #ccc; padding:10px; text-align:left; }
th { background:#E6E6E6; color:#800000; }
tbody tr:nth-child(even) { background:#F9F9F9; }

button { background:#800000; color:#fff; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; transition:0.3s; }
button:hover { background:#a00000; }

.email-form { display:none; margin-top:5px; padding:10px; border:1px solid #ccc; background:#F9F9F9; border-radius:6px; }
.email-form input, .email-form textarea, .email-form button { width:100%; margin-bottom:5px; padding:8px; border-radius:6px; border:1px solid #ccc; }

.msg { text-align:center; font-weight:bold; margin:10px 0; color:#800000; }
.back-link { display:block; text-align:center; margin-top:20px; color:#800000; text-decoration:none; }
.back-link:hover { text-decoration:underline; }
.no-data { text-align:center; margin-top:20px; font-style:italic; color:#666; }

.footer { background: #800000; color: #fff; text-align:center; padding:0.8rem; font-size:0.9rem; }
</style>
<script>
function toggleEmailForm(id) {
    const form = document.getElementById('email-form-' + id);
    form.style.display = (form.style.display === 'block') ? 'none' : 'block';
}
</script>
</head>
<body>
<header class="header">
    <h2>📋 Não Conformidades</h2>
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
                                    "Checklist: " . htmlspecialchars($checklist['nome']) . "\n" .
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
        <p class="no-data">Nenhuma não conformidade encontrada neste checklist.</p>
    <?php endif; ?>

    <a href="acessar_checklist.php?id_checklist=<?= $id_checklist?>" class="back-link">⬅ Voltar a Checklist</a>
</div>

<footer class="footer">
    PUCPR - Engenharia de Software © <?= date("Y") ?>
</footer>
</body>
</html>
