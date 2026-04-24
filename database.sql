-- ============================================================
--  BARBERÍA PREMIUM — Script SQL Completo
--  Compatible con MySQL 5.7+ / MariaDB 10.3+
--  Ejecutar en phpMyAdmin o línea de comandos MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS barberia_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE barberia_db;

-- ------------------------------------------------------------
-- 1. CONFIGURACIÓN GENERAL DE LA BARBERÍA
-- ------------------------------------------------------------
CREATE TABLE configuracion (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(120) NOT NULL DEFAULT 'Barbería Premium',
    slogan        VARCHAR(255),
    telefono      VARCHAR(30),
    email         VARCHAR(120),
    direccion     VARCHAR(255),
    ciudad        VARCHAR(100),
    logo          VARCHAR(255),
    hora_apertura TIME NOT NULL DEFAULT '09:00:00',
    hora_cierre   TIME NOT NULL DEFAULT '20:00:00',
    dias_trabajo  VARCHAR(100) DEFAULT 'Lun,Mar,Mie,Jue,Vie,Sab',
    moneda        VARCHAR(10) DEFAULT 'USD',
    min_cancelacion INT UNSIGNED DEFAULT 60
        COMMENT 'Minutos mínimos de anticipación para cancelar',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registro inicial de configuración
INSERT INTO configuracion (nombre, slogan, telefono, email, direccion, ciudad)
VALUES ('Barbería Elite', 'El arte del buen corte', '+1 (555) 000-0000',
        'info@barberiaelite.com', 'Calle Principal 123', 'Ciudad');

-- ------------------------------------------------------------
-- 2. USUARIOS (tabla base para autenticación)
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(80) NOT NULL,
    apellido      VARCHAR(80) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('admin','barbero','cliente') NOT NULL DEFAULT 'cliente',
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    avatar        VARCHAR(255),
    telefono      VARCHAR(30),
    ultimo_login  DATETIME,
    token_reset   VARCHAR(64),
    token_expira  DATETIME,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol   (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin por defecto: admin@barberia.com / Admin1234
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol)
VALUES ('Admin', 'Sistema', 'admin@barberia.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ------------------------------------------------------------
-- 3. BARBEROS
-- ------------------------------------------------------------
CREATE TABLE barberos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT UNSIGNED NOT NULL UNIQUE,
    especialidad  VARCHAR(150),
    bio           VARCHAR(500),
    foto          VARCHAR(255),
    comision_pct  DECIMAL(5,2) DEFAULT 40.00
        COMMENT 'Porcentaje de comisión sobre cada servicio',
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_barbero_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_barbero_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. CLIENTES
-- ------------------------------------------------------------
CREATE TABLE clientes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT UNSIGNED NOT NULL UNIQUE,
    fecha_nac     DATE,
    notas         VARCHAR(500)
        COMMENT 'Preferencias o notas internas del cliente',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. SERVICIOS
-- ------------------------------------------------------------
CREATE TABLE servicios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    descripcion   TEXT,
    precio        DECIMAL(8,2) NOT NULL,
    duracion_min  INT UNSIGNED NOT NULL DEFAULT 30
        COMMENT 'Duración en minutos',
    imagen        VARCHAR(255),
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_servicio_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO servicios (nombre, descripcion, precio, duracion_min) VALUES
('Corte Clásico',     'Corte tradicional con tijera y máquina.',           15.00, 30),
('Corte + Barba',     'Corte de cabello y arreglo completo de barba.',      25.00, 50),
('Afeitado Clásico',  'Afeitado con navaja y toalla caliente.',             18.00, 40),
('Corte Degradado',   'Fade de alta precisión con máquina.',               20.00, 45),
('Tratamiento Capilar','Hidratación profunda y masaje de cuero cabelludo.', 30.00, 60),
('Barba Premium',     'Perfilado, relleno y cuidado de barba.',             22.00, 35);

-- ------------------------------------------------------------
-- 6. CITAS
-- ------------------------------------------------------------
CREATE TABLE citas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT UNSIGNED NOT NULL,
    barbero_id      INT UNSIGNED NOT NULL,
    servicio_id     INT UNSIGNED NOT NULL,
    fecha           DATE NOT NULL,
    hora_inicio     TIME NOT NULL,
    hora_fin        TIME NOT NULL,
    estado          ENUM('pendiente','confirmada','en_proceso','completada','cancelada')
                    NOT NULL DEFAULT 'pendiente',
    notas_cliente   VARCHAR(500),
    notas_barbero   VARCHAR(500),
    creada_por      INT UNSIGNED COMMENT 'ID del usuario que creó la cita (admin o cliente)',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cita_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes(id),
    CONSTRAINT fk_cita_barbero  FOREIGN KEY (barbero_id)  REFERENCES barberos(id),
    CONSTRAINT fk_cita_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    INDEX idx_cita_fecha        (fecha),
    INDEX idx_cita_barbero_fecha (barbero_id, fecha),
    INDEX idx_cita_cliente      (cliente_id),
    INDEX idx_cita_estado       (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. PAGOS
-- ------------------------------------------------------------
CREATE TABLE pagos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cita_id       INT UNSIGNED NOT NULL UNIQUE,
    monto         DECIMAL(8,2) NOT NULL,
    metodo        ENUM('efectivo','tarjeta','transferencia','otro')
                  NOT NULL DEFAULT 'efectivo',
    estado        ENUM('pendiente','pagado','reembolsado') NOT NULL DEFAULT 'pendiente',
    referencia    VARCHAR(100) COMMENT 'Número de transacción u referencia',
    notas         VARCHAR(255),
    pagado_en     DATETIME,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pago_cita FOREIGN KEY (cita_id)
        REFERENCES citas(id) ON DELETE CASCADE,
    INDEX idx_pago_estado  (estado),
    INDEX idx_pago_metodo  (metodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. HORARIOS BLOQUEADOS
-- ------------------------------------------------------------
CREATE TABLE horarios_bloqueados (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbero_id  INT UNSIGNED NOT NULL,
    fecha       DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin    TIME NOT NULL,
    motivo      VARCHAR(200),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bloqueo_barbero FOREIGN KEY (barbero_id)
        REFERENCES barberos(id) ON DELETE CASCADE,
    INDEX idx_bloqueo_barbero_fecha (barbero_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. GALERÍA
-- ------------------------------------------------------------
CREATE TABLE galeria (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(120),
    descripcion VARCHAR(300),
    imagen      VARCHAR(255) NOT NULL,
    categoria   ENUM('corte','barba','tratamiento','otro') DEFAULT 'corte',
    activa      TINYINT(1) NOT NULL DEFAULT 1,
    orden       INT UNSIGNED DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_galeria_activa (activa),
    INDEX idx_galeria_orden  (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. CONTACTOS (formulario público)
-- ------------------------------------------------------------
CREATE TABLE contactos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(120) NOT NULL,
    email       VARCHAR(120) NOT NULL,
    telefono    VARCHAR(30),
    mensaje     TEXT NOT NULL,
    leido       TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contacto_leido (leido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
