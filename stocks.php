<?php
// stocks.php

// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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

// Obtener todos los registros de stock
if ($method === 'GET' && $route === 'stocks') {
    $query = "SELECT * FROM Stock";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $stocks = array();
    
        while ($row = pg_fetch_assoc($result)) {
            $stocks[] = $row;
        }
    
        sendResponse(200, $stocks);
    } else {
        sendResponse(500, ['error' => 'Error al obtener los libros']);
    }
}

// Obtener un registro de stock específico
elseif ($method === 'GET' && preg_match('/^stocks\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Stock WHERE IDStock = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $stockItem = $result->fetch_assoc();
        sendResponse(200, $stockItem);
    } else {
        sendResponse(404, ['error' => 'Registro de stock no encontrado']);
    }
}

// Crear un nuevo registro de stock
elseif ($method === 'POST' && $route === 'stocks') {
    $input = json_decode(file_get_contents('php://input'), true);

    $idlibro = isset($input['idlibro']) ? pg_escape_string($conn, $input['idlibro']) : null;
    $disponible = isset($input['disponible']) ? (bool) $input['disponible'] : false;

    if (empty($idlibro) || !is_bool($disponible)) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "INSERT INTO Stock (idlibro, disponible) VALUES ($1, $2) RETURNING idstock";
    $params = array($idlibro, $disponible);
    $result = pg_query_params($conn, $query, $params);
    
    if ($result) {
        $row = pg_fetch_assoc($result);
        $libroId = $row['idstock'];
        sendResponse(201, ['id' => $libroId, 'message' => 'Libro creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el libro']);
    }
}


// Actualizar un registro de stock
if ($method === 'PUT' && preg_match('/^stocks\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idLibro = isset($input['idlibro']) ? pg_escape_string($conn, $input['idlibro']) : null;
    $disponible = isset($input['disponible']) ? pg_escape_string($conn, $input['disponible']) : null;

    // Verificar si el registro de stock existe
    $query = "SELECT * FROM stock WHERE idstock = $id";
    $result = pg_query($conn, $query);

    if (pg_num_rows($result) !== 1) {
        sendResponse(404, ['error' => 'Registro de stock no encontrado']);
    }

    // Verificar si el libro existe
    $query = "SELECT * FROM libro WHERE idlibro = '$idLibro'";
    $result = pg_query($conn, $query);

    if (pg_num_rows($result) !== 1) {
        sendResponse(404, ['error' => 'Libro no encontrado']);
    }

    if ($disponible !== '0' && $disponible !== '1') {
        sendResponse(400, ['error' => 'Valor de Disponible no válido']);
    }

    $query = "UPDATE stock SET idlibro = '$idLibro', disponible = '$disponible' WHERE idstock = $id";
    $result = pg_query($conn, $query);

    if ($result) {
        sendResponse(200, ['message' => 'Registro de stock actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el registro de stock']);
    }
}


// Eliminar un registro de stock
elseif ($method === 'DELETE' && preg_match('/^stock\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Stock WHERE IDStock = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Registro de stock eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el registro de stock']);
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
$conn->close();

?>