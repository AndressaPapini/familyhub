<?php
/* ============================================================
   FAMILIA MANAGER — API DE AGENDA
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

switch ($action) {

    // ── Listar eventos ────────────────────────────────────────
    case 'list':
        $mes  = intval($_GET['mes']  ?? date('n'));
        $ano  = intval($_GET['ano']  ?? date('Y'));
        $tipo = sanitize($_GET['tipo'] ?? '');

        $sql = 'SELECT e.*, u.nome AS criador_nome
                FROM eventos e
                JOIN usuarios u ON u.id = e.criador_id
                WHERE e.familia_id = ?
                  AND MONTH(e.data_inicio) = ?
                  AND YEAR(e.data_inicio)  = ?';
        $params = [$familia_id, $mes, $ano];

        if ($tipo) { $sql .= ' AND e.tipo = ?'; $params[] = $tipo; }
        $sql .= ' ORDER BY e.data_inicio ASC';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Criar evento ──────────────────────────────────────────
    case 'create':
        $titulo     = sanitize($_POST['titulo'] ?? '');
        $descricao  = sanitize($_POST['descricao'] ?? '');
        $data_inicio= sanitize($_POST['data_inicio'] ?? '');
        $data_fim   = sanitize($_POST['data_fim'] ?? '') ?: null;
        $local      = sanitize($_POST['local'] ?? '');
        $cor        = sanitize($_POST['cor'] ?? '#6C63FF');
        $tipo       = sanitize($_POST['tipo'] ?? 'evento');

        if (!$titulo || !$data_inicio) {
            jsonResponse(['success' => false, 'message' => 'Título e data são obrigatórios.'], 400);
        }

        $stmt = getDB()->prepare('INSERT INTO eventos (familia_id, criador_id, titulo, descricao, data_inicio, data_fim, local, cor, tipo)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$familia_id, $user_id, $titulo, $descricao, $data_inicio, $data_fim, $local, $cor, $tipo]);
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId(), 'message' => 'Evento criado!']);
        break;

    // ── Excluir evento ────────────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM eventos WHERE id = ? AND familia_id = ?');
        $stmt->execute([$id, $familia_id]);
        jsonResponse(['success' => true, 'message' => 'Evento excluído.']);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
