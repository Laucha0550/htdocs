<?php

$url = 'http://localhost/api.php?route=personas';
$data = array(
    'nombre' => 'John',
    'apellido' => 'Doe',
    'email' => 'johndoe@example.com',
    'dni' => '123456789',
    'telefono' => '1234567890'
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

// Manejar la respuesta
if ($response === false) {
    echo 'Error al realizar la solicitud.';
} else {
    $responseData = json_decode($response, true);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($statusCode === 201) {
        echo 'Persona creada correctamente. ID: ' . $responseData['id'];
    } else {
        echo 'Error al crear la persona: ' . $responseData['error'];
    }
}

?>