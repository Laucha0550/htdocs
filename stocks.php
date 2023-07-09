<?php
// stocks.php

// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar el método de solicitud HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obtener la ruta de la solicitud
$route = $_GET['route'] ?? '';

// Conexión a la base de datos
$servername = "localhost";
$username = "Admin";
$password = "1234";
$dbname = "biblioteca";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error al conectar con la base de datos: " . $conn->connect_error);
}

// Establecer la codificación de caracteres
$conn->set_charset("utf8");

// Obtener todos los registros de stock
if ($method === 'GET' && $route === 'stock') {
    $query = "SELECT * FROM Stock";
    $result = $conn->query($query);
    
    $stock = array();
    
    while ($row = $result->fetch_assoc()) {
        $stock[] = $row;
    }
    
    sendResponse(200, $stock);
}

// Obtener un registro de stock específico
elseif ($method === 'GET' && preg_match('/^stock\/(\d+)$/', $route, $matches)) {
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
elseif ($method === 'POST' && $route === 'stock') {
    $input = json_decode(file_get_contents('php://input'), true);

    $idLibro = $conn->real_escape_string($input['IDLibro']);
    $disponible = $conn->real_escape_string($input['Disponible']);
    
    // Verificar si el libro existe
    $query = "SELECT * FROM Libro WHERE IDLibro = $idLibro";
    $result = $conn->query($query);
    
    if ($result->num_rows !== 1) {
        sendResponse(404, ['error' => 'Libro no encontrado']);
    }
    
    if ($disponible !== '0' && $disponible !== '1') {
        sendResponse(400, ['error' => 'Valor de Disponible no válido']);
    }
    
    $query = "INSERT INTO Stock (IDLibro, Disponible) VALUES ('$idLibro', '$disponible')";
    $result = $conn->query($query);
    
    if ($result === true) {
        $stockId = $conn->insert_id;
        sendResponse(201, ['id' => $stockId, 'message' => 'Registro de stock creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el registro de stock']);
    }
}

// Actualizar un registro de stock
elseif ($method === 'PUT' && preg_match('/^stock\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idLibro = $conn->real_escape_string($input['IDLibro']);
    $disponible = $conn->real_escape_string($input['Disponible']);
    
    // Verificar si el registro de stock existe
    $query = "SELECT * FROM Stock WHERE IDStock = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows !== 1) {
        sendResponse(404, ['error' => 'Registro de stock no encontrado']);
    }
    
    // Verificar si el libro existe
    $query = "SELECT * FROM Libro WHERE IDLibro = $idLibro";
    $result = $conn->query($query);
    
    if ($result->num_rows !== 1){
        sendResponse(404, ['error' => 'Libro no encontrado']);
    }
    
    if ($disponible !== '0' && $disponible !== '1') {
        sendResponse(400, ['error' => 'Valor de Disponible no válido']);
    }
    
    $query = "UPDATE Stock SET IDLibro = '$idLibro', Disponible = '$disponible' WHERE IDStock = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
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