<?php
/* ============================================================
   FAMILIA MANAGER — API DE NOTIFICAÇÕES
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

switch ($action) {

    // ── Listar notificações ───────────────────────────────────
    case 'list':
        $stmt = getDB()->prepare('SELECT * FROM notificacoes
                                  WHERE usuario_id = ?
                                  ORDER BY criado_em DESC
                                  LIMIT 50');
        $stmt->execute([$user_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Contar não lidas ──────────────────────────────────────
    case 'count':
        $stmt = getDB()->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0');
        $stmt->execute([$user_id]);
        jsonResponse(['success' => true, 'count' => intval($stmt->fetchColumn())]);
        break;

    // ── Marcar como lida ──────────────────────────────────────
    case 'read':
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = getDB()->prepare('UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?');
            $stmt->execute([$id, $user_id]);
        } else {
            $stmt = getDB()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?');
            $stmt->execute([$user_id]);
        }
        jsonResponse(['success' => true]);
        break;

    // ── Enviar notificação para família ───────────────────────
    case 'send':
        $titulo   = sanitize($_POST['titulo'] ?? '');
        $mensagem = sanitize($_POST['mensagem'] ?? '');
        $tipo     = sanitize($_POST['tipo'] ?? 'info');
        $icone    = sanitize($_POST['icone'] ?? '🔔');

        if (!$titulo) jsonResponse(['success' => false, 'message' => 'Informe o título.'], 400);

        // Buscar todos os membros da família
        $members = getDB()->prepare('SELECT id FROM usuarios WHERE familia_id = ? AND ativo = 1');
        $members->execute([$familia_id]);
        $ids = $members->fetchAll(PDO::FETCH_COLUMN);

        $stmt = getDB()->prepare('INSERT INTO notificacoes (usuario_id, familia_id, titulo, mensagem, tipo, icone) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($ids as $uid) {
            $stmt->execute([$uid, $familia_id, $titulo, $mensagem, $tipo, $icone]);
        }
        jsonResponse(['success' => true, 'sent' => count($ids)]);
        break;

    // ── Excluir notificação ───────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM notificacoes WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $user_id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
