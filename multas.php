<?php
// multas.php

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

// Obtener todas las multas
if ($method === 'GET' && $route === 'multas') {
    $query = "SELECT * FROM Multa";
    $result = pg_query($conn, $query);
    
    $multas = array();
    
    while ($row = pg_fetch_assoc($result)) {
        $multas[] = $row;
    }
    
    sendResponse(200, $multas);
}

// Obtener una multa específica
elseif ($method === 'GET' && preg_match('/^multas\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Multa WHERE IDMulta = $id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) === 1) {
        $multa = pg_fetch_assoc($result);
        sendResponse(200, $multa);
    } else {
        sendResponse(404, ['error' => 'Multa no encontrada']);
    }
}

// Crear una nueva multa
elseif ($method === 'POST' && $route === 'multas') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios de la multa
    $cantidad = pg_escape_string($conn, $input['cantidad']);
    $dias = pg_escape_string($conn, $input['dias']);
    
    if (!$cantidad || !$dias) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar la nueva multa en la base de datos
    $query = "INSERT INTO Multa (Cantidad, Dias) VALUES ($cantidad, $dias)";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $multaId = pg_last_oid($result);
        sendResponse(201, ['id' => $multaId, 'message' => 'Multa creada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear la multa']);
    }
}

// Actualizar una multa
elseif ($method === 'PUT' && preg_match('/^multas\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $cantidad = pg_escape_string($conn, $input['cantidad']);
    $dias = pg_escape_string($conn, $input['dias']);
    
    if (!$cantidad || !$dias) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE Multa SET Cantidad = $cantidad, Dias = $dias WHERE IDMulta = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Multa actualizada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar la multa']);
    }
}

// Eliminar una multa
elseif ($method === 'DELETE' && preg_match('/^multas\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Multa WHERE IDMulta = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Multa eliminada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar la multa']);
    }
}

// Ruta no encontrada
else {
    sendResponse(404, ['error' => 'Ruta no encontrada']);
}

// Cerrar la conexión a la base de datos
pg_close($conn);
?>
