<?php
// generos.php

// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar el método de solicitud HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener la ruta de la solicitud
$route = $_GET['route'] ?? '';

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
pg_set_client_encoding($conn, "utf8");

// Procesar la solicitud y generar respuestas

// Obtener todos los géneros
if ($method === 'GET' && $route === 'generos') {
    $query = "SELECT * FROM Genero";
    $result = pg_query($conn, $query);
    
    $generos = array();
    
    while ($row = pg_fetch_assoc($result)) {
        $generos[] = $row;
    }
    
    sendResponse(200, $generos);
}

// Obtener un género específico
elseif ($method === 'GET' && preg_match('/^generos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Genero WHERE IDGenero = $id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) === 1) {
        $genero = pg_fetch_assoc($result);
        sendResponse(200, $genero);
    } else {
        sendResponse(404, ['error' => 'Género no encontrado']);
    }
}

// Crear un nuevo género
elseif ($method === 'POST' && $route === 'generos') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios del género
    $nombreGenero = pg_escape_string($conn, $input['nombreGenero']);
    
    if (!$nombreGenero) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar el nuevo género en la base de datos
    $query = "INSERT INTO Genero (NombreGenero) VALUES ('$nombreGenero')";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $generoId = pg_last_oid($conn);
        sendResponse(201, ['id' => $generoId, 'message' => 'Género creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el género']);
    }
}

// Actualizar un género
elseif ($method === 'PUT' && preg_match('/^generos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $nombreGenero = pg_escape_string($conn, $input['nombreGenero']);
    
    if (!$nombreGenero) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE Genero SET NombreGenero = '$nombreGenero' WHERE IDGenero = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Género actualizado correctamente']);
    } else {
        sendResponse(500, ['error'=> 'Error al actualizar el género']);
    }
}

// Eliminar un género
elseif ($method === 'DELETE' && preg_match('/^generos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Genero WHERE IDGenero = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Género eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el género']);
    }
}

// Ruta no encontrada
else {
    sendResponse(404, ['error' => 'Ruta no encontrada']);
}

// Cerrar la conexión a la base de datos
pg_close($conn);

?>