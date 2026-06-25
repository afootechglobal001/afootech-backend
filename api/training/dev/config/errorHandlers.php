<?php
/**
 * Base API Exception
 */
class ApiException extends Exception
{
    protected int $statusCode;

    public function __construct(
        string $message = 'Application error',
        int $statusCode = 500
    ) {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

/**
 * 400 - Bad Request
 */
class BadRequestException extends ApiException
{
    public function __construct(string $message = 'Bad request')
    {
        parent::__construct($message, 400);
    }
}

/**
 * 401 - Unauthorized
 */
class UnauthorizedException extends ApiException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }
}

/**
 * 403 - Forbidden
 */
class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message, 403);
    }
}

/**
 * 404 - Not Found
 */
class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404);
    }
}

class ConflictException extends ApiException
{
    public function __construct(string $message = 'Conflict')
    {
        parent::__construct($message, 409);
    }
}





class ErrorHandler
{
    public static function handle(Throwable $e): void
    {
        $statusCode = self::getHttpStatusCode($e);

        // Log full error internally
        error_log(self::formatErrorForLog($e));

        // Hide sensitive details in production
        $isProduction = false; // change based on your env config

        $response = [
            'response' => $statusCode,
            'success'  => false,
            'message'  => $isProduction
                ? self::getSafeMessage($statusCode, $e->getMessage())
                : $e->getMessage(),
        ];

        if (!$isProduction) {
            $response['error'] = [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response);

        exit;
    }

    private static function getHttpStatusCode(Throwable $e): int
	{
		// If it's our custom API exception
		if ($e instanceof ApiException) {
			return $e->getStatusCode();
		}

		// Database errors
		if ($e instanceof mysqli_sql_exception) {
			return 500;
		}

		// Fallback to valid exception code
		if ($e->getCode() >= 100 && $e->getCode() <= 599) {
			return $e->getCode();
		}

		return 500;
	}


    private static function getSafeMessage(int $statusCode, string $message): string
    {
        return match ($statusCode) {
            400 => $message ?: 'Bad request.',
            401 => $message ?: 'Unauthorized.',
            403 => $message ?: 'Forbidden.',
            404 => $message ?: 'Resource not found.',
            409 => $message ?: 'Conflict.',
            500 => $message ?: 'Internal server error.',
            default => $message ?: 'An unexpected error occurred.',
        };
    }

    private static function formatErrorForLog(Throwable $e): string
    {
        return sprintf(
            "[%s] %s in %s on line %d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }
}