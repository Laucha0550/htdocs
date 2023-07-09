<?php
// personas.php

// archivo_principal.php
// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

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
$method = $_SERVER['REQUEST_METHOD'];
// Establecer la codificación de caracteres
//$conn->set_charset("utf8");

// Procesar la solicitud y generar respuestas

// Obtener todas las personas
if ($method === 'GET' && $route === 'personas') {
    $query = "SELECT * FROM persona";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $personas = array();
    
        while ($row = pg_fetch_assoc($result)) {
            $personas[] = $row;
        }
    
        sendResponse(200, $personas);
    } else {
        sendResponse(500, ['error' => 'Error al obtener las personas']);
    }
}
// Obtener una persona específica
elseif ($method === 'GET' && preg_match('/^personas\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM persona WHERE idpersona = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $persona = $result->fetch_assoc();
        sendResponse(200, $persona);
    } else {
        sendResponse(404, ['error' => 'Persona no encontrada']);
    }
}

// Crear una nueva persona
elseif ($method === 'POST' && $route === 'personas') {
    $input = json_decode(file_get_contents('php://input'), true);

    $nombre = isset($input['nombre']) ? pg_escape_string($conn, $input['nombre']) : null;
    $apellido = isset($input['apellido']) ? pg_escape_string($conn, $input['apellido']) : null;
    $email = isset($input['email']) ? filter_var($input['email'], FILTER_VALIDATE_EMAIL) : null;
    $dni = isset($input['dni']) ? pg_escape_string($conn, $input['dni']) : null;
    $telefono = isset($input['telefono']) ? pg_escape_string($conn, $input['telefono']) : null;
    
    if (empty($nombre) || empty($apellido) || empty($email) || empty($dni) || empty($telefono)) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "INSERT INTO persona (nombre, apellido, email, dni, telefono) VALUES ($1, $2, $3, $4, $5)";
    $params = array($nombre, $apellido, $email, $dni, $telefono);
    $result = pg_query_params($conn, $query, $params);
    
    if ($result) {
        $personaId = pg_last_oid($result);
        sendResponse(201, ['id' => $personaId, 'message' => 'Persona creada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear la persona']);
    }
}



// Actualizar una persona
elseif ($method === 'PUT' && preg_match('/^personas\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
   $input = json_decode(file_get_contents('php://input'), true);

    $nombre = $conn->real_escape_string($input['nombre']);
    $apellido = $conn->real_escape_string($input['apellido']);
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    $dni = $conn->real_escape_string($input['dni']);
    $telefono = $conn->real_escape_string($input['telefono']);
    
    if (!$nombre || !$apellido || !$email || !$dni || !$telefono) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE persona SET Nombre = '$nombre', Apellido = '$apellido', Email = '$email', DNI = '$dni', telefono = '$telefono' WHERE IDPersona = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Persona actualizada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar la persona']);
    }
}

// Eliminar una persona
elseif ($method === 'DELETE' && preg_match('/^personas\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Persona WHERE IDPersona = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Persona eliminada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar la persona']);
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
