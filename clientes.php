<?php
// clientes.php

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

// Obtener todos los clientes
if ($method === 'GET' && $route === 'clientes') {
    $query = "SELECT * FROM cliente";
    $result = pg_query($conn, $query);
    
    $clientes = array();
    
    while ($row = pg_fetch_assoc($result)) {
        $clientes[] = $row;
    }
    
    sendResponse(200, $clientes);
}

elseif ($method === 'GET' && $route === 'cliautnom') {
    $query = "SELECT c.*, CONCAT(p.nombre, ' ', p.apellido) AS nombrecliente
              FROM cliente c
              INNER JOIN persona p ON c.idpersona = p.idpersona";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $clientes = array();
    
        while ($row = pg_fetch_assoc($result)) {
            $clientes[] = $row;
        }
    
        sendResponse(200, $clientes);
    } else {
        sendResponse(500, ['error' => 'Error al obtener los clientes']);
    }
}


// Obtener un cliente específico
elseif ($method === 'GET' && preg_match('/^clientes\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM cliente WHERE idcliente = $id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) === 1) {
        $cliente = pg_fetch_assoc($result);
        sendResponse(200, $cliente);
    } else {
        sendResponse(404, ['error' => 'Cliente no encontrado']);
    }
}

// Crear un nuevo cliente
elseif ($method === 'POST' && $route === 'clientes') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios del cliente
    $idPersona = pg_escape_string($conn, $input['idpersona']);
    $direccion = pg_escape_string($conn, $input['direccion']);
    $idUsuario = pg_escape_string($conn, $input['idsuario']);
    
    if (!$idPersona || !$direccion || !$idUsuario) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar el nuevo cliente en la base de datos
    $query = "INSERT INTO cliente (idpersona, direccion, idusuario) VALUES ($idPersona, '$direccion', $idUsuario)";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $clienteId = pg_last_oid($conn);
        sendResponse(201, ['id' => $clienteId, 'message' => 'Cliente creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el cliente']);
    }
}

// Actualizar un cliente
elseif ($method === 'PUT' && preg_match('/^clientes\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idPersona = pg_escape_string($conn, $input['idpersona']);
    $direccion = pg_escape_string($conn, $input['direccion']);
    $idUsuario = pg_escape_string($conn, $input['idpsuario']);
    
    if (!$idPersona || !$direccion || !$idUsuario) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE cliente SET idpersona = $idPersona, direccion = '$direccion', idusuario = $idUsuario WHERE IDCliente = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Cliente actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el cliente']);
    }
}

// Eliminar un cliente
elseif ($method === 'DELETE' && preg_match('/^clientes\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM cliente WHERE idcliente = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Cliente eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el cliente']);
    }
}

// Ruta no encontrada
else {
    sendResponse(404, ['error' => 'Ruta no encontrada']);
}

// Función para enviar la respuesta en formato JSON
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Cerrar la conexión a la base de datos
pg_close($conn);

?>
