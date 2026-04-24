<?php
// Puxa as configurações do sistema (ex: conexão com banco de dados)
require_once __DIR__ . '/../includes/config.php';

// Garante que apenas pessoas logadas possam acessar esse arquivo
requireLogin();

// Avisa ao navegador que a resposta deste arquivo será no formato JSON (padrão para APIs)
header('Content-Type: application/json; charset=utf-8');

// Descobre qual ação o usuário quer fazer. 
// O operador '??' tenta pegar do GET (URL). Se não existir, tenta do POST (Formulário). Se não achar, fica vazio ''.
$action     = $_GET['action'] ?? $_POST['action'] ?? '';

// Pega os dados do usuário atual direto da sessão (seguro, pois o usuário não pode forjar isso)
$familia_id = $_SESSION['familia_id'];
$user_id    = $_SESSION['user_id'];

// Um roteador simples: dependendo da 'action', ele executa um bloco de código diferente
switch ($action) {

    //  Listar missões (READ) ────────────────────────────────────────
    case 'list':
        // Limpa a entrada do status (ex: 'pendente', 'concluida')
        $status = sanitize($_GET['status'] ?? '');
        
        // Monta a consulta no banco (Query). 
        // A subquery '(SELECT COUNT...)' serve para saber quantas pessoas já concluíram esta missão.
        $sql = 'SELECT m.*,
                  (SELECT COUNT(*) FROM missoes_usuarios mu WHERE mu.missao_id = m.id AND mu.concluida = 1) AS concluidos_count
                FROM missoes m
                WHERE m.familia_id = ?'; // O '?' é um placeholder seguro
        
        $params = [$familia_id];
        
        // Se a pessoa pediu para filtrar por um status específico, adicionamos isso na consulta
        if ($status) { 
            $sql .= ' AND m.status = ?'; 
            $params[] = $status; 
        }
        $sql .= ' ORDER BY m.criado_em DESC'; // Ordena das mais novas para as mais velhas

        // O prepare() e execute() protegem contra "SQL Injection" (ataques hackers)
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        
        // Pega todos os resultados e devolve para o Javascript como JSON
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── 2. Criar missão (CREATE) ──────────────────────────────────────────
    case 'create':
        // Recebe e limpa os dados enviados pelo formulário (POST)
        $titulo     = sanitize($_POST['titulo'] ?? '');
        $descricao  = sanitize($_POST['descricao'] ?? '');
        $pontos     = intval($_POST['pontos'] ?? 10); // intval garante que será um número inteiro
        $icone      = sanitize($_POST['icone'] ?? '⭐');
        $dificuldade= sanitize($_POST['dificuldade'] ?? 'facil');
        $prazo      = sanitize($_POST['prazo'] ?? '') ?: null; // Se não tiver prazo, salva como NULL no banco

        // Validação básica: Não deixa criar missão sem título
        if (!$titulo) jsonResponse(['success' => false, 'message' => 'Informe o título.'], 400);

        // Insere a nova missão no banco de dados da família
        $stmt = getDB()->prepare('INSERT INTO missoes (familia_id, titulo, descricao, pontos, icone, dificuldade, prazo) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$familia_id, $titulo, $descricao, $pontos, $icone, $dificuldade, $prazo]);
        
        // Devolve sucesso e já retorna o ID da missão que acabou de ser criada
        jsonResponse(['success' => true, 'id' => getDB()->lastInsertId()]);
        break;

    //  Concluir missão (UPDATE/COMPLEX LOGIC) ─────────────────────────
    case 'complete':
        $missao_id = intval($_POST['missao_id'] ?? 0);

        $db = getDB();
        
         
        // se der erro no meio do caminho, o banco desfaz TUDO para não ficar inconsistente.
        $db->beginTransaction();
        
        try {
            // Marcar a missão como "concluída" na tabela principal
            $stmt = $db->prepare('UPDATE missoes SET status = "concluida" WHERE id = ? AND familia_id = ?');
            $stmt->execute([$missao_id, $familia_id]);

            // Registrar na tabela de histórico que ESTE usuário concluiu a missão (INSERT IGNORE evita duplicidade)
            $stmt2 = $db->prepare('INSERT IGNORE INTO missoes_usuarios (missao_id, usuario_id, concluida, concluida_em) VALUES (?, ?, 1, NOW())');
            $stmt2->execute([$missao_id, $user_id]);

            //  Buscar quantos pontos a missão valia
            $pts = $db->prepare('SELECT pontos FROM missoes WHERE id = ?');
            $pts->execute([$missao_id]);
            $pontos = $pts->fetchColumn(); // fetchColumn pega apenas aquele valor específico, não uma array inteira

            // Adicionar os pontos na carteira do usuário (pontos = pontos + X)
            $db->prepare('UPDATE usuarios SET pontos = pontos + ? WHERE id = ?')->execute([$pontos, $user_id]);

            //  Criar um alerta/notificação para o usuário comemorando os pontos
            $db->prepare('INSERT INTO notificacoes (usuario_id, familia_id, titulo, mensagem, tipo, icone)
                          VALUES (?, ?, ?, ?, "sucesso", "🏆")')
               ->execute([$user_id, $familia_id, 'Missão concluída!', "Você ganhou {$pontos} pontos!"]);

            // se der certo ele salva definitivamente as 5 operações no banco de dados.
            $db->commit();
            
            // Retorna sucesso e avisa ao Front-end quantos pontos foram ganhos para atualizar a tela
            jsonResponse(['success' => true, 'pontos' => $pontos]);
            
        } catch (Exception $e) {
            // se caso algo der errado, ele desfaz tudo que foi tentado (rollBack) para não corromper os dados
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Erro ao concluir missão.'], 500);
        }
        break;

    // ── 4. Excluir missão (DELETE) ────────────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        
        // Deleta a missão do banco. 
        // Nota de segurança: Ele verifica o "familia_id = ?" para garantir que um usuário não exclua a missão de OUTRA família.
        $stmt = getDB()->prepare('DELETE FROM missoes WHERE id = ? AND familia_id = ?');
        $stmt->execute([$id, $familia_id]);
        
        jsonResponse(['success' => true]);
        break;

    // ── Fallback ──────────────────────────────────────────────────────────
    // Se o JS mandar uma 'action' que não existe no switch (ex: 'update_name'), cai aqui
    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}