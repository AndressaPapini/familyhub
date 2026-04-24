<?php
/* ============================================================
   FAMILIA MANAGER — API DE NOTIFICAÇÕES
   ============================================================ */

// Importa as configurações gerais (conexão com o banco, funções úteis, etc.)
require_once __DIR__ . '/../includes/config.php';

// Proteção da rota: se o usuário não estiver logado, o script para por aqui
requireLogin();

// Define que a resposta desta página será sempre um JSON, que é o formato ideal para o Javascript ler
header('Content-Type: application/json; charset=utf-8');

// Pega a ação solicitada (via GET na URL ou POST via formulário)
$action     = $_GET['action'] ?? $_POST['action'] ?? '';

// Pega os dados de identificação do usuário diretamente da sessão (seguro)
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

// O switch funciona como um roteador: ele escolhe qual bloco de código rodar com base na variável $action
switch ($action) {

    // ── 1. Listar notificações (READ) ───────────────────────────────────
    case 'list':
        // Busca as notificações apenas do usuário logado
        // LIMIT 50: Traz apenas as 50 mais recentes para não pesar o banco de dados nem a tela do celular
        $stmt = getDB()->prepare('SELECT * FROM notificacoes
                                  WHERE usuario_id = ?
                                  ORDER BY criado_em DESC
                                  LIMIT 50');
        $stmt->execute([$user_id]);
        
        // Retorna a lista completa em formato JSON
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── 2. Contar não lidas ──────────────────────────────────────
    case 'count':
        // Conta quantas notificações existem onde a coluna "lida" é igual a 0 (falso)
        $stmt = getDB()->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0');
        $stmt->execute([$user_id]);
        
        // fetchColumn() pega apenas o número exato da contagem (ex: "3") e o intval garante que é um número inteiro
        jsonResponse(['success' => true, 'count' => intval($stmt->fetchColumn())]);
        break;

    // ── 3. Marcar como lida (UPDATE) ──────────────────────────────────────
    case 'read':
        $id = intval($_POST['id'] ?? 0);
        
        // Lógica condicional interessante aqui:
        if ($id) {
            // Se o Javascript enviou um ID específico, marca APENAS aquela notificação como lida (lida = 1)
            $stmt = getDB()->prepare('UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?');
            $stmt->execute([$id, $user_id]);
        } else {
            // Se NÃO enviou um ID, o usuário clicou em "Marcar todas como lidas". 
            // Então atualiza todas as notificações daquele usuário de uma vez só!
            $stmt = getDB()->prepare('UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?');
            $stmt->execute([$user_id]);
        }
        
        jsonResponse(['success' => true]);
        break;

    // ── 4. Enviar notificação para a família inteira (CREATE) ───────────────────────
    case 'send':
        // Recebe e limpa os textos para evitar injeção de código malicioso
        $titulo   = sanitize($_POST['titulo'] ?? '');
        $mensagem = sanitize($_POST['mensagem'] ?? '');
        $tipo     = sanitize($_POST['tipo'] ?? 'info');
        $icone    = sanitize($_POST['icone'] ?? '🔔');

        // Impede o envio de notificações sem título
        if (!$titulo) jsonResponse(['success' => false, 'message' => 'Informe o título.'], 400);

        // Passo A: Descobrir quem faz parte da família (e que está com a conta ativa)
        $members = getDB()->prepare('SELECT id FROM usuarios WHERE familia_id = ? AND ativo = 1');
        $members->execute([$familia_id]);
        
        // PDO::FETCH_COLUMN cria um array simples apenas com os IDs (ex: [1, 5, 8])
        $ids = $members->fetchAll(PDO::FETCH_COLUMN);

        // Passo B: Prepara a query de inserção UMA única vez (isso deixa o código bem mais rápido)
        $stmt = getDB()->prepare('INSERT INTO notificacoes (usuario_id, familia_id, titulo, mensagem, tipo, icone) VALUES (?, ?, ?, ?, ?, ?)');
        
        // Passo C: Faz um loop. Para cada ID de usuário encontrado, executa a inserção no banco
        foreach ($ids as $uid) {
            $stmt->execute([$uid, $familia_id, $titulo, $mensagem, $tipo, $icone]);
        }
        
        // Retorna sucesso e informa para quantas pessoas a notificação foi enviada
        jsonResponse(['success' => true, 'sent' => count($ids)]);
        break;

    // ── 5. Excluir notificação (DELETE) ───────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        
        // Deleta a notificação.
        // A trava de segurança "AND usuario_id = ?" garante que um espertinho não consiga 
        // deletar as notificações da esposa ou dos filhos mandando o ID da notificação deles.
        $stmt = getDB()->prepare('DELETE FROM notificacoes WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $user_id]);
        
        jsonResponse(['success' => true]);
        break;

    // ── Fallback ──────────────────────────────────────────────────────────
    // Retorno de erro caso o sistema peça uma ação que não foi programada acima
    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}