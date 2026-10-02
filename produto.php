<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = '';

// Exclusão
if (isset($_GET['excluir'])) {
    try {
        $conn->prepare("DELETE FROM Produto WHERE Id = ?")->execute([(int)$_GET['excluir']]);
        $mensagem = 'Produto Excluído com Sucesso.'; $tipoMensagem = 'sucesso';
    } catch (PDOException $e) { $mensagem = 'Erro ao excluir: ' . $e->getMessage(); $tipoMensagem = 'erro'; }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'codigo'               => trim($_POST['codigo'] ?? ''),
        'nome'                 => trim($_POST['nome'] ?? ''),
        'preco'                => floatval($_POST['preco'] ?? 0),
        'estoque_atual'        => (int)($_POST['estoque_atual'] ?? 0),
        'estoque_minimo'       => (int)($_POST['estoque_minimo'] ?? 0),
        'ativo'                => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1,
        'peso'                 => floatval($_POST['peso'] ?? 0),
        'porte_produto'        => (int)($_POST['porte_produto'] ?? 0),
        'tipo_especie'         => (int)($_POST['tipo_especie'] ?? 0),
        'categoria'            => trim($_POST['categoria'] ?? ''),
        'fabricante'           => trim($_POST['fabricante'] ?? ''),
        'tamanho'              => trim($_POST['tamanho'] ?? ''),
        'material_fabricacao'  => trim($_POST['material_fabricacao'] ?? ''),
    ];

    if (!$data['codigo'] || !$data['nome'] || $data['preco'] <= 0) {
        $mensagem = 'Preencha todos os campos obrigatórios!'; $tipoMensagem = 'erro';
    } else {
        try {
            if ($id > 0) {
                $sql = "UPDATE Produto SET 
                    CODIGO = :codigo,
                    NOME = :nome,
                    PRECO = :preco,
                    ESTOQUE_ATUAL = :estoque_atual,
                    ESTOQUE_MINIMO = :estoque_minimo,
                    ATIVO = :ativo,
                    PESO = :peso,
                    PORTE_PRODUTO = :porte_produto,
                    TIPO_ESPECIE = :tipo_especie,
                    CATEGORIA = :categoria,
                    FABRICANTE = :fabricante,
                    TAMANHO = :tamanho,
                    MATERIAL_FABRICACAO = :material_fabricacao
                    WHERE Id = :id";

                $data['id'] = $id;
            } else {
                $sql = "INSERT INTO Produto (
                    CODIGO,
                    NOME,
                    PRECO,
                    ESTOQUE_ATUAL,
                    ESTOQUE_MINIMO,
                    ATIVO,
                    PESO,
                    PORTE_PRODUTO,
                    TIPO_ESPECIE,
                    CATEGORIA,
                    FABRICANTE,
                    TAMANHO,
                    MATERIAL_FABRICACAO
                ) VALUES (
                    :codigo,
                    :nome,
                    :preco,
                    :estoque_atual,
                    :estoque_minimo,
                    :ativo,
                    :peso,
                    :porte_produto,
                    :tipo_especie,
                    :categoria,
                    :fabricante,
                    :tamanho,
                    :material_fabricacao
                )";
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($data);

            $mensagem = $id > 0 ? 'Produto atualizado!' : 'Produto cadastrado!';
            $tipoMensagem = 'sucesso';
        } catch (PDOException $e) {
            $mensagem = 'Erro: ' . $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}

// Buscar produto para edição
$produtoEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT 
        Id AS id,
        CODIGO AS codigo,
        NOME AS nome,
        PRECO AS preco,
        ESTOQUE_ATUAL AS estoque_atual,
        ESTOQUE_MINIMO AS estoque_minimo,
        ATIVO AS ativo,
        PESO AS peso,
        PORTE_PRODUTO AS porte_produto,
        TIPO_ESPECIE AS tipo_especie,
        CATEGORIA AS categoria,
        FABRICANTE AS fabricante,
        TAMANHO AS tamanho,
        MATERIAL_FABRICACAO AS material_fabricacao
        FROM Produto WHERE Id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $produtoEditar = $stmt->fetch();
}

// Listar
$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT 
    Id AS id,
    CODIGO AS codigo,
    NOME AS nome,
    PRECO AS preco,
    ESTOQUE_ATUAL AS estoque_atual,
    ESTOQUE_MINIMO AS estoque_minimo,
    ATIVO AS ativo,
    PESO AS peso,
    PORTE_PRODUTO AS porte_produto,
    TIPO_ESPECIE AS tipo_especie,
    CATEGORIA AS categoria,
    FABRICANTE AS fabricante,
    TAMANHO AS tamanho,
    MATERIAL_FABRICACAO AS material_fabricacao
    FROM Produto WHERE ATIVO = 1" . ($busca ? " AND (NOME LIKE :nome OR CODIGO LIKE :codigo OR CATEGORIA LIKE :categoria)" : "") . " ORDER BY NOME";
$stmt = $conn->prepare($sql);
$parametrosBusca = [];
if ($busca) {
    $termoBusca = "%$busca%";
    $parametrosBusca = [
        'nome' => $termoBusca,
        'codigo' => $termoBusca,
        'categoria' => $termoBusca,
    ];
}
$stmt->execute($parametrosBusca);
$produtos = $stmt->fetchAll();

$tipos_especie = [1 => 'Tubo', 2 => 'Barra', 3 => 'Chapa'];
$porte_produtos = [1 => 'Compacto', 2 => 'Leve', 3 => 'Pesado'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produtos - Pet Shop</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Cadastro de Produtos - Pet Shop</h1>
    <p><a href="index.php">⬅️Voltar</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>"><?= htmlspecialchars($mensagem) ?></p>
        <hr>
    <?php endif; ?>

    <h2><?= $produtoEditar ? 'Editar' : 'Novo' ?> Produto</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $produtoEditar['id'] ?? 0 ?>">
        <label>Código: * <input type="text" name="codigo" value="<?= htmlspecialchars($produtoEditar['codigo'] ?? '') ?>" required></label><br>
        <label>Nome: * <input type="text" name="nome" size="40" value="<?= htmlspecialchars($produtoEditar['nome'] ?? '') ?>" required></label><br>
        <label>Preço de Venda: * <input type="number" step="0.01" name="preco" value="<?= htmlspecialchars($produtoEditar['preco'] ?? '') ?>" required></label><br>
        <label>Estoque Atual: <input type="number" name="estoque_atual" min="0" value="<?= $produtoEditar['estoque_atual'] ?? 0 ?>"></label><br>
        <label>Estoque Mínimo: <input type="number" name="estoque_minimo" min="0" value="<?= $produtoEditar['estoque_minimo'] ?? 5 ?>"></label><br>
        <label>Status (Ativo): 
            <select name="ativo">
                <option value="1" <?= ($produtoEditar['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>1 - Ativo</option>
                <option value="0" <?= ($produtoEditar['ativo'] ?? 1) == 0 ? 'selected' : '' ?>>0 - Inativo</option>
            </select>
        </label><br>
        <label>Peso (kg): <input type="number" step="0.01" name="peso" value="<?= $produtoEditar['peso'] ?? 0.00 ?>"></label><br>
        <label>Espécie (Flag): 
            <select name="tipo_especie">
                <option value="">Selecione...</option>
                <option value="1" <?= ($produtoEditar['tipo_especie'] ?? '') == 1 ? 'selected' : '' ?>>1 - Tubo</option>
                <option value="2" <?= ($produtoEditar['tipo_especie'] ?? '') == 2 ? 'selected' : '' ?>>2 - Barra</option>
                <option value="3" <?= ($produtoEditar['tipo_especie'] ?? '') == 3 ? 'selected' : '' ?>>3 - Chapa</option>
            </select>
        </label><br>
        <label>Porte do Produto (Flag): 
            <select name="porte_produto">
                <option value="">Selecione...</option>
                <option value="1" <?= ($produtoEditar['porte_produto'] ?? '') == 1 ? 'selected' : '' ?>>1 - Compacta</option>
                <option value="2" <?= ($produtoEditar['porte_produto'] ?? '') == 2 ? 'selected' : '' ?>>2 - Leve</option>
                <option value="3" <?= ($produtoEditar['porte_produto'] ?? '') == 3 ? 'selected' : '' ?>>3 - Pesado</option>
            </select>
        </label><br>
        <label>Categoria: <input type="text" name="categoria" size="40" value="<?= htmlspecialchars($produtoEditar['categoria'] ?? '') ?>"></label><br>
        <label>Tamanho: <input type="text" name="tamanho" value="<?= htmlspecialchars($produtoEditar['tamanho'] ?? '') ?>"></label><br>
        <label>Material de Fabricação: <input type="text" name="material_fabricacao" value="<?= htmlspecialchars($produtoEditar['material_fabricacao'] ?? '') ?>"></label><br>
        <button type="submit">Salvar</button>
        <?php if ($produtoEditar): ?><a href="produto.php"><button type="button">Cancelar</button></a><?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Produtos</h2>
    <form method="GET">
        <label>Buscar <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome, Código ou Categoria..."></label>
        <button type="submit">Buscar</button>
        <?php if ($busca): ?><a href="produto.php">Limpar</a><?php endif; ?>
    </form>
    <br>

    <?php if ($produtos): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Mín.</th>
                <th>Peso</th>
                <th>Espécie</th>
                <th>Porte</th>
                <th>Tamanho</th>
                <th>Material</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                    <td><?= $p['estoque_atual'] ?></td>
                    <td><?= $p['estoque_minimo'] ?></td>
                    <td><?= $p['peso'] ?> kg</td>
                    <td><?= $tipos_especie[$p['tipo_especie']] ?? '-' ?></td>
                    <td><?= $porte_produtos[$p['porte_produto']] ?? '-' ?></td>
                    <td><?= htmlspecialchars($p['tamanho'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['material_fabricacao'] ?? '-') ?></td>
                    <td>
                        <a href="produto.php?editar=<?= $p['id'] ?>">Editar</a>
                        <a href="produto.php?excluir=<?= $p['id'] ?>" onclick="return confirm('Deseja realmente excluir este produto?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum produto cadastrado.</p>
    <?php endif; ?>
</body>
</html>