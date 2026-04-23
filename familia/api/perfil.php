<?php
/* ============================================================
   FAMILIA MANAGER — API DE PERFIL
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {

    // ── Obter dados do perfil ─────────────────────────────────
    case 'get':
        $stmt = getDB()->prepare('SELECT u.id, u.nome, u.email, u.telefone, u.data_nascimento,
                                    u.bio, u.avatar, u.cor_perfil, u.pontos, u.papel, u.criado_em,
                                    f.nome AS familia_nome, f.codigo AS familia_codigo
                                  FROM usuarios u
                                  JOIN familias f ON f.id = u.familia_id
                                  WHERE u.id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) jsonResponse(['success' => false, 'message' => 'Usuário não encontrado.'], 404);
        unset($user['senha']);
        jsonResponse(['success' => true, 'data' => $user]);
        break;

    // ── Atualizar perfil ──────────────────────────────────────
    case 'update':
        $nome          = sanitize($_POST['nome'] ?? '');
        $telefone      = sanitize($_POST['telefone'] ?? '');
        $data_nasc     = sanitize($_POST['data_nascimento'] ?? '') ?: null;
        $bio           = sanitize($_POST['bio'] ?? '');
        $avatar        = sanitize($_POST['avatar'] ?? '');
        $cor_perfil    = sanitize($_POST['cor_perfil'] ?? '');

        if (!$nome) jsonResponse(['success' => false, 'message' => 'Nome é obrigatório.'], 400);

        $stmt = getDB()->prepare('UPDATE usuarios SET nome = ?, telefone = ?, data_nascimento = ?, bio = ?, avatar = ?, cor_perfil = ? WHERE id = ?');
        $stmt->execute([$nome, $telefone, $data_nasc, $bio, $avatar, $cor_perfil, $user_id]);

        $_SESSION['user_nome'] = $nome;
        jsonResponse(['success' => true, 'message' => 'Perfil atualizado!']);
        break;

    // ── Alterar senha ─────────────────────────────────────────
    case 'change_password':
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha  = $_POST['nova_senha']  ?? '';
        $confirmacao = $_POST['confirmacao'] ?? '';

        if (!$senha_atual || !$nova_senha || !$confirmacao) {
            jsonResponse(['success' => false, 'message' => 'Preencha todos os campos.'], 400);
        }
        if ($nova_senha !== $confirmacao) {
            jsonResponse(['success' => false, 'message' => 'As senhas não coincidem.'], 400);
        }
        if (strlen($nova_senha) < 6) {
            jsonResponse(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'], 400);
        }

        $stmt = getDB()->prepare('SELECT senha FROM usuarios WHERE id = ?');
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($senha_atual, $hash)) {
            jsonResponse(['success' => false, 'message' => 'Senha atual incorreta.'], 401);
        }

        $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        getDB()->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')->execute([$novo_hash, $user_id]);
        jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso!']);
        break;

    // ── Membros da família ────────────────────────────────────
    case 'members':
        $familia_id = $_SESSION['familia_id'];
        $stmt = getDB()->prepare('SELECT id, nome, email, papel, pontos, avatar, criado_em FROM usuarios WHERE familia_id = ? AND ativo = 1 ORDER BY pontos DESC');
        $stmt->execute([$familia_id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Remover membro ────────────────────────────────────────
    case 'remove_member':
        $familia_id  = $_SESSION['familia_id'];
        $membro_id   = intval($_POST['membro_id'] ?? 0);

        // Só admin pode remover
        $stmt = getDB()->prepare('SELECT papel FROM usuarios WHERE id = ?');
        $stmt->execute([$user_id]);
        $papel = $stmt->fetchColumn();

        if ($papel !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Sem permissão.'], 403);
        }
        if ($membro_id === $user_id) {
            jsonResponse(['success' => false, 'message' => 'Você não pode remover a si mesmo.'], 400);
        }

        $stmt = getDB()->prepare('UPDATE usuarios SET ativo = 0 WHERE id = ? AND familia_id = ?');
        $stmt->execute([$membro_id, $familia_id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
