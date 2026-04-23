<?php
/* ============================================================
   FAMILIA MANAGER — API DE FINANÇAS
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

switch ($action) {

    // ── Resumo do mês ─────────────────────────────────────────
    case 'summary':
        $mes = intval($_GET['mes'] ?? date('n'));
        $ano = intval($_GET['ano'] ?? date('Y'));

        $stmt = getDB()->prepare('SELECT
            SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) AS receitas,
            SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) AS despesas,
            COUNT(*) AS total
          FROM transacoes
          WHERE familia_id = ? AND MONTH(data) = ? AND YEAR(data) = ?');
        $stmt->execute([$familia_id, $mes, $ano]);
        $summary = $stmt->fetch();
        $summary['saldo'] = ($summary['receitas'] ?? 0) - ($summary['despesas'] ?? 0);
        jsonResponse(['success' => true, 'data' => $summary]);
        break;

    // ── Listar transações ─────────────────────────────────────
    case 'list':
        $mes  = intval($_GET['mes']  ?? date('n'));
        $ano  = intval($_GET['ano']  ?? date('Y'));
        $tipo = sanitize($_GET['tipo'] ?? '');

        $sql = 'SELECT t.*, u.nome AS usuario_nome FROM transacoes t
                JOIN usuarios u ON u.id = t.usuario_id
                WHERE t.familia_id = ? AND MONTH(t.data) = ? AND YEAR(t.data) = ?';
        $params = [$familia_id, $mes, $ano];
        if ($tipo) { $sql .= ' AND t.tipo = ?'; $params[] = $tipo; }
        $sql .= ' ORDER BY t.data DESC';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Criar transação ───────────────────────────────────────
    case 'create':
        $tipo      = sanitize($_POST['tipo'] ?? '');
        $descricao = sanitize($_POST['descricao'] ?? '');
        $valor     = floatval($_POST['valor'] ?? 0);
        $categoria = sanitize($_POST['categoria'] ?? '');
        $data      = sanitize($_POST['data'] ?? date('Y-m-d'));

        if (!in_array($tipo, ['receita','despesa']) || !$descricao || !$valor) {
            jsonResponse(['success' => false, 'message' => 'Dados inválidos.'], 400);
        }

        $stmt = getDB()->prepare('INSERT INTO transacoes (familia_id, usuario_id, tipo, descricao, valor, categoria, data) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$familia_id, $user_id, $tipo, $descricao, $valor, $categoria, $data]);
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId()]);
        break;

    // ── Excluir transação ─────────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM transacoes WHERE id = ? AND familia_id = ?');
        $stmt->execute([$id, $familia_id]);
        jsonResponse(['success' => true]);
        break;

    // ── Metas ─────────────────────────────────────────────────
    case 'metas':
        $stmt = getDB()->prepare('SELECT * FROM metas_financeiras WHERE familia_id = ? ORDER BY criado_em DESC');
        $stmt->execute([$familia_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'create_meta':
        $titulo     = sanitize($_POST['titulo'] ?? '');
        $valor_meta = floatval($_POST['valor_meta'] ?? 0);
        $valor_atual= floatval($_POST['valor_atual'] ?? 0);
        $prazo      = sanitize($_POST['prazo'] ?? '') ?: null;
        $icone      = sanitize($_POST['icone'] ?? '🎯');

        if (!$titulo || !$valor_meta) jsonResponse(['success' => false, 'message' => 'Dados inválidos.'], 400);

        $stmt = getDB()->prepare('INSERT INTO metas_financeiras (familia_id, titulo, valor_meta, valor_atual, prazo, icone) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$familia_id, $titulo, $valor_meta, $valor_atual, $prazo, $icone]);
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId()]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
