<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\ApiClient;

/** Valida el formulario y delega la creación efectiva de cuentas a FastAPI. */
final class RegisterController
{
    public function index(): void
    {
        if (!isset($_SESSION['register_csrf'])) {
            $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
        }
        [$formMessage, $formSuccess] = self::handle();
        $csrfToken = $_SESSION['register_csrf'];
        require __DIR__ . '/../../Views/Auth/register.php';
    }

    public static function handle(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['', false];
        }

        if (!hash_equals((string) ($_SESSION['register_csrf'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))
            || !isset($_POST['csrf_token']) || $_POST['csrf_token'] === '') {
            http_response_code(403);
            return ['No se pudo validar la solicitud. Actualiza la página e inténtalo de nuevo.', false];
        }

        $license = trim((string) ($_POST['idoneidad'] ?? ''));
        $email = trim((string) ($_POST['correo'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $termsAccepted = isset($_POST['terminos']) && $_POST['terminos'] === 'on';

        if (!$termsAccepted || $license === '' || $email === '' || $password === '') {
            http_response_code(422);
            return ['Completa los campos obligatorios y acepta los términos.', false];
        }
        if (strlen($license) > 100 || strlen($email) > 254
            || strlen($password) > 128 || strlen($password) < 8
            || !preg_match('/[^\p{L}\p{N}]/u', $password)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            return ['Verifica el correo, los límites de longitud y la contraseña (mínimo 8 caracteres y un carácter especial).', false];
        }

        // La API verifica la idoneidad, impide duplicados y aplica las políticas de contraseña.
        $result = (new ApiClient())->postJson('/auth/register', [
            'numero_idoneidad' => $license,
            'correo' => $email,
            'password' => $password,
        ]);
        $status = $result['status'];

        if ($result['body'] === null || $status === 0 || $status >= 500) {
            http_response_code(502);
            return ['El registro no está disponible en este momento. Intenta más tarde.', false];
        }
        if ($status === 409) {
            http_response_code(409);
            return ['El usuario o correo ya está registrado.', false];
        }
        if ($status < 200 || $status >= 300) {
            http_response_code(422);
            return ['No se pudo completar el registro. Verifica tus datos e idoneidad.', false];
        }

        $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
        return ['Solicitud de registro procesada correctamente.', true];
    }
}
