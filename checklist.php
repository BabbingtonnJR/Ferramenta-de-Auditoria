<?php
require 'conexao.php';
$conn = conecta_db();

$msg = "";
// Se o formulário for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $descricao = $_POST["descricao"];

    $sql = "INSERT INTO Checklist (nome, descricao) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nome, $descricao);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id; // pega o ID da checklist criada
        header("Location: itens.php?id_checklist=" . $last_id);
        exit();
    } else {
        $msg = "❌ Erro ao criar checklist: " . $conn->error;
    }

    $stmt->close();
}


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Checklist</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 60px auto;
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
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        input, textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            background: #004080;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0066cc;
        }
        .msg {
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
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
        <h1>Criar Checklist</h1>

        <form method="POST" action="">
            <label for="nome">Nome do Checklist:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" rows="4" required></textarea>

            <button type="submit">Salvar Checklist</button>
        </form>

        <?php if ($msg): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>

        <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
    </div>
</body>
</html>