<?php
// empleados.php

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


// Obtener todos los empleados
if ($method === 'GET' && $route === 'empleados') {
    $query = "SELECT * FROM Empleado";
    $result = $conn->query($query);
    
    $empleados = array();
    
    while ($row = $result->fetch_assoc()) {
        $empleados[] = $row;
    }
    
    sendResponse(200, $empleados);
}

// Obtener un empleado específico
elseif ($method === 'GET' && preg_match('/^empleados\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Empleado WHERE IDEmpleado = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $empleado = $result->fetch_assoc();
        sendResponse(200, $empleado);
    } else {
        sendResponse(404, ['error' => 'Empleado no encontrado']);
    }
}

// Crear un nuevo empleado
elseif ($method === 'POST' && $route === 'empleados') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios del empleado
    $idPersona = $conn->real_escape_string($input['idPersona']);
    $idUsuario = $conn->real_escape_string($input['idUsuario']);
    $cargo = $conn->real_escape_string($input['cargo']);
    
    if (!$idPersona || !$idUsuario || !$cargo) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar el nuevo empleado en la base de datos
    $query = "INSERT INTO Empleado (IDPersona, IDUsuario, Cargo) VALUES ($idPersona, $idUsuario, $cargo)";
    $result = $conn->query($query);
    
    if ($result === true) {
        $empleadoId = $conn->insert_id;
        sendResponse(201, ['id' => $empleadoId, 'message' => 'Empleado creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el empleado']);
    }
}

// Actualizar un empleado
elseif ($method === 'PUT' && preg_match('/^empleados\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idPersona = $conn->real_escape_string($input['idPersona']);
    $idUsuario = $conn->real_escape_string($input['idUsuario']);
    $cargo = $conn->real_escape_string($input['cargo']);
    
    if (!$idPersona || !$idUsuario || !$cargo) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE Empleado SET IDPersona = $idPersona, IDUsuario = $idUsuario, Cargo = $cargo WHERE IDEmpleado = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Empleado actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el empleado']);
    }
}

// Eliminar un empleado
elseif ($method === 'DELETE' && preg_match('/^empleados\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Empleado WHERE IDEmpleado = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Empleado eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el empleado']);
    }
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
