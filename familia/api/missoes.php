<?php
/* ============================================================
   FAMILIA MANAGER — API DE MISSÕES
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$action     = $_GET['action'] ?? $_POST['action'] ?? '';
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

switch ($action) {

    // ── Listar missões ────────────────────────────────────────
    case 'list':
        $status = sanitize($_GET['status'] ?? '');
        $sql = 'SELECT m.*,
                  (SELECT COUNT(*) FROM missoes_usuarios mu WHERE mu.missao_id = m.id AND mu.concluida = 1) AS concluidos_count
                FROM missoes m
                WHERE m.familia_id = ?';
        $params = [$familia_id];
        if ($status) { $sql .= ' AND m.status = ?'; $params[] = $status; }
        $sql .= ' ORDER BY m.criado_em DESC';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Criar missão ──────────────────────────────────────────
    case 'create':
        $titulo     = sanitize($_POST['titulo'] ?? '');
        $descricao  = sanitize($_POST['descricao'] ?? '');
        $pontos     = intval($_POST['pontos'] ?? 10);
        $icone      = sanitize($_POST['icone'] ?? '⭐');
        $dificuldade= sanitize($_POST['dificuldade'] ?? 'facil');
        $prazo      = sanitize($_POST['prazo'] ?? '') ?: null;

        if (!$titulo) jsonResponse(['success' => false, 'message' => 'Informe o título.'], 400);

        $stmt = getDB()->prepare('INSERT INTO missoes (familia_id, titulo, descricao, pontos, icone, dificuldade, prazo) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$familia_id, $titulo, $descricao, $pontos, $icone, $dificuldade, $prazo]);
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId()]);
        break;

    // ── Concluir missão ───────────────────────────────────────
    case 'complete':
        $missao_id = intval($_POST['missao_id'] ?? 0);

        $db = getDB();
        $db->beginTransaction();
        try {
            // Marcar missão como concluída
            $stmt = $db->prepare('UPDATE missoes SET status = "concluida" WHERE id = ? AND familia_id = ?');
            $stmt->execute([$missao_id, $familia_id]);

            // Registrar conclusão do usuário
            $stmt2 = $db->prepare('INSERT IGNORE INTO missoes_usuarios (missao_id, usuario_id, concluida, concluida_em) VALUES (?, ?, 1, NOW())');
            $stmt2->execute([$missao_id, $user_id]);

            // Buscar pontos da missão
            $pts = $db->prepare('SELECT pontos FROM missoes WHERE id = ?');
            $pts->execute([$missao_id]);
            $pontos = $pts->fetchColumn();

            // Adicionar pontos ao usuário
            $db->prepare('UPDATE usuarios SET pontos = pontos + ? WHERE id = ?')->execute([$pontos, $user_id]);

            // Criar notificação
            $db->prepare('INSERT INTO notificacoes (usuario_id, familia_id, titulo, mensagem, tipo, icone)
                          VALUES (?, ?, ?, ?, "sucesso", "🏆")')
               ->execute([$user_id, $familia_id, 'Missão concluída!', "Você ganhou {$pontos} pontos!"]);

            $db->commit();
            jsonResponse(['success' => true, 'pontos' => $pontos]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Erro ao concluir missão.'], 500);
        }
        break;

    // ── Excluir missão ────────────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM missoes WHERE id = ? AND familia_id = ?');
        $stmt->execute([$id, $familia_id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
