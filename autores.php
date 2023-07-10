<?php
// autores.php

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

// Obtener todos los autores
if ($method === 'GET' && $route === 'autores') {
    $query = "SELECT * FROM autor";
    $result = pg_query($conn, $query);
    
    $autores = array();
    
    while ($row = pg_fetch_assoc($result)) {
        $autores[] = $row;
    }
    
    sendResponse(200, $autores);
}


// Obtener un autor específico
elseif ($method === 'GET' && preg_match('/^autores\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM autor WHERE idautor = $id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) === 1) {
        $autor = pg_fetch_assoc($result);
        sendResponse(200, $autor);
    } else {
        sendResponse(404, ['error' => 'autor no encontrado']);
    }
}

// Crear un nuevo autor

    elseif ($method === 'POST' && $route === 'autores') {
        $input = json_decode(file_get_contents('php://input'), true);
    
        $idpersona = isset($input['idpersona']) ? pg_escape_string($conn, $input['idpersona']) : null;
        $resena = isset($input['resena']) ? pg_escape_string($conn, $input['resena']) : null;
        
        if (empty($idpersona) || empty($resena)) {
            sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
        }
        
        $query = "INSERT INTO autor (idpersona, resena) VALUES ($1, $2) RETURNING idautor";
        $params = array($idpersona, $resena);
        $result = pg_query_params($conn, $query, $params);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            $autorId = $row['idautor'];
            sendResponse(201, ['id' => $autorId, 'message' => 'Autor creado correctamente']);
        } else {
            sendResponse(500, ['error' => 'Error al crear el autor']);
        }
    }
    

// Actualizar un autor
elseif ($method === 'PUT' && preg_match('/^autores\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idpersona = pg_escape_string($conn, $input['idpersona']);
    $resena = pg_escape_string($conn, $input['resena']);
    
    if (!$idpersona || !$resena) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE autor SET idpersona = $idpersona, resena = '$resena' WHERE idautor = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'autor actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el autor']);
    }
}

// Eliminar un autor
elseif ($method === 'DELETE' && preg_match('/^autores\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM autor WHERE idautor = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'autor eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el autor']);
    }
}

// Ruta no encontrada
else {
    sendResponse(404, ['error' => 'Ruta no encontrada']);
}
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Cerrar la conexión a la base de datos
pg_close($conn);

?>
