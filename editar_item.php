<?php
require 'conexao.php';
$conn = conecta_db();

$id_item = isset($_GET['id_item']) ? intval($_GET['id_item']) : 0;
$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

// Lógica de processamento do formulário de edição
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_item_form'])) {
    $id_item_form = intval($_POST['id_item_form']);
    $nova_descricao = $_POST['nova_descricao'];
    $id_checklist_form = intval($_POST['id_checklist_form']);

    if ($id_item_form > 0 && !empty($nova_descricao)) {
        $sql_update = "UPDATE Item SET descricao = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nova_descricao, $id_item_form);

        if ($stmt_update->execute()) {
            $msg = "✅ Item editado com sucesso!";
        } else {
            $msg = "❌ Erro ao editar o item: " . $conn->error;
        }

        $stmt_update->close();
    } else {
        $msg = "❌ Dados de edição inválidos.";
    }

    $conn->close();
    header("Location: itens.php?id_checklist=$id_checklist_form&msg=" . urlencode($msg));
    exit();
}

// Lógica para exibir o formulário de edição
$item = null;
if ($id_item > 0 && $id_checklist > 0) {
    $sql_item = "SELECT id, descricao FROM Item WHERE id = ?";
    $stmt_item = $conn->prepare($sql_item);
    $stmt_item->bind_param("i", $id_item);
    $stmt_item->execute();
    $result_item = $stmt_item->get_result();
    $item = $result_item->fetch_assoc();
    $stmt_item->close();

    if (!$item) {
        $msg = "❌ Item não encontrado.";
    }
} else {
    $msg = "❌ ID do item não fornecido.";
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Item</title>
    <link rel="stylesheet" href="css/editar_item.css">
</head>
<body>
    <header class="header">
        <h1>✏️ Editar Item - PUCPR</h1>
    </header>

    <main class="main-content">
        <section class="card">
            <?php if (!empty($msg)): ?>
                <div class="msg"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <?php if ($item): ?>
                <form method="POST" action="editar_item.php">
                    <input type="hidden" name="id_item_form" value="<?= $item['id'] ?>">
                    <input type="hidden" name="id_checklist_form" value="<?= $id_checklist ?>">
                    
                    <label for="nova_descricao">Nova Descrição:</label>
                    <input type="text" id="nova_descricao" name="nova_descricao" value="<?= htmlspecialchars($item['descricao']) ?>" required>
                    
                    <button type="submit">Salvar Alterações</button>
                </form>
            <?php endif; ?>

            <div class="link-area">
                <a href="itens.php?id_checklist=<?= $id_checklist ?>" class="back-link">⬅ Voltar para o Checklist</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        PUCPR - Engenharia de Software © <?= date("Y") ?>
    </footer>
</body>
</html>
