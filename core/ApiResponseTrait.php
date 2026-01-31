<?php

/**
 * 🔹 Trait: ApiResponseTrait
 * يوفر استجابة موحدة لكل الـ APIs في النظام.
 * يمكن تضمينه في أي Controller بسهولة باستخدام:
 *   use ApiResponseTrait;
 */

trait ApiResponseTrait
{
    protected function respond(
        bool $success,
        string $message,
        $data = null,
        $errors = null,
        int $statusCode = 200
    ): array {

        http_response_code($statusCode);

        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors
        ];
    }
}
