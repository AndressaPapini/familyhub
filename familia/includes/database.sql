-- ============================================================
--  FAMILIA MANAGER — SCHEMA DO BANCO DE DADOS
-- ============================================================

CREATE DATABASE IF NOT EXISTS familia_manager
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE familia_manager;

-- ── Famílias ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS familias (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL,
  codigo      VARCHAR(10)  NOT NULL UNIQUE,
  descricao   TEXT,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Usuários ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id  INT UNSIGNED,
  nome        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  senha       VARCHAR(255) NOT NULL,
  papel       ENUM('admin','membro') DEFAULT 'membro',
  avatar      VARCHAR(255),
  pontos         INT UNSIGNED DEFAULT 0,
  telefone       VARCHAR(20),
  data_nascimento DATE,
  bio            TEXT,
  cor_perfil     VARCHAR(30),
  ativo          TINYINT(1) DEFAULT 1,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Eventos / Agenda ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS eventos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id  INT UNSIGNED NOT NULL,
  criador_id  INT UNSIGNED NOT NULL,
  titulo      VARCHAR(200) NOT NULL,
  descricao   TEXT,
  data_inicio DATETIME NOT NULL,
  data_fim    DATETIME,
  local       VARCHAR(200),
  cor         VARCHAR(7) DEFAULT '#6C63FF',
  tipo        ENUM('evento','aniversario','compromisso','reuniao','outro') DEFAULT 'evento',
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE,
  FOREIGN KEY (criador_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Lista de Compras ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS listas_compras (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id  INT UNSIGNED NOT NULL,
  nome        VARCHAR(150) NOT NULL,
  criado_por  INT UNSIGNED NOT NULL,
  concluida   TINYINT(1) DEFAULT 0,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS itens_compra (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lista_id    INT UNSIGNED NOT NULL,
  nome        VARCHAR(200) NOT NULL,
  quantidade  DECIMAL(10,2) DEFAULT 1,
  unidade     VARCHAR(20) DEFAULT 'un',
  categoria   VARCHAR(50),
  preco       DECIMAL(10,2),
  comprado    TINYINT(1) DEFAULT 0,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lista_id) REFERENCES listas_compras(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Finanças ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS transacoes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id  INT UNSIGNED NOT NULL,
  usuario_id  INT UNSIGNED NOT NULL,
  tipo        ENUM('receita','despesa') NOT NULL,
  descricao   VARCHAR(200) NOT NULL,
  valor       DECIMAL(12,2) NOT NULL,
  categoria   VARCHAR(80),
  data        DATE NOT NULL,
  recorrente  TINYINT(1) DEFAULT 0,
  observacao  TEXT,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS metas_financeiras (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id  INT UNSIGNED NOT NULL,
  titulo      VARCHAR(150) NOT NULL,
  valor_meta  DECIMAL(12,2) NOT NULL,
  valor_atual DECIMAL(12,2) DEFAULT 0,
  prazo       DATE,
  icone       VARCHAR(10) DEFAULT '🎯',
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Notificações ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notificacoes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NOT NULL,
  familia_id  INT UNSIGNED,
  titulo      VARCHAR(200) NOT NULL,
  mensagem    TEXT,
  tipo        ENUM('info','sucesso','aviso','erro') DEFAULT 'info',
  icone       VARCHAR(10) DEFAULT '🔔',
  lida        TINYINT(1) DEFAULT 0,
  link        VARCHAR(255),
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Missões ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS missoes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id  INT UNSIGNED NOT NULL,
  titulo      VARCHAR(200) NOT NULL,
  descricao   TEXT,
  pontos      INT UNSIGNED DEFAULT 10,
  icone       VARCHAR(10) DEFAULT '⭐',
  dificuldade ENUM('facil','medio','dificil') DEFAULT 'facil',
  prazo       DATE,
  status      ENUM('ativa','concluida','cancelada') DEFAULT 'ativa',
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS missoes_usuarios (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  missao_id   INT UNSIGNED NOT NULL,
  usuario_id  INT UNSIGNED NOT NULL,
  concluida   TINYINT(1) DEFAULT 0,
  concluida_em DATETIME,
  UNIQUE KEY uq_missao_usuario (missao_id, usuario_id),
  FOREIGN KEY (missao_id) REFERENCES missoes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Dados de exemplo ─────────────────────────────────────────
INSERT IGNORE INTO familias (nome, codigo, descricao) VALUES
  ('Família Silva', 'SILVA2024', 'Nossa família unida!');

INSERT IGNORE INTO usuarios (familia_id, nome, email, senha, papel, pontos) VALUES
  (1, 'João Silva',   'joao@familia.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  320),
  (1, 'Maria Silva',  'maria@familia.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'membro', 280),
  (1, 'Pedro Silva',  'pedro@familia.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'membro', 195),
  (1, 'Ana Silva',    'ana@familia.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'membro', 150);
-- Senha padrão: password

INSERT IGNORE INTO eventos (familia_id, criador_id, titulo, descricao, data_inicio, data_fim, local, cor, tipo) VALUES
  (1, 1, 'Aniversário da Maria',   'Festa de 40 anos!',          '2026-05-15 18:00:00', '2026-05-15 23:00:00', 'Casa',         '#FF6584', 'aniversario'),
  (1, 1, 'Reunião Familiar',       'Planejamento do mês',        '2026-04-20 19:00:00', '2026-04-20 21:00:00', 'Sala de estar','#6C63FF', 'reuniao'),
  (1, 2, 'Consulta médica Pedro',  'Pediatra Dr. Carlos',        '2026-04-22 10:00:00', NULL,                  'Clínica São Lucas','#43C6AC','compromisso'),
  (1, 1, 'Churrasco de domingo',   'Família toda reunida',       '2026-04-26 12:00:00', '2026-04-26 18:00:00', 'Quintal',      '#FFB347', 'evento');

INSERT IGNORE INTO listas_compras (familia_id, nome, criado_por) VALUES
  (1, 'Compras da semana', 1),
  (1, 'Festa da Maria',    2);

INSERT IGNORE INTO itens_compra (lista_id, nome, quantidade, unidade, categoria, preco) VALUES
  (1, 'Arroz',        2,  'kg',  'Grãos',      5.99),
  (1, 'Feijão',       1,  'kg',  'Grãos',      7.50),
  (1, 'Leite',        6,  'L',   'Laticínios', 4.20),
  (1, 'Pão de forma', 1,  'un',  'Padaria',    6.90),
  (1, 'Frango',       2,  'kg',  'Carnes',     14.99),
  (2, 'Refrigerante', 4,  'L',   'Bebidas',    8.50),
  (2, 'Bolo',         1,  'un',  'Confeitaria',65.00),
  (2, 'Salgadinhos',  200,'g',   'Snacks',     12.00);

INSERT IGNORE INTO transacoes (familia_id, usuario_id, tipo, descricao, valor, categoria, data) VALUES
  (1, 1, 'receita',  'Salário João',          5500.00, 'Salário',     '2026-04-05'),
  (1, 2, 'receita',  'Salário Maria',         4200.00, 'Salário',     '2026-04-05'),
  (1, 1, 'despesa',  'Aluguel',               1800.00, 'Moradia',     '2026-04-10'),
  (1, 1, 'despesa',  'Supermercado',           650.00, 'Alimentação', '2026-04-12'),
  (1, 2, 'despesa',  'Escola das crianças',    900.00, 'Educação',    '2026-04-08'),
  (1, 1, 'despesa',  'Conta de luz',           180.00, 'Contas',      '2026-04-15'),
  (1, 1, 'despesa',  'Internet',               120.00, 'Contas',      '2026-04-15'),
  (1, 2, 'despesa',  'Academia',               150.00, 'Saúde',       '2026-04-03'),
  (1, 1, 'receita',  'Freelance',              800.00, 'Extra',       '2026-04-18'),
  (1, 2, 'despesa',  'Farmácia',                85.00, 'Saúde',       '2026-04-16');

INSERT IGNORE INTO metas_financeiras (familia_id, titulo, valor_meta, valor_atual, prazo, icone) VALUES
  (1, 'Viagem de férias',    8000.00, 3200.00, '2026-12-01', '✈️'),
  (1, 'Reforma da cozinha',  5000.00, 1500.00, '2026-08-01', '🔨'),
  (1, 'Fundo de emergência', 10000.00,6500.00, NULL,         '🛡️');

INSERT IGNORE INTO missoes (familia_id, titulo, descricao, pontos, icone, dificuldade, prazo) VALUES
  (1, 'Organizar a garagem',    'Limpar e organizar todos os itens da garagem',  30, '🏠', 'medio',   '2026-04-30'),
  (1, 'Semana sem delivery',    'Cozinhar em casa todos os dias por uma semana', 50, '🍳', 'dificil', '2026-04-27'),
  (1, 'Leitura em família',     'Ler um livro juntos por 30 min por dia',        20, '📚', 'facil',   '2026-04-25'),
  (1, 'Plantar uma horta',      'Criar uma pequena horta no quintal',            40, '🌱', 'medio',   '2026-05-15'),
  (1, 'Passeio no parque',      'Fazer um piquenique no parque',                 15, '🌳', 'facil',   '2026-04-26');

INSERT IGNORE INTO notificacoes (usuario_id, familia_id, titulo, mensagem, tipo, icone) VALUES
  (1, 1, 'Aniversário se aproxima!',  'O aniversário da Maria é em 28 dias.',    'aviso',  '🎂'),
  (1, 1, 'Meta atingida!',            'Vocês pouparam R$ 3.200 para a viagem!',  'sucesso','🎉'),
  (1, 1, 'Nova missão disponível',    'Semana sem delivery foi adicionada.',     'info',   '⭐'),
  (2, 1, 'Lista de compras atualizada','João adicionou itens à lista.',          'info',   '🛒'),
  (3, 1, 'Parabéns!',                 'Você completou a missão Leitura!',        'sucesso','🏆');
