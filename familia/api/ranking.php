<?php
/* ============================================================
   FAMILIA MANAGER — API DE RANKING
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

switch ($action) {

    // ── Ranking da família ────────────────────────────────────
    case 'list':
        $stmt = getDB()->prepare('SELECT u.id, u.nome, u.avatar, u.pontos, u.papel,
                                    (SELECT COUNT(*) FROM missoes_usuarios mu WHERE mu.usuario_id = u.id AND mu.concluida = 1) AS missoes_concluidas
                                  FROM usuarios u
                                  WHERE u.familia_id = ? AND u.ativo = 1
                                  ORDER BY u.pontos DESC');
        $stmt->execute([$familia_id]);
        $members = $stmt->fetchAll();

        // Adicionar posição
        foreach ($members as $i => &$m) {
            $m['posicao'] = $i + 1;
        }

        jsonResponse(['success' => true, 'data' => $members]);
        break;

    // ── Minha posição ─────────────────────────────────────────
    case 'my_rank':
        $stmt = getDB()->prepare('SELECT COUNT(*) + 1 AS posicao FROM usuarios
                                  WHERE familia_id = ? AND pontos > (SELECT pontos FROM usuarios WHERE id = ?)');
        $stmt->execute([$familia_id, $user_id]);
        $pos = $stmt->fetchColumn();

        $stmt2 = getDB()->prepare('SELECT pontos FROM usuarios WHERE id = ?');
        $stmt2->execute([$user_id]);
        $pts = $stmt2->fetchColumn();

        jsonResponse(['success' => true, 'posicao' => intval($pos), 'pontos' => intval($pts)]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
