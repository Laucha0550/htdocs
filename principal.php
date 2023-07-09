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

// Establecer la codificación de caracteres
//$conn->set_charset("utf8");
$url = $_SERVER['REQUEST_URI'];
$queryString = parse_url($url, PHP_URL_QUERY);

// Parsear los parámetros de la cadena de consulta
parse_str($queryString, $params);

// Obtener el valor de 'route' si está definido
$route = isset($params['route']) ? $params['route'] : '';

// Procesar la solicitud y generar respuestas
if (strpos($route, 'personas') === 0) {
    // Incluir el archivo personas.php
    include 'personas.php';
} elseif (strpos($route, 'usuarios') === 0) {
    // Incluir el archivo usuarios.php
    include 'usuarios.php';
} elseif (strpos($route, 'multas') === 0) {
    // Incluir el archivo multas.php
    include 'multas.php';
} elseif (strpos($route, 'autores') === 0) {
    // Incluir el archivo autores.php
    include 'autores.php';
} elseif (strpos($route, 'clientes') === 0) {
    // Incluir el archivo clientes.php
    include 'clientes.php';
} elseif (strpos($route, 'empleados') === 0) {
    // Incluir el archivo empleados.php
    include 'empleados.php'; 
} elseif (strpos($route, 'generos') === 0) {
    // Incluir el archivo genero.php
    include 'generos.php';
} elseif (strpos($route, 'generolibro') === 0) {
    // Incluir el archivo generolibro.php
    include 'generolibro.php';
} elseif (strpos($route, 'libros') === 0) {
    // Incluir el archivo libros.php
    include 'libros.php';
} elseif (strpos($route, 'prestamos') === 0) {
    // Incluir el archivo prestamos.php
    include 'prestamos.php';
} elseif (strpos($route, 'stocks') === 0) {
    // Incluir el archivo stocks.php
    include 'stocks.php';
} elseif (strpos($route, 'usuarios') === 0) {
    // Incluir el archivo usuarios.php
    include 'usuarios.php';
} else {
    echo($route);
    sendResponse(404, ['error' => 'Ruta no encontrada']);
}

// Cerrar la conexión a la base de datos
$conn->close();


?>