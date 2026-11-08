<?php


final class Response
{
    public static function json(
        array $datos,
        int $codigo = 200
    ): never {
        http_response_code($codigo);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}