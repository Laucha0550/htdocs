<?php
// autenticacion.php
require 'clave.php';
require 'vendor/autoload.php';
use Firebase\JWT\JWT;



$key = SECRET_KEY;

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
if ($method === 'POST' && $route === 'autenticacion') {
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
else {
    sendResponse(404, ['error' => 'Ruta no encontrada']);
}

function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
pg_close($conn);
?>