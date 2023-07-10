<?php
// pedidos.php

// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST');
//header('Access-Control-Allow-Headers: Content-Type');

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
// Obtener todos los pedidos
if ($method === 'GET' && $route === 'pedidos') {
    $query = "SELECT * FROM pedido";
    $result = pg_query($conn, $query);

    if ($result) {
        $pedidos = array();

        while ($row = pg_fetch_assoc($result)) {
            $pedidos[] = $row;
        }

        sendResponse(200, $pedidos);
    } else {
        sendResponse(500, ['error' => 'Error al obtener los pedidos']);
    }
}

// Obtener un pedido específico
elseif ($method === 'GET' && preg_match('/^pedidos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];

    $query = "SELECT * FROM pedido WHERE idpedidos = $id";
    $result = pg_query($conn, $query);

    if (pg_num_rows($result) === 1) {
        $pedido = pg_fetch_assoc($result);
        sendResponse(200, $pedido);
    } else {
        sendResponse(404, ['error' => 'Pedido no encontrado']);
    }
}

// Crear un nuevo pedido
elseif ($method === 'POST' && $route === 'pedidos') {
    $input = json_decode(file_get_contents('php://input'), true);

    $idpersona = isset($input['idpersona']) ? pg_escape_string($conn, $input['idpersona']) : null;
    $idlibro = isset($input['idlibro']) ? pg_escape_string($conn, $input['idlibro']) : null;
    $estado = isset($input['estado']) ? pg_escape_string($conn, $input['estado']) : null;
    $observacion = isset($input['observacion']) ? pg_escape_string($conn, $input['observacion']) : null;

    if (empty($idpersona) || empty($idlibro) || empty($estado)) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }

    $query = "INSERT INTO pedido (idpersona, idlibro, estado, observacion) VALUES ($1, $2, $3, $4) RETURNING idpedidos";
    $params = array($idpersona, $idlibro, $estado, $observacion);
    $result = pg_query_params($conn, $query, $params);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $pedidoId = $row['idpedidos'];
        sendResponse(201, ['id' => $pedidoId, 'message' => 'Pedido creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el pedido']);
    }
}

// Actualizar un pedido
elseif ($method === 'PUT' && preg_match('/^pedidos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];

    $input = json_decode(file_get_contents('php://input'), true);

    $idpersona = pg_escape_string($conn, $input['idpersona']);
    $idlibro = pg_escape_string($conn, $input['idlibro']);
    $estado = pg_escape_string($conn, $input['estado']);
    $observacion = pg_escape_string($conn, $input['observacion']);

    if (!$idpersona || !$idlibro || !$estado) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }

    $query = "UPDATE pedido SET idpersona = $idpersona, idlibro = $idlibro, estado = '$estado', observacion = '$observacion' WHERE idpedidos = $id";
    $result = pg_query($conn, $query);

    if ($result) {
        sendResponse(200, ['message' => 'Pedido actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el pedido']);
    }
}

// Eliminar un pedido
elseif ($method === 'DELETE' && preg_match('/^pedidos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];

    $query = "DELETE FROM pedido WHERE idpedidos = $id";
    $result = pg_query($conn, $query);

    if ($result) {
        sendResponse(200, ['message' => 'Pedido eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el pedido']);
    }
}

// Ruta no encontrada
else {
    sendResponse(404, ['error' => 'Ruta no encontrada']);
}

function sendResponse($statusCode, $data)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Cerrar la conexión a la base de datos
pg_close($conn);
?>
