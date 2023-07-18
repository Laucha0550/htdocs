<?php
// generolibro.php

// Establecer encabezados para permitir el acceso desde diferentes dominios
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
//header('Access-Control-Allow-Headers: Content-Type');

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
//$conn->set_charset("utf8");

// Obtener todos los géneros de libros
if ($method === 'GET' && $route === 'rutagl') {
    $query = "SELECT * FROM generolibro";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $rutagl = array();
    
        while ($row = pg_fetch_assoc($result)) {
            $rutagl[] = $row;
        }
    
        sendResponse(200, $rutagl);
    } else {
        sendResponse(500, ['error' => 'Error al obtener las rutagl']);
    }
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
elseif ($method === 'POST' && $route === 'rutagl') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar los campos obligatorios
    $idGenero = isset($input['idgenero']) ? pg_escape_string($conn, $input['idgenero']) : null;
    $idLibro = isset($input['idlibro']) ? pg_escape_string($conn, $input['idlibro']) : null;
    
    if (!$idGenero || !$idLibro) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Insertar la relación en la tabla GeneroLibro
    $query = "INSERT INTO generolibro (idgenero, idlibro) VALUES ($1, $2) RETURNING idgenerolibro";
    $params = array($idGenero, $idLibro);
    $result = pg_query_params($conn, $query, $params);
    
    if ($result) {
        $row = pg_fetch_assoc($result);
        $generoLibroId = $row['idgenerolibro'];
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
pg_close($conn);

?>
