<?php
// libros.php

// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST');
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

// Procesar la solicitud y generar respuestas
//optirne todos lo libros
if ($method === 'GET' && $route === 'libros') {
    $query = "SELECT * FROM libro";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $libros = array();
    
        while ($row = pg_fetch_assoc($result)) {
            $libros[] = $row;
        }
    
        sendResponse(200, $libros);
    } else {
        sendResponse(500, ['error' => 'Error al obtener los libros']);
    }
}

// Obtener un libro específico
elseif ($method === 'GET' && preg_match('/^libros\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM libro WHERE idlibro = $id";
    $result = pg_query($conn, $query);
    
    if (pg_num_rows($result) === 1) {
        $libro = pg_fetch_assoc($result);
        sendResponse(200, $libro);
    } else {
        sendResponse(404, ['error' => 'Libro no encontrado']);
    }
}

//crea nuevo libro
elseif ($method === 'POST' && $route === 'libros') {
    $input = json_decode(file_get_contents('php://input'), true);

    $nombreLibro = isset($input['nombrelibro']) ? pg_escape_string($conn, $input['nombrelibro']) : null;
    $isbn = isset($input['isbn']) ? pg_escape_string($conn, $input['isbn']) : null;
    $idAutor = isset($input['idautor']) ? pg_escape_string($conn, $input['idautor']) : null;
    $imagen = isset($input['imagen']) ? pg_escape_string($conn, $input['imagen']) : null;
    $descripcion = isset($input['descripcion']) ? pg_escape_string($conn, $input['descripcion']) : null;
    
    if (empty($nombreLibro) || empty($isbn) || empty($idAutor) || empty($imagen) || empty($descripcion)) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "INSERT INTO libro (nombrelibro, isbn, idautor, imagen, descripcion) VALUES ($1, $2, $3, $4, $5)";
    $params = array($nombreLibro, $isbn, $idAutor, $imagen, $descripcion);
    $result = pg_query_params($conn, $query, $params);
    
    if ($result) {
        $libroId = pg_last_oid($result);
        sendResponse(201, ['id' => $libroId, 'message' => 'Libro creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el libro']);
    }
}


// Actualizar un libro
elseif ($method === 'PUT' && preg_match('/^libros\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $nombreLibro = pg_escape_string($conn, $input['nombrelibro']);
    $isbn = pg_escape_string($conn, $input['isbn']);
    $idAutor = pg_escape_string($conn, $input['idautor']);
    $imagen = pg_escape_string($conn, $input['imagen']);
    $descripcion = pg_escape_string($conn, $input['descripcion']);
    
    if (!$nombreLibro || !$isbn || !$idAutor) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE libro SET nombrelibro = '$nombreLibro', isbn = '$isbn', idautor = $idAutor, imagen = '$imagen', descripcion = '$descripcion' WHERE idlibro = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Libro actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el libro']);
    }
}

// Eliminar un libro
elseif ($method === 'DELETE' && preg_match('/^libros\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM libro WHERE idlibro = $id";
    $result = pg_query($conn, $query);
    
    if ($result) {
        sendResponse(200, ['message' => 'Libro eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el libro']);
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
