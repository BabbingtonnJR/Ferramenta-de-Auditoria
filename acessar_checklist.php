<?php
require 'conexao.php';
$conn = conecta_db();

$id_checklist = isset($_GET['id_checklist']) ? intval($_GET['id_checklist']) : 0;
$msg = "";

// Lógica de salvamento dos dados (conformidade e não conformidade)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['conformidade'])) {
    foreach ($_POST['conformidade'] as $id_item => $status) {
        // Primeiro, atualiza a conformidade na tabela Item
        $sql_update_conformidade = "UPDATE Item SET conformidade = ? WHERE id = ?";
        $stmt_update_conformidade = $conn->prepare($sql_update_conformidade);
        $stmt_update_conformidade->bind_param("si", $status, $id_item);
        $stmt_update_conformidade->execute();
        $stmt_update_conformidade->close();
        
        // Se a conformidade for 'Nao', insere na tabela naoConformidade
        if ($status == 'Nao') {
            $descricao_nc = $_POST['descricao_nc'][$id_item];
            $estado_nc = $_POST['estado_nc'][$id_item];
            $prioridade_nc = $_POST['prioridade_nc'][$id_item];
            
            // Verifica se a não conformidade já existe para evitar duplicatas
            $sql_check_nc = "SELECT id FROM naoConformidade WHERE id_item = ?";
            $stmt_check_nc = $conn->prepare($sql_check_nc);
            $stmt_check_nc->bind_param("i", $id_item);
            $stmt_check_nc->execute();
            $result_check_nc = $stmt_check_nc->get_result();
            
            if ($result_check_nc->num_rows == 0) {
                // Insere nova não conformidade
                $sql_insert_nc = "INSERT INTO naoConformidade (id_item, descricao, estado, prioridade) VALUES (?, ?, ?, ?)";
                $stmt_insert_nc = $conn->prepare($sql_insert_nc);
                $stmt_insert_nc->bind_param("isss", $id_item, $descricao_nc, $estado_nc, $prioridade_nc);
                $stmt_insert_nc->execute();
                $stmt_insert_nc->close();
            } else {
                // Atualiza a não conformidade existente
                $sql_update_nc = "UPDATE naoConformidade SET descricao = ?, estado = ?, prioridade = ? WHERE id_item = ?";
                $stmt_update_nc = $conn->prepare($sql_update_nc);
                $stmt_update_nc->bind_param("sssi", $descricao_nc, $estado_nc, $prioridade_nc, $id_item);
                $stmt_update_nc->execute();
                $stmt_update_nc->close();
            }
            $stmt_check_nc->close();

        } else {
            // Se a conformidade não for 'Nao', marca a não conformidade como resolvida, se existir
            $sql_update_nc = "UPDATE naoConformidade SET estado = 'Resolvida' WHERE id_item = ?";
            $stmt_update_nc = $conn->prepare($sql_update_nc);
            $stmt_update_nc->bind_param("i", $id_item);
            $stmt_update_nc->execute();
            $stmt_update_nc->close();
        }
    }
    
    $msg = "✅ Status de conformidade atualizados com sucesso!";
    header("Location: acessar_checklist.php?id_checklist=$id_checklist&msg=" . urlencode($msg));
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

// Buscar todos os itens e os dados de não conformidade
$sql_itens = "
    SELECT 
        i.id, 
        i.numero_item, 
        i.descricao, 
        i.conformidade,
        nc.descricao AS descricao_nc,
        nc.estado AS estado_nc,
        nc.prioridade AS prioridade_nc
    FROM 
        Item i
    INNER JOIN 
        Item_checklist ic ON i.id = ic.id_item
    LEFT JOIN 
        naoConformidade nc ON i.id = nc.id_item
    WHERE 
        ic.id_checklist = ?
    ORDER BY 
        i.numero_item ASC";
$stmt = $conn->prepare($sql_itens);
$stmt->bind_param("i", $id_checklist);
$stmt->execute();
$itens = $stmt->get_result();
$stmt->close();

$conn->close();

// Calcular % de aderência
$total_itens = 0;
$total_conformes = 0;

foreach ($itens as $item) {
    if ($item['conformidade'] != 'Nao Aplicavel') {
        $total_itens++;
        if ($item['conformidade'] == 'Sim') {
            $total_conformes++;
        }
    }
}

$percentual_aderencia = ($total_itens > 0) ? round(($total_conformes / $total_itens) * 100, 2) : 0;

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

        input,
        select {
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

        table,
        th,
        td {
            border: 1px solid #ccc;
        }

        th,
        td {
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

        .nao-conformidade-fields {
            text-align: left;
        }

        .nao-conformidade-fields label,
        .nao-conformidade-fields input,
        .nao-conformidade-fields textarea {
            display: block;
            width: 95%;
            margin-bottom: 5px;
        }

        /* Estilo normal da página já existente aqui... */

        /* Estilo para impressão */
        @media print {
            /* Ocultar tudo que não é relatório */
            body * {
                visibility: hidden;
            }

            /* Mostrar apenas o container do relatório */
            #relatorio, #relatorio * {
                visibility: visible;
            }

            /* Garantir que o container fique no topo da página impressa */
            #relatorio {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            /* Ocultar botões dentro do relatório */
            #relatorio button,
            #relatorio a {
                display: none;
            }
        }

    </style>
    
</head>
<body>
    <div class="container">
    <h1>Checklist: <?= e($checklist['nome'])?></h1>
    <p><strong>Descrição:</strong> <?= e($checklist['descricao'])?></p>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg"><?= e($_GET['msg'])?></div>
    <?php endif; ?>

    <h2>Itens do Checklist</h2>
    <form method="POST" action="acessar_checklist.php?id_checklist=<?= $id_checklist ?>">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Conformidade</th>
                    <th>Detalhes Não Conformidade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($itens as $row): ?>
                <tr id="item-row-<?= $row['id'] ?>">
                    <td><?= $row['numero_item'] ?></td>
                    <td><?= e($row['descricao'])?></td>
                    <td>
                        <select name="conformidade[<?= $row['id'] ?>]" onchange="mostrarNaoConformidade(this, '<?= $row['id'] ?>')">
                            <option value="">Selecione uma opção</option>
                            <option value="Nao Aplicavel" <?= ($row['conformidade'] == 'Nao Aplicavel' || $row['conformidade'] == '') ? 'selected' : '' ?>>Não Aplicável</option>
                            <option value="Sim" <?= ($row['conformidade'] == 'Sim') ? 'selected' : '' ?>>Sim</option>
                            <option value="Nao" <?= ($row['conformidade'] == 'Nao') ? 'selected' : '' ?>>Não</option>
                        </select>
                    </td>
                    <td class="nao-conformidade-fields">
                        <!-- Bloco para Não Conformidade -->
                        <div id="nao-conformidade-<?= $row['id'] ?>" style="display: <?= ($row['conformidade'] == 'Nao') ? 'block' : 'none' ?>;">
                            <label>Descrição:</label>
                            <textarea name="descricao_nc[<?= $row['id'] ?>]" rows="2"><?= e($row['descricao_nc']) ?></textarea>
                            <label>Estado:</label>
                            <input type="text" name="estado_nc[<?= $row['id'] ?>]" value="<?= e($row['estado_nc']) ?>">
                            <label>Prioridade:</label>
                            <input type="text" name="prioridade_nc[<?= $row['id'] ?>]" value="<?= e($row['prioridade_nc']) ?>">
                        </div>

                        <!-- Bloco para Não Aplicável -->
                        <div id="nao-aplicavel-<?= $row['id'] ?>" style="display: <?= ($row['conformidade'] == 'Nao Aplicavel') ? 'block' : 'none' ?>;">
                        </div>

                    </td>

                    <td>
                        <a href="excluir_item.php?id_item=<?= $row['id'] ?>&id_checklist=<?= $id_checklist ?>" onclick="return confirm('Tem certeza que deseja excluir este item?');">Excluir</a>
                        <a href="editar_item.php?id_item=<?= $row['id'] ?>&id_checklist=<?= $id_checklist ?>">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="container" id="relatorio">
            <h1>Checklist: <?= e($checklist['nome'])?></h1>
            <p><strong>Descrição:</strong> <?= e($checklist['descricao']) ?></p>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Conformidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $row): ?>
                    <tr>
                        <td><?= $row['numero_item'] ?></td>
                        <td><?= e($row['descricao'])?></td>
                        <td><?= e($row['conformidade']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Relatório de Aderência</h2>
            <p><strong>Total de Itens Avaliados:</strong> <?= $total_itens ?></p>
            <p><strong>Total de Itens Conformes:</strong> <?= $total_conformes ?></p>
            <p><strong>Percentual de Aderência:</strong> <?= $percentual_aderencia ?>%</p>
        </div>

<!-- Botão para imprimir -->
<button onclick="window.print()">🖨️ Imprimir Relatório</button>


        <button type="submit" style="margin-top: 15px;">Salvar Conformidade</button>
    </form>

    <a href="acessar_nao_conformidade.php" class="back-link">⬅ Acessar não conformidades</a>
    <a href="acessar_escalabilidade.php?id_checklist=<?= $checklist['id'] ?>">Escalonamentos</a>
    <a href="index.php" class="back-link">⬅ Voltar ao Menu</a>
</div>

<script>
function mostrarNaoConformidade(selectElement, id_item) {
    const blocoNC = document.getElementById('nao-conformidade-' + id_item);
    const blocoNA = document.getElementById('nao-aplicavel-' + id_item);

    if (selectElement.value === 'Nao') {
        blocoNC.style.display = 'block';
        blocoNA.style.display = 'none';
    } else if (selectElement.value === 'Nao Aplicavel') {
        blocoNC.style.display = 'none';
        blocoNA.style.display = 'block';
    } else {
        blocoNC.style.display = 'none';
        blocoNA.style.display = 'none';
    }
}
</script>