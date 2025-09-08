<?php
require 'conexao.php';
$conn = conecta_db();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["arquivo"])) {
    $conteudo = file_get_contents($_FILES["arquivo"]["tmp_name"]);
    $dados = json_decode($conteudo, true);

    if (!$dados) {
        die("❌ Arquivo inválido!");
    }

    $nome = $dados["checklist"]["nome"];
    $descricao = $dados["checklist"]["descricao"];
    $data_criacao = $dados["checklist"]["data_criacao"];

    // Inserir checklist
    $sql = "INSERT INTO Checklist (nome, descricao, data_criacao) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $descricao, $data_criacao);
    $stmt->execute();
    $id_checklist = $stmt->insert_id;

    // Inserir itens
    foreach ($dados["itens"] as $item) {
        $descricao_item = $item["descricao"];
        $numero_item = $item["numero_item"];

        $sql_item = "INSERT INTO Item (descricao, numero_item) VALUES (?, ?)";
        $stmt_item = $conn->prepare($sql_item);
        $stmt_item->bind_param("si", $descricao_item, $numero_item);
        $stmt_item->execute();
        $id_item = $stmt_item->insert_id;

        $sql_rel = "INSERT INTO Item_checklist (id_checklist, id_item) VALUES (?, ?)";
        $stmt_rel = $conn->prepare($sql_rel);
        $stmt_rel->bind_param("ii", $id_checklist, $id_item);
        $stmt_rel->execute();
    }

    $msg = "✅ Checklist importada com sucesso!";
    header("Location: lista_checklist.php?msg=" . urlencode($msg));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Checklist</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; padding:0; }
        .container { max-width:600px; margin:40px auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        h1 { text-align:center; color:#004080; }
        form { display:flex; flex-direction:column; gap:15px; }
        input[type="file"] { padding:10px; border:1px solid #ccc; border-radius:6px; }
        button { background:#004080; color:white; padding:10px; border:none; border-radius:6px; cursor:pointer; }
        button:hover { background:#0066cc; }
        .back-link { display:block; text-align:center; margin-top:20px; color:#004080; text-decoration:none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Importar Checklist</h1>
        <form action="importar_checklist.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="arquivo" accept=".json" required>
            <button type="submit">Importar</button>
        </form>
        <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
    </div>
</body>
</html>
