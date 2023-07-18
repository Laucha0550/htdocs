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
$host = "biblioteca23.crpl8nkfotjs.us-east-2.rds.amazonaws.com";
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


// Obtener todos los empleados
if ($method === 'GET' && $route === 'empleados') {
    $query = "SELECT * FROM Empleado";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $empleados = array();
    
        while ($row = pg_fetch_assoc($result)) {
            $empleados[] = $row;
        }
    
        sendResponse(200, $empleados);
    } else {
        sendResponse(500, ['error' => 'Error al obtener los empleados']);
    }
}

elseif ($method === 'GET' && $route === 'empautnom') {
    $query = "SELECT e.*, CONCAT(p.nombre, ' ', p.apellido) AS nombreempleado
              FROM empleado e
              INNER JOIN persona p ON e.idpersona = p.idpersona";
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
