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