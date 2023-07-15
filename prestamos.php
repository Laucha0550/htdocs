<?php
// prestamos.php

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

// Obtener todos los préstamos
if ($method === 'GET' && $route === 'prestamos') {
    $query = "SELECT * FROM Prestamo";
    $result = pg_query($conn, $query);
    
    if ($result) {
        $prestamos = array();
    
        while ($row = pg_fetch_assoc($result)) {
            $prestamos[] = $row;
        }
    
        sendResponse(200, $prestamos);
    } else {
        sendResponse(500, ['error' => 'Error al obtener los prestamos']);
    }
}

// Obtener un préstamo específico
elseif ($method === 'GET' && preg_match('/^prestamos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "SELECT * FROM Prestamo WHERE IDPrestamo = $id";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $prestamo = $result->fetch_assoc();
        sendResponse(200, $prestamo);
    } else {
        sendResponse(404, ['error' => 'Préstamo no encontrado']);
    }
}

// Crear un nuevo préstamo
elseif ($method === 'POST' && $route === 'prestamos') {
    $input = json_decode(file_get_contents('php://input'), true);

    $idStock = isset($input['idstock']) ? pg_escape_string($conn, $input['idstock']) : null;
    $idCliente = isset($input['idcliente']) ? pg_escape_string($conn, $input['idcliente']) : null;
    $idEmpleado = isset($input['idempleado']) ? pg_escape_string($conn, $input['idempleado']) : null;
    $fechaPrestamo = isset($input['fechaprestamo']) ? pg_escape_string($conn, $input['fechaprestamo']) : null;
    $fechaDevolucion = isset($input['fechadevolucion']) ? pg_escape_string($conn, $input['fechadevolucion']) : null;
    $fechaEntrega = isset($input['fechaentrega']) ? pg_escape_string($conn, $input['fechaentrega']) : null;
    $idMulta = isset($input['idmulta']) ? pg_escape_string($conn, $input['idmulta']) : null;
    
    // Verificar que los campos obligatorios no estén vacíos
    if (empty($idStock) || empty($idCliente) || empty($idEmpleado) || empty($fechaPrestamo) || empty($fechaDevolucion)) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    // Convertir las fechas al formato deseado en tu base de datos
    $fechaPrestamo = date('Y-m-d H:i:s', strtotime($fechaPrestamo));
    $fechaDevolucion = date('Y-m-d H:i:s', strtotime($fechaDevolucion));
    
    // Verificar si fechaEntrega y idMulta están vacíos y asignar NULL en su lugar
    $fechaEntrega = $fechaEntrega !== '' ? date('Y-m-d H:i:s', strtotime($fechaEntrega)) : null;
    $idMulta = $idMulta !== '' ? $idMulta : null;
    
    $query = "INSERT INTO prestamo (idstock, idcliente, idempleado, fechaprestamo, fechadevolucion, fechaentrega, idmulta) VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING idprestamo";
    $params = array($idStock, $idCliente, $idEmpleado, $fechaPrestamo, $fechaDevolucion, $fechaEntrega, $idMulta);
    $result = pg_query_params($conn, $query, $params);
    
    if ($result) {
        $row = pg_fetch_assoc($result);
        $prestamoId = $row['idprestamo'];
        sendResponse(201, ['id' => $prestamoId, 'message' => 'Préstamo creado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al crear el préstamo']);
    }
}


// Actualizar un préstamo
elseif ($method === 'PUT' && preg_match('/^prestamos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $input = json_decode(file_get_contents('php://input'), true);

    $idStock = $conn->real_escape_string($input['id_stock']);
    $idCliente = $conn->real_escape_string($input['id_cliente']);
    $idEmpleado = $conn->real_escape_string($input['id_empleado']);
    $fechaPrestamo = $conn->real_escape_string($input['fecha_prestamo']);
    $fechaDevolucion = $conn->real_escape_string($input['fecha_devolucion']);
    
    // Verificar que los campos obligatoriosno estén vacíos
    if (!$idStock || !$idCliente || !$idEmpleado || !$fechaPrestamo || !$fechaDevolucion) {
        sendResponse(400, ['error' => 'Datos incompletos o no válidos']);
    }
    
    $query = "UPDATE Prestamo SET IDStock = '$idStock', IDCliente = '$idCliente', IDEmpleado = '$idEmpleado', FechaPrestamo = '$fechaPrestamo', FechaDevolucion = '$fechaDevolucion' WHERE IDPrestamo = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Préstamo actualizado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al actualizar el préstamo']);
    }
}

// Eliminar un préstamo
elseif ($method === 'DELETE' && preg_match('/^prestamos\/(\d+)$/', $route, $matches)) {
    $id = $matches[1];
    
    $query = "DELETE FROM Prestamo WHERE IDPrestamo = $id";
    $result = $conn->query($query);
    
    if ($result === true) {
        sendResponse(200, ['message' => 'Préstamo eliminado correctamente']);
    } else {
        sendResponse(500, ['error' => 'Error al eliminar el préstamo']);
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
