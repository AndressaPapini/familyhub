<?php
/* ============================================================
   FAMILIA MANAGER — API DE AUTENTICAÇÃO
   ============================================================ */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Login ─────────────────────────────────────────────────
    case 'login':
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!$email || !$senha) {
            jsonResponse(['success' => false, 'message' => 'Preencha todos os campos.'], 400);
        }

        try {
            $db   = getDB();
            $stmt = $db->prepare('SELECT u.*, f.nome AS familia_nome FROM usuarios u
                                  LEFT JOIN familias f ON f.id = u.familia_id
                                  WHERE u.email = ? AND u.ativo = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($senha, $user['senha'])) {
                jsonResponse(['success' => false, 'message' => 'E-mail ou senha incorretos.'], 401);
            }

            $_SESSION['user_id']      = $user['id'];
            $_SESSION['familia_id']   = $user['familia_id'];
            $_SESSION['user']         = [
                'id'           => $user['id'],
                'nome'         => $user['nome'],
                'email'        => $user['email'],
                'papel'        => $user['papel'],
                'avatar'       => $user['avatar'],
                'pontos'       => $user['pontos'],
                'familia_id'   => $user['familia_id'],
                'familia_nome' => $user['familia_nome'],
            ];

            jsonResponse(['success' => true, 'redirect' => '../pages/dashboard.html', 'user' => $_SESSION['user']]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno. Tente novamente.'], 500);
        }
        break;

    // ── Registro ──────────────────────────────────────────────
    case 'register':
        $nome          = sanitize($_POST['nome'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $senha         = $_POST['senha'] ?? '';
        $confirma      = $_POST['confirma'] ?? '';
        $familia_acao  = $_POST['familia_acao'] ?? 'criar'; // 'criar' ou 'entrar'
        $familia_nome  = sanitize($_POST['familia_nome'] ?? '');
        $familia_codigo= strtoupper(trim($_POST['familia_codigo'] ?? ''));

        if (!$nome || !$email || !$senha) {
            jsonResponse(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.'], 400);
        }
        if ($senha !== $confirma) {
            jsonResponse(['success' => false, 'message' => 'As senhas não coincidem.'], 400);
        }
        if (strlen($senha) < 6) {
            jsonResponse(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres.'], 400);
        }

        try {
            $db = getDB();

            // Verificar e-mail duplicado
            $check = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Este e-mail já está cadastrado.'], 409);
            }

            $familia_id = null;
            $papel      = 'membro';

            if ($familia_acao === 'criar') {
                if (!$familia_nome) {
                    jsonResponse(['success' => false, 'message' => 'Informe o nome da família.'], 400);
                }
                $codigo = strtoupper(substr(md5($familia_nome . time()), 0, 8));
                $ins = $db->prepare('INSERT INTO familias (nome, codigo) VALUES (?, ?)');
                $ins->execute([$familia_nome, $codigo]);
                $familia_id = $db->lastInsertId();
                $papel = 'admin';
            } elseif ($familia_acao === 'entrar') {
                if (!$familia_codigo) {
                    jsonResponse(['success' => false, 'message' => 'Informe o código da família.'], 400);
                }
                $fam = $db->prepare('SELECT id FROM familias WHERE codigo = ?');
                $fam->execute([$familia_codigo]);
                $famRow = $fam->fetch();
                if (!$famRow) {
                    jsonResponse(['success' => false, 'message' => 'Código de família inválido.'], 404);
                }
                $familia_id = $famRow['id'];
            }

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $ins2 = $db->prepare('INSERT INTO usuarios (familia_id, nome, email, senha, papel) VALUES (?, ?, ?, ?, ?)');
            $ins2->execute([$familia_id, $nome, $email, $hash, $papel]);

            jsonResponse(['success' => true, 'message' => 'Conta criada com sucesso! Faça login.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno. Tente novamente.'], 500);
        }
        break;

    // ── Logout ────────────────────────────────────────────────
    case 'logout':
        session_destroy();
        jsonResponse(['success' => true, 'redirect' => '../index.html']);
        break;

    // ── Verificar sessão ──────────────────────────────────────
    case 'check':
        if (isLoggedIn()) {
            jsonResponse(['logged' => true, 'user' => currentUser()]);
        } else {
            jsonResponse(['logged' => false]);
        }
        break;

    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}
