<?php
require __DIR__ . '/enviar_email.php'; // se você substituiu o arquivo, já contem a função

$dest = 'ricardo.quadros@gmail.com';
$nome = 'Ricardo Quadros';
$id = 12345;
$data = date('d/m/Y H:i:s');

$result = enviarEmailProtocolo($dest, $nome, $id, $data, true); // debug = true

header('Content-Type: text/plain; charset=utf-8');
print_r($result);
