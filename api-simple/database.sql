-- ======================================================================
-- database.sql
-- ======================================================================
-- Crea la base, las tablas de la API (usuarios, productos) y la tabla
-- del ejercicio grupal "ventas" (ver docs/ejercicio-nueva-entidad.md).
--
-- Cómo importarlo:
--
--   mysql -u root -p < database.sql          (línea de comandos)
--
-- o desde phpMyAdmin: Importar -> elegir este archivo -> Continuar.
--
-- Si tu base, usuario o contraseña son distintos a los de .env.example,
-- acordate de actualizar tu .env (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD).
--
-- Si ya habias importado una version anterior de este archivo (sin la
-- tabla ventas), volve a importarlo desde cero para que se agregue:
--
--   DROP DATABASE utu_demo;    (borra la base vieja)
--   mysql -u root -p < database.sql
-- ======================================================================

CREATE DATABASE IF NOT EXISTS utu_demo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE utu_demo;

-- ----------------------------------------------------------------------
-- Tabla usuarios
-- ----------------------------------------------------------------------
CREATE TABLE usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    clave_hash VARCHAR(255)  NOT NULL,
    rol        VARCHAR(20)   NOT NULL DEFAULT 'usuario',
    activo     TINYINT(1)    NOT NULL DEFAULT 1
);

-- ----------------------------------------------------------------------
-- Tabla productos
-- ----------------------------------------------------------------------
CREATE TABLE productos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150)   NOT NULL,
    descripcion TEXT,
    precio      DECIMAL(10, 2) NOT NULL,
    stock       INT            NOT NULL DEFAULT 0,
    categoria   VARCHAR(50)    NOT NULL,
    activo      TINYINT(1)     NOT NULL DEFAULT 1
);

-- ----------------------------------------------------------------------
-- Usuarios de prueba
-- La contraseña ya viene convertida en un hash con password_hash() (bcrypt), tal
-- cual quedaría guardada de verdad: NUNCA se guarda en texto plano.
--
--   admin@utu.edu.uy  / admin123   (rol admin)
--   alumno@utu.edu.uy / alumno123  (rol usuario)
-- ----------------------------------------------------------------------
INSERT INTO usuarios (nombre, email, clave_hash, rol, activo) VALUES
('Ana Administradora', 'admin@utu.edu.uy',  '$2y$12$.Fvn3QkhSJip4AEcBNeH3eUFB67Y/zUXpnpVzHvNme3qcHV1zlwjm', 'admin',   1),
('Bruno Alumno',        'alumno@utu.edu.uy', '$2y$12$FGZYCw99rH7qj/G5O/OJm.Mc.AvsrevKOjufKCEbNEqEOZtXaQ3NS', 'usuario', 1);

-- ----------------------------------------------------------------------
-- Productos de ejemplo
-- ----------------------------------------------------------------------
INSERT INTO productos (nombre, descripcion, precio, stock, categoria, activo) VALUES
('Teclado mecánico',     'Teclado con luces y switches azules.',    2450.00,  12, 'perifericos',   1),
('Mouse inalámbrico',    'Mouse óptico con receptor USB.',           890.00,  34, 'perifericos',   1),
('Monitor 24 pulgadas',  'Monitor Full HD con HDMI.',                9800.00,  5, 'monitores',     1),
('Notebook 15 pulgadas', 'Notebook con 8 GB de RAM y disco SSD.',   38500.00,  0, 'computadoras',  1),
('Auriculares',          'Auriculares con micrófono.',               3200.00, 18, 'audio',         1);

-- ======================================================================
-- Tabla del ejercicio grupal "ventas" (ver
-- docs/ejercicio-nueva-entidad.md). Ya esta creada a proposito: en esta
-- etapa del curso todavia no vimos como disenar tablas, asi que la base
-- se las damos hecha. Lo que construye la clase es el backend PHP
-- (Model ya armado en models/Venta.php; Repository, Service y
-- Controller con un metodo por grupo) sobre ESTA MISMA tabla.
-- ======================================================================

-- ----------------------------------------------------------------------
-- Tabla ventas
-- ----------------------------------------------------------------------
CREATE TABLE ventas (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT NOT NULL,
    producto_id      INT NOT NULL,
    cantidad         INT NOT NULL,
    precio_unitario  DECIMAL(10, 2) NOT NULL,
    total            DECIMAL(10, 2) NOT NULL,
    --el estado puede ser confirmada o anulada. Si es anulada, el stock del producto se devuelve.
    estado           VARCHAR(20) NOT NULL DEFAULT 'confirmada',
    fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ventas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id),

    CONSTRAINT fk_ventas_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
);

-- 'estado' guarda 'confirmada' o 'anulada'. Una venta anulada NO se
-- borra (se pierde el historial): queda marcada, y el stock del
-- producto se devuelve. Ver el endpoint "anular" en la letra del
-- ejercicio.
INSERT INTO ventas (usuario_id, producto_id, cantidad, precio_unitario, total, estado) VALUES
(2, 1, 1, 2450.00, 2450.00, 'confirmada'),
(2, 2, 2,  890.00, 1780.00, 'confirmada'),
(2, 5, 1, 3200.00, 3200.00, 'anulada');

-- ======================================================================
-- Tabla reviews
-- ======================================================================
-- Entidad nueva y minima: un usuario opina sobre un producto (1 a 5
-- puntos + comentario opcional). Misma idea que "ventas": dos claves
-- foraneas (usuario_id, producto_id) apuntando a tablas que ya existen.
-- ----------------------------------------------------------------------
CREATE TABLE reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    producto_id INT NOT NULL,
    puntuacion  INT NOT NULL,
    comentario  TEXT,
    fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reviews_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id),

    CONSTRAINT fk_reviews_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
);

-- 'puntuacion' va de 1 a 5: la validacion de ese rango se hace en el
-- Controller/Service (como el resto del curso), no con un CHECK en la
-- base.
INSERT INTO reviews (usuario_id, producto_id, puntuacion, comentario) VALUES
(2, 1, 5, 'Excelente teclado, se siente muy solido.'),
(2, 3, 3, 'El monitor esta bien pero esperaba mejor calidad de imagen.');
