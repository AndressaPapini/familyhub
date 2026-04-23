<?php
/* ============================================================
   FAMILIA MANAGER — API DE COMPRAS
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

switch ($action) {

    // ── Listar listas ─────────────────────────────────────────
    case 'lists':
        $stmt = getDB()->prepare('SELECT l.*, u.nome AS criador_nome,
                                    (SELECT COUNT(*) FROM itens_compra WHERE lista_id = l.id) AS total_itens,
                                    (SELECT COUNT(*) FROM itens_compra WHERE lista_id = l.id AND comprado = 1) AS itens_comprados
                                  FROM listas_compras l
                                  JOIN usuarios u ON u.id = l.criado_por
                                  WHERE l.familia_id = ?
                                  ORDER BY l.criado_em DESC');
        $stmt->execute([$familia_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Criar lista ───────────────────────────────────────────
    case 'create_list':
        $nome = sanitize($_POST['nome'] ?? '');
        if (!$nome) jsonResponse(['success' => false, 'message' => 'Informe o nome da lista.'], 400);
        $stmt = getDB()->prepare('INSERT INTO listas_compras (familia_id, nome, criado_por) VALUES (?, ?, ?)');
        $stmt->execute([$familia_id, $nome, $user_id]);
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId()]);
        break;

    // ── Listar itens ──────────────────────────────────────────
    case 'items':
        $lista_id = intval($_GET['lista_id'] ?? 0);
        $stmt = getDB()->prepare('SELECT * FROM itens_compra WHERE lista_id = ? ORDER BY comprado ASC, criado_em ASC');
        $stmt->execute([$lista_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Adicionar item ────────────────────────────────────────
    case 'add_item':
        $lista_id  = intval($_POST['lista_id'] ?? 0);
        $nome      = sanitize($_POST['nome'] ?? '');
        $quantidade= floatval($_POST['quantidade'] ?? 1);
        $unidade   = sanitize($_POST['unidade'] ?? 'un');
        $categoria = sanitize($_POST['categoria'] ?? '');
        $preco     = floatval($_POST['preco'] ?? 0);

        if (!$nome || !$lista_id) jsonResponse(['success' => false, 'message' => 'Dados inválidos.'], 400);

        $stmt = getDB()->prepare('INSERT INTO itens_compra (lista_id, nome, quantidade, unidade, categoria, preco) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$lista_id, $nome, $quantidade, $unidade, $categoria, $preco ?: null]);
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId()]);
        break;

    // ── Marcar item como comprado ─────────────────────────────
    case 'toggle_item':
        $id = intval($_POST['id'] ?? 0);
        $stmt = getDB()->prepare('UPDATE itens_compra SET comprado = NOT comprado WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    // ── Excluir item ──────────────────────────────────────────
    case 'delete_item':
        $id = intval($_POST['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM itens_compra WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
