create table roles(
id_rol serial not null primary key,
nomb_rol varchar(50) not null);

create table Pagos_Factura(
id_forma_pago serial not null primary key,
nomb_medio varchar(50),
comision decimal(10,2));

create table tipos_venta(
id_tipo_venta serial not null primary key,
nomb_tipo varchar(50) not null);
  
create table Generos(
id_genero serial not null primary key,
nomb_genero varchar(50));

create table Salas( 
id_sala serial not null primary key, 
nomb_sala varchar(50), 
aforo smallint not null,
tipo_sala varchar(50));

create table directores(
id_director serial not null primary key,
nomb1_d varchar(50) not null,
nomb2_d varchar(50) null,
apel_d varchar(50) not null,
fecha_nac_d date not null,
foto_url varchar(255) null); -- [Modificado] Añadido para la foto del director

create table actores(
id_actor serial not null primary key,
nomb1_a varchar(50) not null,
nomb2_a varchar(50) null,
apel_a varchar(50) not null,
fecha_nac_a date not null,
foto_url varchar(255) null); -- [Modificado] Añadido para la foto del actor

create table peliculas(
id_peliculas serial not null primary key,
nomb_p varchar(50) not null,
año_p smallint not null,
duracion_M int not null,
id_genero int not null, 
poster_url varchar(255) null, -- [Modificado] Añadido para el póster de la película
constraint fk_peliculas_generos FOREIGN KEY (id_genero) references Generos(id_genero)
);

create table Usuarios(
id_usuario serial not null primary key,
nomb1_u varchar(50) not null,
nomb2_u varchar(50) null,
apel_u varchar(50) not null,
correo_u varchar(100) not null unique,
contraseña_u varchar(255) not null,
tel_u varchar(50) not null unique,
id_rol int not null,
constraint fk_usuarios_roles foreign key (id_rol) references roles(id_rol)
);

create table asientos(
id_asiento serial not null primary key,
numero_asiento varchar(3) not null,
estado bool not null,
id_sala int not null,
constraint fk_asientos_salas foreign key (id_sala) references salas(id_sala)
);

create table funciones(
id_funcion serial not null primary key,
fecha_hora timestamp not null,
id_sala int not null,
id_peliculas int not null,
precio decimal(10,2) not null,
constraint fk_funciones_salas foreign key (id_sala) references salas(id_sala),
constraint fk_funciones_peliculas foreign key (id_peliculas) references peliculas(id_peliculas)
);

create table boletos (
id_boletos serial not null primary key,
fecha_compra timestamp default current_timestamp not null,
id_usuario int not null,
id_asiento int not null,
id_tipo_venta int not null,
id_forma_pago int not null,
id_funcion int not null,
constraint fk_boletos_usuarios foreign key (id_usuario) references Usuarios(id_usuario),
constraint fk_boletos_asientos foreign key (id_asiento) references Asientos(id_asiento),
constraint fk_boletos_tiposVentas foreign key (id_tipo_venta) references tipos_venta(id_tipo_venta),
constraint fk_boletos_pagosFormas foreign key (id_forma_pago) references Pagos_factura(id_forma_pago),
constraint fk_boletos_funciones foreign key (id_funcion) references funciones(id_funcion)
);

create table actua(
id_actor int not null,
id_peliculas int not null,
primary key (id_actor,id_peliculas),
constraint fk_actua_actor foreign key (id_actor) references actores(id_actor),
constraint fk_actua_pelicula foreign key (id_peliculas) references peliculas(id_peliculas)
);

create table dirige(
id_director int not null,
id_peliculas int not null,
primary key (id_director,id_peliculas),
constraint fk_dirige_director foreign key (id_director) references directores(id_director),
constraint fk_dirige_pelicula foreign key (id_peliculas) references peliculas(id_peliculas)
);

-- Restricciones en Cascada y Validaciones Check
ALTER TABLE actua 
DROP CONSTRAINT fk_actua_pelicula,
ADD CONSTRAINT fk_actua_pelicula 
    FOREIGN KEY (id_peliculas) REFERENCES peliculas(id_peliculas) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE actua 
DROP CONSTRAINT fk_actua_actor,
ADD CONSTRAINT fk_actua_actor 
    FOREIGN KEY (id_actor) REFERENCES actores(id_actor) 
    ON DELETE CASCADE ON UPDATE CASCADE;
    
ALTER TABLE boletos 
DROP CONSTRAINT fk_boletos_usuarios,
ADD CONSTRAINT fk_boletos_usuarios 
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) 
    ON DELETE RESTRICT ON UPDATE CASCADE;
    
ALTER TABLE asientos 
DROP CONSTRAINT fk_asientos_salas,
ADD CONSTRAINT fk_asientos_salas 
    FOREIGN KEY (id_sala) REFERENCES salas(id_sala) 
    ON DELETE CASCADE ON UPDATE CASCADE;
    
ALTER TABLE Salas 
ADD CONSTRAINT chk_aforo_positivo CHECK (aforo > 0);

ALTER TABLE Pagos_Factura 
ADD CONSTRAINT chk_comision_no_negativa CHECK (comision >= 0);

ALTER TABLE peliculas 
ADD CONSTRAINT chk_duracion_positiva CHECK (duracion_M > 0);

ALTER TABLE funciones 
ADD CONSTRAINT chk_precio_no_negativo CHECK (precio >= 0);

ALTER TABLE Usuarios 
ADD CONSTRAINT chk_correo_valido CHECK (correo_u LIKE '%@%.%');

ALTER TABLE directores 
ADD CONSTRAINT chk_fecha_nac_director CHECK (fecha_nac_d < CURRENT_DATE);

ALTER TABLE actores 
ADD CONSTRAINT chk_fecha_nac_actor CHECK (fecha_nac_a < CURRENT_DATE);

ALTER TABLE dirige 
DROP CONSTRAINT fk_dirige_pelicula,
ADD CONSTRAINT fk_dirige_pelicula 
    FOREIGN KEY (id_peliculas) REFERENCES peliculas(id_peliculas) 
    ON DELETE CASCADE ON UPDATE CASCADE;
    
ALTER TABLE dirige 
DROP CONSTRAINT fk_dirige_director,
ADD CONSTRAINT fk_dirige_director 
    FOREIGN KEY (id_director) REFERENCES directores(id_director) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE asientos
DROP COLUMN estado;

ALTER TABLE boletos
ADD CONSTRAINT uq_asiento_funcion
UNIQUE (id_funcion, id_asiento);

INSERT INTO tipos_venta (nomb_tipo)
VALUES
('Venta en línea');

INSERT INTO Pagos_Factura (nomb_medio, comision)
VALUES
('Tarjeta', 0),
('PSE', 0);
-- Roles del sistema
INSERT INTO roles (nomb_rol)
VALUES
('Administrador'),
('Cliente');
-- ============================================================
-- 1. VALIDAR DISPONIBILIDAD (EVITAR DOBLE VENTA DE ASIENTO)
-- ============================================================
CREATE OR REPLACE FUNCTION validar_disponibilidad_asiento()
RETURNS TRIGGER AS $$
DECLARE
    v_ocupado BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 FROM boletos 
        WHERE id_asiento = NEW.id_asiento AND id_funcion = NEW.id_funcion
    ) INTO v_ocupado;

    IF v_ocupado THEN
        RAISE EXCEPTION 'El asiento con ID % ya se encuentra vendido para esta función.', NEW.id_asiento;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validar_disponibilidad
BEFORE INSERT ON boletos
FOR EACH ROW
EXECUTE FUNCTION validar_disponibilidad_asiento();


-- ============================================================
-- 2. OCUPAR ASIENTO AUTOMÁTICAMENTE TRAS LA VENTA
-- ============================================================
CREATE OR REPLACE FUNCTION actualizar_estado_asiento()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE asientos
    SET estado = FALSE -- FALSE = Ocupado
    WHERE id_asiento = NEW.id_asiento;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_ocupar_asiento
AFTER INSERT ON boletos
FOR EACH ROW
EXECUTE FUNCTION actualizar_estado_asiento();


-- ============================================================
-- 3. VALIDAR TRASLAPE DE HORARIOS EN UNA SALA
-- ============================================================
CREATE OR REPLACE FUNCTION validar_traslape_funciones()
RETURNS TRIGGER AS $$
DECLARE
    v_duracion_nueva INT;
    v_conflicto INT;
BEGIN
    SELECT duracion_M INTO v_duracion_nueva 
    FROM peliculas 
    WHERE id_peliculas = NEW.id_peliculas;

    SELECT COUNT(*) INTO v_conflicto
    FROM funciones f
    JOIN peliculas p ON f.id_peliculas = p.id_peliculas
    WHERE f.id_sala = NEW.id_sala
      AND f.id_funcion <> NEW.id_funcion
      AND (
          NEW.fecha_hora BETWEEN f.fecha_hora AND (f.fecha_hora + (p.duracion_M || ' minutes')::INTERVAL)
          OR 
          (NEW.fecha_hora + (v_duracion_nueva || ' minutes')::INTERVAL) BETWEEN f.fecha_hora AND (f.fecha_hora + (p.duracion_M || ' minutes')::INTERVAL)
      );

    IF v_conflicto > 0 THEN
        RAISE EXCEPTION 'La sala ya está ocupada por otra función en ese rango de horario.';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validar_horario_sala
BEFORE INSERT OR UPDATE ON funciones
FOR EACH ROW
EXECUTE FUNCTION validar_traslape_funciones();


-- ============================================================
-- 4. LIBERAR ASIENTO AL CANCELAR O BORRAR UN BOLETO
-- ============================================================
CREATE OR REPLACE FUNCTION liberar_asiento_cancelado()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE asientos
    SET estado = TRUE -- TRUE = Disponible
    WHERE id_asiento = OLD.id_asiento;
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_liberar_asiento
AFTER DELETE ON boletos
FOR EACH ROW
EXECUTE FUNCTION liberar_asiento_cancelado();


-- ============================================================
-- 5. VALIDAR QUE EL ASIENTO PERTENEZCA A LA SALA DE LA FUNCIÓN
-- ============================================================
CREATE OR REPLACE FUNCTION validar_asiento_pertenece_sala()
RETURNS TRIGGER AS $$
DECLARE
    v_sala_funcion INT;
    v_sala_asiento INT;
BEGIN
    SELECT id_sala INTO v_sala_funcion FROM funciones WHERE id_funcion = NEW.id_funcion;
    SELECT id_sala INTO v_sala_asiento FROM asientos WHERE id_asiento = NEW.id_asiento;

    IF v_sala_funcion <> v_sala_asiento THEN
        RAISE EXCEPTION 'El asiento seleccionado (ID: %) no pertenece a la sala asignada para la función (ID: %).', NEW.id_asiento, NEW.id_funcion;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validar_sala_asiento
BEFORE INSERT OR UPDATE ON boletos
FOR EACH ROW
EXECUTE FUNCTION validar_asiento_pertenece_sala();
-- Eliminar triggers que usan la columna inexistente 'estado'
DROP TRIGGER IF EXISTS trg_ocupar_asiento ON boletos;
DROP FUNCTION IF EXISTS actualizar_estado_asiento();

DROP TRIGGER IF EXISTS trg_liberar_asiento ON boletos;
DROP FUNCTION IF EXISTS liberar_asiento_cancelado();