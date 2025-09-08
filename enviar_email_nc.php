<?php
require 'vendor/autoload.php'; // ou require PHPMailer manual

require 'conexao.php';
$conn = conecta_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_nc = $_POST['id_nc'] ?? null;
    $destinatario = $_POST['destinatario'] ?? '';
    $remetente = 'checklistes1@gmail.com'; // AQUI VAI SEU EMAIL
    $assunto = $_POST['assunto'] ?? 'Não Conformidade';
    $mensagem = $_POST['mensagem'] ?? '';

    if (empty($destinatario) || empty($id_nc)) {
        die("❌ Todos os campos são obrigatórios!");
    }

    // Salvar no banco
    $stmt = $conn->prepare("INSERT INTO Email (id_nc, email_destinatario, email_remetente) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_nc, $destinatario, $remetente);
    $stmt->execute();
    $stmt->close();

    // Envio via PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = "checklistes1@gmail.com"; // AQUI TAMBEM VAI SEU EMAIL
        $mail->Password = 'udtj zrfs cemz dqua'; //AQUI VAI A SENHA DO APP OU CHAVE
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('checklistes1@gmail.com', 'Auditoria'); // igual ao Username
        $mail->addAddress($destinatario);

        $mail->Subject = $assunto;
        $mail->Body    = $mensagem;

        $mail->send();
        header("Location: acessar_nao_conformidade.php?msg=" . urlencode("✅ Email enviado com sucesso!"));
    } catch (Exception $e) {
        die("❌ Erro ao enviar email: {$mail->ErrorInfo}");
    }

    $conn->close();
}
?>
