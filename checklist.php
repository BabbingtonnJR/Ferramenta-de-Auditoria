<?php
require 'conexao.php';
$conn = conecta_db();

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $descricao = $_POST["descricao"];

    $sql = "INSERT INTO Checklist (nome, descricao) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nome, $descricao);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
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
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <!-- Cabeçalho -->
    <header class="header">
        <h1>📋 Criar Checklist</h1>
    </header>

    <!-- Conteúdo -->
    <main class="main-content">
        <section class="card">
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

            <div class="link-area">
                <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
            </div>
        </section>
    </main>

    <!-- Rodapé -->
    <footer class="footer">
        <p>PUCPR - Engenharia de Software © <?= date("Y") ?></p>
    </footer>
</body>
</html>
