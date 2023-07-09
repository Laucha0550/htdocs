<?php
// prestamos.php

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

// Obtener todos los préstamos
if ($method === 'GET' && $route === 'prestamos') {
    $query = "SELECT * FROM Prestamo";
    $result = $conn->query($query);
    
    $prestamos = array();
    
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    
    sendResponse(200, $prestamos);
}

// Obtener un préstamo específico
elseif ($method === 'GET' && preg_match('/^prestamos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Prestamo WHERE IDPrestamo = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $prestamo = $result->fetch_assoc();
        sendResponse(200, $prestamo);
    } else {
        sendResponse(404, ['error' => 'Préstamo no encontrado']);
    }
}

// Crear un nuevo préstamo
elseif ($method === 'POST' && $route === 'prestamos') {
    $input = json_decode(file_get_contents('php://input'), true);

    $idStock = $conn->real_escape_string($input['id_stock']);
    $idCliente = $conn->real_escape_string($input['id_cliente']);
    $idEmpleado = $conn->real_escape_string($input['id_empleado']);
    $fechaPrestamo = $conn->real_escape_string($input['fecha_prestamo']);
    $fechaDevolucion = $conn->real_escape_string($input['fecha_devolucion']);
    
    // Verificar que los campos obligatorios no estén vacíos
    if (!$idStock || !$idCliente || !$idEmpleado || !$fechaPrestamo || !$fechaDevolucion) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "INSERT INTO Prestamo (IDStock, IDCliente, IDEmpleado, FechaPrestamo, FechaDevolucion) VALUES ('$idStock', '$idCliente', '$idEmpleado', '$fechaPrestamo', '$fechaDevolucion')";
    $result = $conn->query($query);
    
    if ($result === true) {
        $prestamoId = $conn->insert_id;
        sendResponse(201, ['id' => $prestamoId, 'message' => 'Préstamo creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el préstamo']);
    }
}

// Actualizar un préstamo
elseif ($method === 'PUT' && preg_match('/^prestamos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idStock = $conn->real_escape_string($input['id_stock']);
    $idCliente = $conn->real_escape_string($input['id_cliente']);
    $idEmpleado = $conn->real_escape_string($input['id_empleado']);
    $fechaPrestamo = $conn->real_escape_string($input['fecha_prestamo']);
    $fechaDevolucion = $conn->real_escape_string($input['fecha_devolucion']);
    
    // Verificar que los campos obligatoriosno estén vacíos
    if (!$idStock || !$idCliente || !$idEmpleado || !$fechaPrestamo || !$fechaDevolucion) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE Prestamo SET IDStock = '$idStock', IDCliente = '$idCliente', IDEmpleado = '$idEmpleado', FechaPrestamo = '$fechaPrestamo', FechaDevolucion = '$fechaDevolucion' WHERE IDPrestamo = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Préstamo actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el préstamo']);
    }
}

// Eliminar un préstamo
elseif ($method === 'DELETE' && preg_match('/^prestamos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Prestamo WHERE IDPrestamo = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Préstamo eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el préstamo']);
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
