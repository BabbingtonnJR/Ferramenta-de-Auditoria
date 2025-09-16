<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

$sql_checklist = "SELECT * FROM Checklist WHERE id = ?";
$stmt = $conn->prepare($sql_checklist);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();

if (!$checklist) {
    die("❌ Checklist não encontrada.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $descricao = $_POST["descricao"];

    $sql_numero = "
        SELECT COALESCE(MAX(i.numero_item), 0) + 1 AS proximo
        FROM Item i
        INNER JOIN Item_checklist ic ON i.id = ic.id_item
        WHERE ic.id_checklist = ?";
    $stmt_num = $conn->prepare($sql_numero);
    $stmt_num->bind_param("i", $id_checklist);
    $stmt_num->execute();
    $result_num = $stmt_num->get_result();
    $row_num = $result_num->fetch_assoc();
    $numero_item = $row_num['proximo'];
    $stmt_num->close();

    $sql_item = "INSERT INTO Item (descricao, numero_item) VALUES (?, ?)";
    $stmt = $conn->prepare($sql_item);
    $stmt->bind_param("si", $descricao, $numero_item);

    if ($stmt->execute()) {
        $id_item = $stmt->insert_id;

        $sql_relacao = "INSERT INTO Item_checklist (id_checklist, id_item) VALUES (?, ?)";
        $stmt_rel = $conn->prepare($sql_relacao);
        $stmt_rel->bind_param("ii", $id_checklist, $id_item);

        if ($stmt_rel->execute()) {
            $msg = "✅ Item adicionado com sucesso!";
        } else {
            $msg = "❌ Erro ao vincular item ao checklist: " . $conn->error;
        }

        $stmt_rel->close();
    } else {
        $msg = "❌ Erro ao adicionar item: " . $conn->error;
    }

    $stmt->close();

    header("Location: itens.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
    exit();
}

$sql_itens = "
    SELECT i.id, i.descricao, i.numero_item
    FROM Item i
    INNER JOIN Item_checklist ic ON i.id = ic.id_item
    WHERE ic.id_checklist = ?";
$stmt = $conn->prepare($sql_itens);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$itens = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itens do Checklist</title>
    <link rel="stylesheet" href="css/itens.css">
</head>
<body>
    <header class="header">
        <h1>📋 Itens - PUCPR</h1>
    </header>

    <main class="main-content">
        <section class="card">
            <h2><?= htmlspecialchars($checklist['nome']) ?></h2>
            <p><strong>Descrição:</strong> <?= htmlspecialchars($checklist['descricao']) ?></p>

            <?php if (isset($_GET['msg'])): ?>
                <div class="msg"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>

            <h3>Adicionar Novo Item</h3>
            <form method="POST" action="">
                <input type="text" name="descricao" placeholder="Descrição do Item" required>
                <button type="submit">Adicionar Item</button>
            </form>

            <h3>Itens já cadastrados</h3>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $itens->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['numero_item'] ?></td>
                            <td><?= htmlspecialchars($row['descricao']) ?></td>
                            <td>
                                <a href="excluir_item.php?id_item=<?= $row['id'] ?>&id_checklist=<?= $id_checklist ?>" onclick="return confirm('Tem certeza que deseja excluir este item?');">Excluir</a>
                                <a href="editar_item.php?id_item=<?= $row['id'] ?>&id_checklist=<?= $id_checklist ?>">Editar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="link-area">
                <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        PUCPR - Engenharia de Software © <?= date("Y") ?>
    </footer>
</body>
</html>
