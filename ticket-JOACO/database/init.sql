
CREATE DATABASE IF NOT EXISTS dely
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE dely;

-- =====================================
-- TABLA: USUARIOS
-- =====================================

CREATE TABLE usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cedula CHAR(8) NOT NULL UNIQUE,
    nombre VARCHAR(60) NOT NULL,
    apellido VARCHAR(60) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    rol ENUM('encargado', 'repartidor') NOT NULL,

    activo BOOLEAN NOT NULL DEFAULT TRUE,

    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;


-- =====================================
-- TABLA: PRIORIDADES
-- =====================================

CREATE TABLE prioridades (
    id_prioridad TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
) ENGINE = InnoDB;


-- =====================================
-- TABLA: ESTADOS DE PEDIDOS
-- =====================================

CREATE TABLE estados (
    id_estado TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE = InnoDB;


-- =====================================
-- TABLA: PEDIDOS
-- =====================================

CREATE TABLE pedidos (
    id_pedido INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    cliente VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    id_encargado INT UNSIGNED NOT NULL,
    id_repartidor INT UNSIGNED NULL,
    id_estado TINYINT UNSIGNED NOT NULL,
    id_prioridad TINYINT UNSIGNED NOT NULL,

    CONSTRAINT fk_pedido_encargado
        FOREIGN KEY (id_encargado)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_pedido_repartidor
        FOREIGN KEY (id_repartidor)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_pedido_estado
        FOREIGN KEY (id_estado)
        REFERENCES estados(id_estado)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_pedido_prioridad
        FOREIGN KEY (id_prioridad)
        REFERENCES prioridades(id_prioridad)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_pedido_fecha (fecha),
    INDEX idx_pedido_estado_fecha (id_estado, fecha),
    INDEX idx_pedido_prioridad_fecha (id_prioridad, fecha),
    INDEX idx_pedido_encargado_fecha (id_encargado, fecha),
    INDEX idx_pedido_repartidor_fecha (id_repartidor, fecha)

) ENGINE = InnoDB;


-- =====================================
-- TABLA: HISTORIAL DE PEDIDOS
-- =====================================

CREATE TABLE historial_pedidos (
    id_historial INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_pedido INT UNSIGNED NOT NULL,
    id_estado TINYINT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_historial_pedido
        FOREIGN KEY (id_pedido)
        REFERENCES pedidos(id_pedido)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_historial_estado
        FOREIGN KEY (id_estado)
        REFERENCES estados(id_estado)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_historial_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_historial_pedido_fecha (id_pedido, fecha)

) ENGINE = InnoDB;


-- =====================================
-- DATOS INICIALES
-- =====================================

INSERT INTO prioridades (nombre) VALUES
('Baja'),
('Normal'),
('Alta'),
('Urgente');


INSERT INTO estados (nombre) VALUES
('En preparación'),
('En camino'),
('Entregado');
