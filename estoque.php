<?php 
require_once 'config.php'; 
verificarLogin();

$mensagem = "";
$tipoMensagem = "sucesso";
$alertaEstoque = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $pid = (int)($_POST['produto_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? "";
    $qtd = (int)($_POST['quantidade'] ?? 0);
    $data = $_POST['data'] ?? "";

    if (!$pid || !$tipo || !$data || $qtd <= 0) {
        $mensagem = "Informe produto, tipo, data e quantidade válida.";
        $tipoMensagem = "erro";
    } else {
        $stmt = $conn->prepare("SELECT * FROM Produto WHERE Id = :id");
        $stmt->execute(['id' => $pid]);

        if (!$prod = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mensagem = "Produto não encontrado";
            $tipoMensagem = "erro";
        } else {
            $ant = (int)$prod['ESTOQUE_ATUAL'];
            $novo = $tipo === 'ENTRADA' ? $ant + $qtd : $ant - $qtd;

            if ($novo < 0) {
                $mensagem = "Estoque insuficiente";
                $tipoMensagem = "erro";
            } else {
                try {
                    $conn->beginTransaction();

                    $conn->prepare("INSERT INTO Movimentacoes (TIPO, DATA, QUANTIDADE, SALDO_ANTERIOR, Usuario_ID, Produto_Id) VALUES (:tipo, :data, :quantidade, :saldo_anterior, :usuarios_id, :produtos_id)")
                        ->execute([
                            'tipo' => $tipo === 'ENTRADA' ? 1 : 2,
                            'data' => $data,
                            'quantidade' => $qtd,
                            'saldo_anterior' => $ant,
                            'usuarios_id' => (int)$_SESSION['usuario_id'],
                            'produtos_id' => $pid,
                        ]);

                    $conn->prepare("UPDATE Produto SET ESTOQUE_ATUAL = :novo WHERE Id = :id")
                        ->execute(['novo' => $novo, 'id' => $pid]);

                    $conn->commit();

                    $mensagem = "Movimentação registrada com sucesso";
                    $tipoMensagem = "sucesso";

                    if ($tipo === 'SAIDA' && $novo <= (int)$prod['ESTOQUE_MINIMO']) {
                        $alertaEstoque = "ALERTA DE ESTOQUE BAIXO! O produto " . $prod['NOME'] . " está com estoque baixo. Atual: " . $novo . ", Mínimo: " . $prod['ESTOQUE_MINIMO'];
                        $mensagem .= " - " . $alertaEstoque;
                    }
                } catch (PDOException $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $mensagem = "Erro: " . $e->getMessage();
                    $tipoMensagem = "erro";
                }
            }
        }
    }
}
$produtos = $conn->query("SELECT 
    Id AS id,
    NOME AS nome,
    CATEGORIA AS categoria,
    PRECO AS preco,
    ESTOQUE_ATUAL AS estoque_atual,
    ESTOQUE_MINIMO AS estoque_minimo,
    ATIVO AS ativo
    FROM Produto
    WHERE ATIVO = 1
    ORDER BY NOME")->fetchAll();

$movimentacoes = $conn->query("SELECT 
    m.Id AS id,
    m.TIPO AS tipo,
    m.DATA AS data,
    m.QUANTIDADE AS quantidade,
    m.SALDO_ANTERIOR AS saldo_anterior,
    p.NOME AS produto_nome,
    u.NOME AS usuario_nome
    FROM Movimentacoes m
    LEFT JOIN Produto p ON p.Id = m.Produto_Id
    LEFT JOIN Usuario u ON u.ID = m.Usuario_ID
    ORDER BY m.Id DESC")->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Estoque - Industria</title>
</head>
<body>
    <h1>Gestão de Estoque - Industria</h1>
    <p><a href="index.php">⬅️Voltar</a></p>
    <hr> 

    <?php if (!empty($mensagem)): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($mensagem) ?>
        </p>
        <hr>
    <?php endif; ?>

    <?php if (!empty($alertaEstoque)): ?>
        <p style="color: gold;">
            <?= htmlspecialchars($alertaEstoque) ?>
        </p>
        <hr>
    <?php endif; ?>

    <h2>Nova Movimentação de Estoque</h2>
    <form method="post">
        <label>Produto:
            <select name="produto_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <br>
        <label>Tipo:
            <input type="radio" name="tipo" value="ENTRADA" required> ENTRADA
            <input type="radio" name="tipo" value="SAIDA" required> SAÍDA
        </label>
        <br>
        <label>Quantidade:
            <input type="number" name="quantidade" min="1" required>
        </label>
        <br>
        <label>Data:
            <input type="date" name="data" value="<?= date('Y-m-d') ?>" required>
        </label>
        <br>
        <br>
        <br>
        <button type="submit">Registrar Movimentação</button>
    </form>
        <h2>Lista de Produtos</h2>
        <?php if($produtos): ?>
            <table border="1">
             <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Aviso</th>
                </tr>
               
        <?php foreach($produtos as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['id'])?></td>
                <td><?= htmlspecialchars($p['nome'])?></td>
                <td><?= htmlspecialchars((string)$p['categoria'])?></td>
                <td><?= number_format($p['preco'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($p['estoque_atual'])?></td>
                <td><?= htmlspecialchars($p['estoque_minimo'])?></td>
                <td>
                    <?php if($p['estoque_atual'] <= $p['estoque_minimo']): ?>
                        <strong> AVISO: estoque baixo! </strong>
                    <?php else: ?>
                        Normal
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?> <p>Nenhum produto cadastrado.</p><?php endif; ?>

        <hr>

        <h2>Histórico de Movimentações</h2>
        <?php if($movimentacoes): ?>
            <table border="1">
                <tr>
                    <th>ID</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Data</th>
                    <th>Quantidade</th>
                    <th>Saldo Anterior</th>
                    <th>Usuário</th>
                </tr>
                <?php foreach($movimentacoes as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['produto_nome']) ?></td>
                        <td><?= $m['tipo'] == 1 ? 'ENTRADA' : 'SAÍDA' ?></td>
                        <td><?= date('d/m/Y', strtotime($m['data'])) ?></td>
                        <td><?= $m['quantidade'] ?></td>
                        <td><?= $m['saldo_anterior'] ?></td>
                        <td><?= htmlspecialchars($m['usuario_nome']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
                    <?php else: ?> <p>Nenhuma movimentação registrada.</p><?php endif; ?>
</body>
</html>
