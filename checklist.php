<?php
require 'conexao.php';
$conn = conecta_db();

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $descricao = $_POST["descricao"];
    $prazos_nome = $_POST["prazo_nome"];
    $prazos_dias = $_POST["prazo_dias"];

    if (empty($prazos_nome) || count($prazos_nome) == 0) {
        $msg = "❌ É obrigatório cadastrar pelo menos um prazo!";
    } else {
        $sql = "INSERT INTO Checklist (nome, descricao) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nome, $descricao);

        if ($stmt->execute()) {
            $last_id = $conn->insert_id;
            $stmt->close();

            $sqlPrazo = "INSERT INTO Prazo (nome, dias, id_checklist) VALUES (?, ?, ?)";
            $stmtPrazo = $conn->prepare($sqlPrazo);

            for ($i = 0; $i < count($prazos_nome); $i++) {
                $nomePrazo = $prazos_nome[$i];
                $diasPrazo = intval($prazos_dias[$i]);

                $stmtPrazo->bind_param("sii", $nomePrazo, $diasPrazo, $last_id);
                $stmtPrazo->execute();
            }


            $stmtPrazo->close();

            header("Location: itens.php?id_checklist=" . $last_id);
            exit();
        } else {
            $msg = "❌ Erro ao criar checklist: " . $conn->error;
        }
    }
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
    <header class="header">
        <h1>📋 Criar Checklist</h1>
    </header>

    <main class="main-content">
        <section class="card">
            <form method="POST" action="">
                <label for="nome">Nome do Checklist:</label>
                <input type="text" id="nome" name="nome" required>

                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4" required></textarea>

                <h3>⏳ Nível de não conformidades</h3>
                <div id="prazos">
                    <div class="prazo-item">
                        <label>Nome do Prazo:</label>
                        <input type="text" name="prazo_nome[]" required>

                        <label>Dias:</label>
                        <input type="number" name="prazo_dias[]" min="1" required>
                    </div>
                </div>

                <button type="button" onclick="addPrazo()">+ Adicionar Prazo</button>
                <br><br>

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

    <footer class="footer">
        <p>PUCPR - Engenharia de Software © <?= date("Y") ?></p>
    </footer>
</body>
</html>

<script>
function addPrazo() {
    const div = document.createElement("div");
    div.classList.add("prazo-item");
    div.innerHTML = `
        <label>Nome do Prazo:</label>
        <input type="text" name="prazo_nome[]" required>
        <label>Dias:</label>
        <input type="number" name="prazo_dias[]" min="1" required>
    `;
    document.getElementById("prazos").appendChild(div);
}
</script>
