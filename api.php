<?php

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

// Procesar la solicitud y generar respuestas

// Obtener todas las personas
if ($method === 'GET' && $route === 'personas') {
    $query = "SELECT * FROM Persona";
    $result = $conn->query($query);
    
    $personas = array();
    
    while ($row = $result->fetch_assoc()) {
        $personas[] = $row;
    }
    
    sendResponse(200, $personas);
}

// Obtener una persona específica
elseif ($method === 'GET' && preg_match('/^personas\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Persona WHERE IDPersona = $id";
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

    $nombre = $conn->real_escape_string($input['nombre']);
    $apellido = $conn->real_escape_string($input['apellido']);
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    $dni = $conn->real_escape_string($input['dni']);
    $telefono = $conn->real_escape_string($input['telefono']);
    
    if (!$nombre || !$apellido || !$email || !$dni || !$telefono) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "INSERT INTO Persona (Nombre, Apellido, Email, DNI, Telefono) VALUES ('$nombre', '$apellido', '$email', '$dni', '$telefono')";
    $result = $conn->query($query);
    
    if ($result === true) {
        $personaId = $conn->insert_id;
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
    
    $query = "UPDATE Persona SET Nombre = '$nombre', Apellido = '$apellido', Email = '$email', DNI = '$dni', Telefono = '$telefono' WHERE IDPersona = $id";
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

// Obtener todos los usuarios
if ($method === 'GET' && $route === 'usuarios') {
    $query = "SELECT * FROM Usuario";
    $result = $conn->query($query);
    
    $usuarios = array();
    
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    
    sendResponse(200, $usuarios);
}

// Obtener un usuario específico
elseif ($method === 'GET' && preg_match('/^usuarios\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Usuario WHERE IDUsuario = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        sendResponse(200, $usuario);
    } else {
        sendResponse(404, ['error' => 'Usuario no encontrado']);
    }
}

// Crear un nuevo usuario
elseif ($method === 'POST' && $route === 'usuarios') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios del usuario
    $nombreUsuario = $conn->real_escape_string($input['nombreUsuario']);
    $contrasena = $conn->real_escape_string($input['contrasena']);
    
    if (!$nombreUsuario || !$contrasena) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar el nuevo usuario en la base de datos
    $query = "INSERT INTO Usuario (NombreUsuario, Contrasena) VALUES ('$nombreUsuario', '$contrasena')";
    $result = $conn->query($query);
    
    if ($result === true) {
        $usuarioId = $conn->insert_id;
        sendResponse(201, ['id' => $usuarioId, 'message' => 'Usuario creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el usuario']);
    }
}

// Actualizar un usuario
elseif ($method === 'PUT' && preg_match('/^usuarios\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $nombreUsuario = $conn->real_escape_string($input['nombreUsuario']);
    $contrasena = $conn->real_escape_string($input['contrasena']);
    
    if (!$nombreUsuario || !$contrasena) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE Usuario SET NombreUsuario = '$nombreUsuario', Contrasena = '$contrasena' WHERE IDUsuario = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Usuario actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el usuario']);
    }
}

// Eliminar un usuario
elseif ($method === 'DELETE' && preg_match('/^usuarios\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Usuario WHERE IDUsuario = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Usuario eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el usuario']);
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
