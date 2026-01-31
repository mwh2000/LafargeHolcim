<?php
function sendJson(array $res, int $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit; // مهم جدًا لإنهاء السكربت بعد طباعة JSON
}
