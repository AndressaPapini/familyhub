-- ============================================================
--  FAMILIA MANAGER — SCHEMA DO BANCO DE DADOS ATUALIZADO
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
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  familia_id   INT UNSIGNED,
  nome         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  senha        VARCHAR(255) NOT NULL,
  papel        ENUM('admin','membro') DEFAULT 'membro',
  avatar       VARCHAR(255),
  pontos       INT UNSIGNED DEFAULT 0,
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
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  missao_id    INT UNSIGNED NOT NULL,
  usuario_id   INT UNSIGNED NOT NULL,
  concluida    TINYINT(1) DEFAULT 0,
  concluida_em DATETIME,
  UNIQUE KEY uq_missao_usuario (missao_id, usuario_id),
  FOREIGN KEY (missao_id) REFERENCES missoes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Dados de exemplo (Alinhados com o Site) ─────────────────────────────────────────

-- Ajustado para bater com "Família Parrilla" do JavaScript
INSERT IGNORE INTO familias (id, nome, codigo, descricao) VALUES
  (1, 'Família Parrilla', 'PARR2026', 'Nossa família unida e organizada!');

-- Ajustado e-mails para o padrão e incluída a senha do Demo (123456) 
-- Nota: Em produção, use password_hash do PHP.
INSERT IGNORE INTO usuarios (id, familia_id, nome, email, senha, papel, pontos) VALUES
  (1, 1, 'Rafaela Parrilla',  'rafaela.parrilla@email.com', '123456', 'admin',  320),
  (2, 2, 'Andressa Parrilla', 'andressa.parrilla@email.com', '123456', 'membro', 280),
  (3, 3, 'Isabelli Parrilla', 'isabelli.parrilla@email.com', '123456', 'membro', 195),
  (4, 4, 'Gustavo Parrilla',  'gustavo.parrilla@email.com',  '123456', 'membro', 150),
  (5, 5, 'Rafael Parrilla',   'rafael.parrilla@email.com',   '123456', 'membro', 150);

INSERT IGNORE INTO eventos (familia_id, criador_id, titulo, descricao, data_inicio, data_fim, local, cor, tipo) VALUES
  (1, 1, 'Aniversário da Merlotto', 'Festa de 17 anos!', '2026-05-07 18:00:00', '2026-05-07 23:00:00', 'Casa', '#FF6584', 'aniversario'),
  (1, 1, 'Reunião Familiar', 'Planejamento do mês', '2026-04-20 19:00:00', '2026-04-20 21:00:00', 'Sala de estar','#6C63FF', 'reuniao');

INSERT IGNORE INTO listas_compras (id, familia_id, nome, criado_por) VALUES
  (1, 1, 'Compras da semana', 1),
  (2, 1, 'Festa de Aniversário', 2);

INSERT IGNORE INTO itens_compra (lista_id, nome, quantidade, unidade, categoria, preco) VALUES
  (1, 'Arroz', 2, 'kg', 'Grãos', 5.99),
  (1, 'Feijão', 1, 'kg', 'Grãos', 7.50),
  (1, 'Leite', 6, 'L', 'Laticínios', 4.20),
  (2, 'Refrigerante', 4, 'L', 'Bebidas', 8.50),
  (2, 'Bolo de Festa', 1, 'un', 'Confeitaria', 65.00);

INSERT IGNORE INTO transacoes (familia_id, usuario_id, tipo, descricao, valor, categoria, data) VALUES
  (1, 1, 'receita', 'Salário Rafaela', 5500.00, 'Salário', '2026-04-05'),
  (1, 1, 'despesa', 'Aluguel', 1800.0