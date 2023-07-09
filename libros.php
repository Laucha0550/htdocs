<?php
// libros.php

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

// Obtener todos los libros
if ($method === 'GET' && $route === 'libros') {
    $query = "SELECT * FROM libro";
    $result = pg_query($conn, $query);
    
    $libros = array();
    
    while ($row = pg_fetch_assoc($result)) {
        $libros[] = $row;
    }
    
    sendResponse(200, $libros);
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

// Crear un nuevo libro
elseif ($method === 'POST' && $route === 'libros') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios del libro
    $nombreLibro = pg_escape_string($conn, $input['nombrelibro']);
    $isbn = pg_escape_string($conn, $input['isbn']);
    $idAutor = pg_escape_string($conn, $input['idautor']);
    $imagen = pg_escape_string($conn, $input['imagen']);
    $descripcion = pg_escape_string($conn, $input['descripcion']);
    
    if (!$nombrelibro || !$isbn || !$idAutor || $imagen  || $descripcion) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar el nuevo libro en la base de datos
    $query = "INSERT INTO libro (nombrelibro, isbn, idautor, imagen, descripcion) VALUES ('$nombreLibro', '$isbn', $idAutor, '$imagen', '$descripcion')";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $libroId = pg_last_oid($conn);
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

// Cerrar la conexión a la base de datos
pg_close($conn);

?>
