<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu de Auditoria</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header class="header">
        <h1>📋 Sistema de Auditoria - PUCPR</h1>
    </header>

    <main class="main-content">
        <section class="card">
            <h2>Selecione uma opção</h2>
            <div class="menu-options">
                <a href="checklist.php" class="option-btn">Criar Checklist</a>
                <a href="lista_checklist.php" class="option-btn">Ver Checklists</a>
                <a href="importar_checklist.php" class="option-btn">Importar Checklist</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>PUCPR - Engenharia de Software © <?= date("Y") ?></p>
    </footer>
</body>
</html>