<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RegistroMedicoRepository;

require_once __DIR__ . '/../Helpers/ui.php';   // e() y enviarPdf()

class PerfilController {

    private const PERFIL = '/index.php?c=perfil&a=';

    private ?array $perfil = null;   // caché de perfilPropio()

    /**
     * Muestra la pantalla principal del perfil (Solo lectura)
     * Ruta esperada: /Perfil
     */
    public function index() {
        $certificado = $this->datosCertificado();
        // Datos reales del médico con sesión (GET /medicos/{id}: perfil completo por ser el propio)
        $idMedico = \Auth::idMedico();
        $perfil = $this->perfilPropio();
        $medico = ($perfil['ok'] ?? false) ? $perfil['datos'] : [];
        $errorPerfil = $idMedico === null
            ? 'Vuelve a iniciar sesión para ver tu perfil.'
            : (($perfil['ok'] ?? false) ? '' : ($perfil['error'] ?: 'No se pudo cargar tu perfil.'));
        require_once __DIR__ . '/../Views/Client/Perfil/index.php';
    }

    /**
     * Muestra el formulario para modificar los datos
     * Ruta esperada: /Perfil/editar
     */
    public function editar() {
        $certificado = $this->datosCertificado();
        require_once __DIR__ . '/../Views/Client/Perfil/editar.php';
    }

    /**
     * Recibe los datos del formulario cuando el usuario hace clic en "Guardar"
     * Ruta esperada: POST a /Perfil/actualizar
     */
    public function actualizar() {
        header("Location: /index.php?c=perfil&a=index");
        exit();
    }

    /**
     * POST: sube el certificado de idoneidad (PDF) del médico con sesión.
     * La API (POST /medicos/{id}/certificado) valida el PDF y que sea su propio perfil.
     */
    public function subirCertificado(): never {
        $idMedico = \Auth::idMedico();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $idMedico === null) {
            $this->volverConMensaje('error', 'Inicia sesión como médico para subir tu certificado.');
        }
        if (!hash_equals((string) ($_SESSION['perfil_csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
            $this->volverConMensaje('error', 'La sesión del formulario venció. Intenta de nuevo.');
        }
        $archivo = $_FILES['certificado_idoneidad'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->volverConMensaje('error', 'Elige el PDF del certificado.');
        }

        $resultado = (new RegistroMedicoRepository())->subirCertificado($idMedico, $archivo);
        $resultado['ok']
            ? $this->volverConMensaje('success', 'Certificado de idoneidad guardado.')
            : $this->volverConMensaje('error', $resultado['error'] ?: 'No se pudo subir el certificado.');
    }

    /**
     * POST: sube la foto de perfil (JPG/PNG) del médico con sesión.
     * La API (POST /medicos/{id}/foto) valida la imagen y que sea su propio perfil.
     */
    public function subirFoto(): never {
        $idMedico = \Auth::idMedico();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $idMedico === null) {
            $this->volverConMensaje('error', 'Inicia sesión como médico para cambiar tu foto.');
        }
        if (!hash_equals((string) ($_SESSION['perfil_csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
            $this->volverConMensaje('error', 'La sesión del formulario venció. Intenta de nuevo.');
        }
        $archivo = $_FILES['foto'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->volverConMensaje('error', 'Elige la imagen de tu foto.');
        }

        $resultado = (new RegistroMedicoRepository())->subirFoto($idMedico, $archivo);
        $resultado['ok']
            ? $this->volverConMensaje('success', 'Foto de perfil guardada.')
            : $this->volverConMensaje('error', $resultado['error'] ?: 'No se pudo subir la foto.');
    }

    /** GET: muestra el PDF del certificado del médico con sesión. */
    public function certificado(): never {
        $idMedico = \Auth::idMedico();
        $resultado = $idMedico === null ? null : (new RegistroMedicoRepository())->descargarCertificado($idMedico);
        enviarPdf($resultado, 'certificado_idoneidad.pdf');
    }

    // URL del PDF y de la foto (si ya los subió), mensaje de la última subida y token del formulario
    private function datosCertificado(): array {
        if (empty($_SESSION['perfil_csrf'])) {
            $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
        }
        $flash = $_SESSION['perfil_flash'] ?? null;
        unset($_SESSION['perfil_flash']);

        $perfil = $this->perfilPropio();
        $datos = ($perfil['ok'] ?? false) ? $perfil['datos'] : [];
        return [
            'url'      => !empty($datos['certificado_pdf']) ? self::PERFIL . 'certificado' : null,
            'foto_url' => ($datos['foto_url'] ?? '') ?: null,
            'flash'    => is_array($flash) ? $flash : null,
            'csrf'     => $_SESSION['perfil_csrf'],
        ];
    }

    // Perfil completo del médico con sesión, pedido una sola vez por petición
    private function perfilPropio(): ?array {
        $idMedico = \Auth::idMedico();
        if ($idMedico !== null && $this->perfil === null) {
            $this->perfil = (new RegistroMedicoRepository())->obtener($idMedico);
        }
        return $this->perfil;
    }

    private function volverConMensaje(string $tipo, string $texto): never {
        $_SESSION['perfil_flash'] = ['tipo' => $tipo, 'texto' => $texto];
        header('Location: ' . self::PERFIL . 'editar');
        exit;
    }
}
