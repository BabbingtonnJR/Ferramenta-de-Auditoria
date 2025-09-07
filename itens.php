<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

// Verifica se checklist existe
$sql_checklist = "SELECT * FROM Checklist WHERE id = ?";
$stmt = $conn->prepare($sql_checklist);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();

if (!$checklist) {
    die("❌ Checklist não encontrada.");
}

// Se o formulário for enviado para adicionar item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $descricao = $_POST["descricao"];
    $conformidade = $_POST["conformidade"];

    // 1. Descobre o próximo número do item no checklist
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

    // 2. Insere o item
    $sql_item = "INSERT INTO Item (descricao, conformidade, numero_item) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_item);
    $stmt->bind_param("ssi", $descricao, $conformidade, $numero_item);

    if ($stmt->execute()) {
        $id_item = $stmt->insert_id;

        // 3. Relaciona o item com o checklist
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

// Buscar todos os itens já cadastrados para esse checklist
$sql_itens = "
    SELECT i.id, i.descricao, i.conformidade, numero_item
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #004080;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        input, select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            background: #004080;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background: #0066cc;
        }
        .msg {
            text-align: center;
            font-weight: bold;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #004080;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Checklist: <?= htmlspecialchars($checklist['nome']) ?></h1>
        <p><strong>Descrição:</strong> <?= htmlspecialchars($checklist['descricao']) ?></p>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <h2>Adicionar Novo Item</h2>
        <form method="POST" action="">
            <input type="text" name="descricao" placeholder="Descrição do Item" required>
            
            <select name="conformidade" required>
                <option value="">Selecione Conformidade</option>
                <option value="Sim">Sim</option>
                <option value="Não">Não</option>
                <option value="Não se aplica">Não se aplica</option>
            </select>

            <button type="submit">Adicionar Item</button>
        </form>

        <h2>Itens já cadastrados</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Conformidade</th>
            </tr>
            <?php while ($row = $itens->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['numero_item'] ?></td>
                    <td><?= htmlspecialchars($row['descricao']) ?></td>
                    <td><?= htmlspecialchars($row['conformidade']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
    </div>
</body>
</html>
