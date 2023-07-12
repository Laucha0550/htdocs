<?php
// usuarios.php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;

// Generar una clave secreta
$length = 16; // Longitud de clave en bytes (128 bits)
$key = bin2hex(random_bytes($length));

function verifyToken($token) {
    global $key; // Utilizar la clave secreta generada

    try {
        $decoded = JWT::decode($token, $key, array('HS256'));
        return true;
    } catch (Exception $e) {
        return false;
    }
}

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

// Obtener todos los usuarios
if ($method === 'GET' && $route === 'usuarios') {
    $query = "SELECT * FROM Usuario";
    $result = pg_query($conn, $query);
    
    $usuarios = array();
    
    while ($row = pg_fetch_assoc($result)) {
        $usuarios[] = $row;
    }
    
    sendResponse(200, $usuarios);
}

// Obtener un usuario específico
elseif ($method === 'GET' && preg_match('/^usuarios\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Usuario WHERE IDUsuario = $id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) === 1) {
        $usuario = pg_fetch_assoc($result);
        sendResponse(200, $usuario);
    } else {
        sendResponse(404, ['error' => 'Usuario no encontrado']);
    }
}
elseif ($method === 'POST' && $route === 'autenticacion') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios del usuario
    $nombreUsuario = pg_escape_string($conn, $input['nombreusuario']);
    $contrasena = pg_escape_string($conn, $input['contrasena']);
    
    if (!$nombreUsuario || !$contrasena) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Verificar si el usuario y la contraseña coinciden en la base de datos
    $query = "SELECT * FROM usuario WHERE nombreusuario = '$nombreUsuario' AND contrasena = '$contrasena'";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) === 1) {
        // Usuario autenticado correctamente
        $usuario = pg_fetch_assoc($result);
        
        // Generar el token de autenticación
        $token = JWT::encode(['usuarioId' => $usuario['idusuario']], $key);
        
        sendResponse(200, ['token' => $token]);
    } else {
        // Usuario no autenticado
        sendResponse(401, ['error' => 'Credenciales inválidas']);
    }
}
// Crear un nuevo usuario
elseif ($method === 'POST' && $route === 'usuarios') {
    // Verificar el token antes de procesar la solicitud
    $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $authorizationHeader);

    if (!verifyToken($token)) {
        sendResponse(401, ['error' => 'Token inválido']);
    }
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios del usuario
    $nombreUsuario = pg_escape_string($conn, $input['nombreusuario']);
    $contrasena = pg_escape_string($conn, $input['contrasena']);
    
    if (!$nombreUsuario || !$contrasena) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar el nuevo usuario en la base de datos
    $query = "INSERT INTO usuario (nombreusuario, contrasena) VALUES ('$nombreUsuario', '$contrasena')";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $usuarioId = pg_last_oid($result);
        sendResponse(201, ['id' => $usuarioId, 'message' => 'Usuario creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el usuario']);
    }
}


// Actualizar un usuario
elseif ($method === 'PUT' && preg_match('/^usuarios\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $nombreUsuario = pg_escape_string($conn, $input['nombreUsuario']);
    $contrasena = pg_escape_string($conn, $input['contrasena']);
    
    if (!$nombreUsuario || !$contrasena) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE Usuario SET NombreUsuario = '$nombreUsuario', Contrasena = '$contrasena' WHERE IDUsuario = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Usuario actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el usuario']);
    }
}

// Eliminar un usuario
elseif ($method === 'DELETE' && preg_match('/^usuarios\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Usuario WHERE IDUsuario = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Usuario eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el usuario']);
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
