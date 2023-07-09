<?php

// archivo_principal.php
// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Conexión a la base de datos
$host = "localhost";
$port = "5432";
$dbname = "biblioteca";
$user = "postgres";
$password = "admin123";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Error al conectar con la base de datos: " . pg_last_error());
}

// Crear la tabla de Persona
$sql = "CREATE TABLE Persona (
    IDPersona SERIAL PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    DNI VARCHAR(20) NOT NULL,
    Telefono VARCHAR(20) NOT NULL
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Persona' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Persona': " . pg_last_error($conn);
}

// Crear la tabla de Usuario
$sql = "CREATE TABLE Usuario (
    IDUsuario SERIAL PRIMARY KEY,
    Contrasena VARCHAR(100) NOT NULL,
    NombreUsuario VARCHAR(50) NOT NULL
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Usuario' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Usuario': " . pg_last_error($conn);
}

// Crear la tabla de Multa
$sql = "CREATE TABLE Multa (
    IDMulta SERIAL PRIMARY KEY,
    Cantidad DECIMAL(10, 2) NOT NULL,
    Dias INT NOT NULL
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Multa' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Multa': " . pg_last_error($conn);
}

// Crear la tabla de Genero
$sql = "CREATE TABLE Genero (
    IDGenero SERIAL PRIMARY KEY,
    NombreGenero VARCHAR(50) NOT NULL
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Genero' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Genero': " . pg_last_error($conn);
}

// Crear la tabla de Autor
$sql = "CREATE TABLE Autor (
    IDAutor SERIAL PRIMARY KEY,
    IDPersona INT NOT NULL,
    Resena VARCHAR(200) NOT NULL,
    FOREIGN KEY (IDPersona) REFERENCES Persona(IDPersona)
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Autor' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Autor': " . pg_last_error($conn);
}

// Crear la tabla de Cliente
$sql = "CREATE TABLE Cliente (
    IDCliente SERIAL PRIMARY KEY,
    IDPersona INT NOT NULL,
    Direccion VARCHAR(200) NOT NULL,
    IDUsuario INT NOT NULL,
    FOREIGN KEY (IDPersona) REFERENCES Persona(IDPersona),
    FOREIGN KEY (IDUsuario) REFERENCES Usuario(IDUsuario)
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Cliente' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Cliente': " . pg_last_error($conn);
}

// Crear la tabla de Libro
$sql = "CREATE TABLE Libro (
    IDLibro SERIAL PRIMARY KEY,
    NombreLibro VARCHAR(100) NOT NULL,
    ISBN VARCHAR(20) NOT NULL,
    IDAutor INT NOT NULL,
    FOREIGN KEY (IDAutor) REFERENCES Autor(IDAutor)
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Libro' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Libro': " . pg_last_error($conn);
}

// Crear la tabla de Empleado
$sql = "CREATE TABLE Empleado (
    IDEmpleado SERIAL PRIMARY KEY,
    IDPersona INT NOT NULL,
    IDUsuario INT NOT NULL,
    Cargo BOOLEAN NOT NULL,
    FOREIGN KEY (IDPersona) REFERENCES Persona(IDPersona),
    FOREIGN KEY (IDUsuario) REFERENCES Usuario(IDUsuario)
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Empleado' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Empleado': " . pg_last_error($conn);
}

// Crear la tabla de GeneroLibro
$sql = "CREATE TABLE GeneroLibro (
    IDGeneroLibro SERIAL PRIMARY KEY,
    IDGenero INT NOT NULL,
    IDLibro INT NOT NULL,
    FOREIGN KEY (IDGenero) REFERENCES Genero(IDGenero),
    FOREIGN KEY (IDLibro) REFERENCES Libro(IDLibro)
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'GeneroLibro' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'GeneroLibro': " . pg_last_error($conn);
}

// Crear la tabla de Stock
$sql = "CREATE TABLE Stock (
    IDStock SERIAL PRIMARY KEY,
    IDLibro INT NOT NULL,
    Disponible BOOLEAN NOT NULL,
    FOREIGN KEY (IDLibro) REFERENCES Libro(IDLibro)
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Stock' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Stock': " . pg_last_error($conn);
}

// Crear la tabla de Prestamo
$sql = "CREATE TABLE Prestamo (
    IDPrestamo SERIAL PRIMARY KEY,
    IDStock INT NOT NULL,
    IDCliente INT NOT NULL,
    IDEmpleado INT NOT NULL,
    FechaPrestamo DATE NOT NULL,
    FechaDevolucion DATE NOT NULL,
    FechaEntrega DATE,
    IDMulta INT,
    FOREIGN KEY (IDStock) REFERENCES Stock(IDStock),
    FOREIGN KEY (IDCliente) REFERENCES Cliente(IDCliente),
    FOREIGN KEY (IDEmpleado) REFERENCES Empleado(IDEmpleado),
    FOREIGN KEY (IDMulta) REFERENCES Multa(IDMulta)
)";
$result = pg_query($conn, $sql);
if ($result) {
    echo "La tabla 'Prestamo' se ha creado correctamente.\n";
} else {
    echo "Error al crear la tabla 'Prestamo': " . pg_last_error($conn);
}

pg_close($conn);
?>
