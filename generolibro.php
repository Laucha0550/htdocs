<?php
// generolibro.php

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

// Obtener todos los géneros de libros
if ($method === 'GET' && $route === 'generoslibro') {
    $query = "SELECT * FROM GeneroLibro";
    $result = $conn->query($query);
    
    $generosLibro = array();
    
    while ($row = $result->fetch_assoc()) {
        $generosLibro[] = $row;
    }
    
    sendResponse(200, $generosLibro);
}

// Obtener los géneros de un libro específico
elseif ($method === 'GET' && preg_match('/^generoslibro\/(\d+)$/', $route, $matches)) {
    $idLibro = $matches[1];
    
    $query = "SELECT Genero.IDGenero, Genero.NombreGenero FROM Genero
              INNER JOIN GeneroLibro ON Genero.IDGenero = GeneroLibro.IDGenero
              WHERE GeneroLibro.IDLibro = $idLibro";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $generos = array();
        while ($row = $result->fetch_assoc()) {
            $generos[] = $row;
        }
        sendResponse(200, $generos);
    } else {
        sendResponse(404, ['error' => 'No se encontraron géneros para el libro']);
    }
}

// Asignar un género a un libro
elseif ($method === 'POST' && $route === 'generoslibro') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios
    $idGenero = $conn->real_escape_string($input['idGenero']);
    $idLibro = $conn->real_escape_string($input['idLibro']);
    
    if (!$idGenero || !$idLibro) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar la relación en la tabla GeneroLibro
    $query = "INSERT INTO GeneroLibro (IDGenero, IDLibro) VALUES ($idGenero, $idLibro)";
    $result = $conn->query($query);
    
    if ($result === true) {
        $generoLibroId = $conn->insert_id;
        sendResponse(201, ['id' => $generoLibroId, 'message' => 'Género asignado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al asignar el género']);
    }
}

//Actualizar la relación entre un género y un libro específico
elseif ($method === 'PUT' && preg_match('/^generoslibro\/(\d+)$/', $route, $matches)) {
    $idGeneroLibro = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idGenero = $conn->real_escape_string($input['idGenero']);
    $idLibro = $conn->real_escape_string($input['idLibro']);
    
    if (!$idGenero || !$idLibro) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE GeneroLibro SET IDGenero = $idGenero, IDLibro = $idLibro WHERE IDGeneroLibro = $idGeneroLibro";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Relación actualizada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar la relación']);
    }
}

// Eliminar la relación entre un género y un libro específico
elseif ($method === 'DELETE' && preg_match('/^generoslibro\/(\d+)$/', $route, $matches)) {
    $idGeneroLibro = $matches[1];
    
    $query = "DELETE FROM GeneroLibro WHERE IDGeneroLibro = $idGeneroLibro";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Relación eliminada correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar la relación']);
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
