<?php
require 'vendor/autoload.php';
require 'conexao.php';
$conn = conecta_db();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("❌ Requisição inválida!");
}

$id_nc = isset($_POST['id_nc']) ? intval($_POST['id_nc']) : 0;
$destinatario = isset($_POST['destinatario']) ? trim($_POST['destinatario']) : '';

if ($id_nc <= 0 || empty($destinatario)) {
    die("❌ Todos os campos são obrigatórios!");
}

// Buscar dados da NC e Checklist com o último escalonamento
$sql = "
    SELECT 
        nc.id AS id_nc,
        nc.descricao AS descricao_nc,
        nc.estado,
        nc.prioridade,
        nc.data_criacao,
        i.descricao AS descricao_item,
        c.nome AS nome_checklist,
        p.nome AS classificacao,
        e.responsavel AS responsavel_db,
        P.dias AS prazo_db
    FROM naoConformidade nc
    INNER JOIN Item i ON nc.id_item = i.id
    INNER JOIN Item_checklist ic ON i.id = ic.id_item
    INNER JOIN Checklist c ON ic.id_checklist = c.id
    LEFT JOIN (
        SELECT e1.*
        FROM Escalonamento e1
        INNER JOIN (
            SELECT id_nc, MAX(prazo) AS max_prazo
            FROM Escalonamento
            GROUP BY id_nc
        ) e2 ON e1.id_nc = e2.id_nc AND e1.prazo = e2.max_prazo
    ) e ON e.id_nc = nc.id
    LEFT JOIN Prazo p ON p.id = nc.id_prazo
    WHERE nc.id = ?
    LIMIT 1
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_nc);
$stmt->execute();
$result = $stmt->get_result();
$nc = $result->fetch_assoc();
$stmt->close();

if (!$nc) die("❌ Não conformidade não encontrada!");

// Prioridade de valores: POST (form) -> banco (Escalonamento / NC) -> fallback
$responsavel = !empty($_POST['responsavel']) ? trim($_POST['responsavel']) : ($nc['responsavel_db'] ?? 'Não definido');
$rqa = !empty($_POST['rqa']) ? trim($_POST['rqa']) : ($nc['prioridade'] ?? '---'); // se você tiver coluna específica, ajuste aqui
$acao_corretiva = !empty($_POST['acao']) ? trim($_POST['acao']) : ($nc['prioridade'] ?? '---');
$prazo = !empty($_POST['prazo']) ? trim($_POST['prazo']) : ($nc['prazo_db'] ?? null);

// Se o prazo veio em formato YYYY-MM-DD (input type=date), converte para DATETIME
if (!empty($prazo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $prazo)) {
    $prazo = $prazo . ' 23:59:59';
}

// Monta assunto e corpo (HTML)
$remetente = 'checklistes1@gmail.com';
$assunto = "Solicitação de Resolução de Não Conformidade #".$nc['id_nc'];

$mensagem = "
<html>
<head><meta charset='utf-8'></head>
<body>
📋 <strong>Solicitação de Resolução de Não Conformidade</strong><br><br>

✅ <strong>Projeto:</strong> ".htmlspecialchars($nc['nome_checklist'])."<br>
📅 <strong>Data da Solicitação:</strong> ".htmlspecialchars($nc['data_criacao'])."<br>
👤 <strong>Responsável:</strong> ".htmlspecialchars($responsavel)."<br>
📌 <strong>RQA Responsável:</strong> ".htmlspecialchars($rqa)."<br>
⏰ <strong>Prazo de Resolução:</strong> ".htmlspecialchars($prazo ?? 'Não definido')."<br>
📈 <strong>Estado:</strong> ".htmlspecialchars($nc['estado'])."<br>
📝 <strong>Item de Checklist:</strong> ".htmlspecialchars($nc['descricao_item'])."<br>
📝 <strong>Descrição da NC:</strong> ".htmlspecialchars($nc['descricao_nc'])."<br>
🏷 <strong>Classificação:</strong> ".htmlspecialchars($nc['classificacao'] ?? 'Não definida')("Dias: " htmlspecialchars($prazo['prazo_db']))."<br>
⚙ <strong>Ação Corretiva Indicada:</strong> ".htmlspecialchars($acao_corretiva)."<br>
</body>
</html>
";

// Envio via PHPMailer (cria o objeto ANTES de usar métodos)
$mail = new PHPMailer(true);
try {
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $remetente;
    $mail->Password = 'udtj zrfs cemz dqua'; // sua app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom($remetente, 'Auditoria');
    $mail->addAddress($destinatario);

    $mail->isHTML(true);
    $mail->Subject = $assunto;
    $mail->Body    = $mensagem;

    $mail->send();

    // Salvar envio no banco
    $stmt = $conn->prepare("INSERT INTO Email (id_nc, email_destinatario, email_remetente) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_nc, $destinatario, $remetente);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    header("Location: acessar_nao_conformidade.php?msg=" . urlencode("✅ Email enviado com sucesso!"));
    exit;
} catch (Exception $e) {
    // Em caso de erro, dá informação útil
    die("❌ Erro ao enviar email: " . $mail->ErrorInfo);
}
?>
