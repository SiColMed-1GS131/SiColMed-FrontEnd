<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CatalogosRepository;
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
        [$medico, $errorPerfil] = $this->medicoYError();
        require_once __DIR__ . '/../Views/Client/Perfil/index.php';
    }

    /**
     * Muestra el formulario para modificar los datos
     * Ruta esperada: /Perfil/editar
     */
    public function editar() {
        $certificado = $this->datosCertificado();
        [$medico, $errorPerfil] = $this->medicoYError();
        // Si el último guardado falló, se muestra lo que el médico había escrito
        $enviado = $_SESSION['perfil_formulario'] ?? [];
        unset($_SESSION['perfil_formulario']);
        $medico = (is_array($enviado) ? $enviado : []) + $medico;
        $direccion = $this->listasDireccion($medico);
        require_once __DIR__ . '/../Views/Client/Perfil/editar.php';
    }

    /**
     * POST: guarda el contacto y la dirección residencial del médico con sesión
     * (PATCH /medicos/{id}/perfil). Los demás campos del formulario son de solo lectura.
     */
    public function actualizar(): never {
        $idMedico = \Auth::idMedico();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $idMedico === null) {
            $this->volverConMensaje('error', 'Inicia sesión como médico para editar tu perfil.', 'datos');
        }
        if (!hash_equals((string) ($_SESSION['perfil_csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
            $this->volverConMensaje('error', 'La sesión del formulario venció. Intenta de nuevo.', 'datos');
        }

        $resultado = (new RegistroMedicoRepository())->actualizarPerfil($idMedico, $_POST);
        if ($resultado['ok']) {
            $this->volverConMensaje('success', empty($resultado['datos']['sin_cambios'])
                ? 'Tus cambios se guardaron.' : 'No había cambios que guardar.', 'datos');
        }

        $_SESSION['perfil_formulario'] = array_diff_key($_POST, ['csrf' => true]);
        $this->volverConMensaje('error', $this->mensajeError($resultado), 'datos');
    }

    /**
     * Distritos o corregimientos en JSON para los selects en cascada de "Editar perfil".
     * Igual que RegistroController::catalogo, que solo admite al administrador.
     * ?c=perfil&a=catalogo&recurso=distritos&id=<id_provincia> | recurso=corregimientos&id=<id_distrito>
     */
    public function catalogo(): never {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $catalogos = new CatalogosRepository();
        $lista = match (true) {
            $id === false => null,
            ($_GET['recurso'] ?? '') === 'distritos' => $catalogos->distritos($id),
            ($_GET['recurso'] ?? '') === 'corregimientos' => $catalogos->corregimientos($id),
            default => null,
        };
        header('Content-Type: application/json; charset=UTF-8');
        if ($lista === null) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Catálogo no válido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(array_map(static fn(array $i) => ['id' => $i['id'], 'nombre' => $i['nombre']], $lista), JSON_UNESCAPED_UNICODE);
        exit;
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

    // Datos reales del médico con sesión (GET /medicos/{id}: perfil completo por ser el propio)
    private function medicoYError(): array {
        $perfil = $this->perfilPropio();
        $medico = ($perfil['ok'] ?? false) ? $perfil['datos'] : [];
        $error = \Auth::idMedico() === null
            ? 'Vuelve a iniciar sesión para ver tu perfil.'
            : (($perfil['ok'] ?? false) ? '' : ($perfil['error'] ?: 'No se pudo cargar tu perfil.'));
        return [$medico, $error];
    }

    // Opciones de los selects de dirección: distritos y corregimientos solo de lo ya elegido;
    // el resto lo carga perfil.js con la acción catalogo (como en el formulario de registro)
    private function listasDireccion(array $medico): array {
        $catalogos = new CatalogosRepository();
        $provincia = (int) ($medico['residencia_provincia'] ?? 0);
        $distrito = (int) ($medico['residencia_distrito'] ?? 0);
        return [
            'provincias'     => $catalogos->provincias(),
            'distritos'      => $provincia > 0 ? $catalogos->distritos($provincia) : [],
            'corregimientos' => $distrito > 0 ? $catalogos->corregimientos($distrito) : [],
        ];
    }

    // Mensaje para la alerta: el error general de la API o el de cada campo, con su etiqueta
    private function mensajeError(array $resultado): string {
        $etiquetas = [
            'telefono_residencial' => 'Teléfono residencial', 'celular' => 'Celular',
            'email_personal' => 'Email personal', 'apartado_postal' => 'Apartado postal',
            'residencia_provincia' => 'Provincia', 'residencia_distrito' => 'Distrito',
            'residencia_corregimiento' => 'Corregimiento', 'residencia_calle' => 'Calle',
            'residencia_edificio' => 'Edificio', 'residencia_numero' => 'Número',
        ];
        $partes = [];
        foreach ($resultado['errores'] ?? [] as $campo => $mensaje) {
            $partes[] = ($etiquetas[$campo] ?? $campo) . ': ' . $mensaje;
        }
        $error = trim(($resultado['error'] ?? '') . ' ' . implode(' ', $partes));
        return $error !== '' ? $error : 'No se pudieron guardar tus cambios.';
    }

    // Perfil completo del médico con sesión, pedido una sola vez por petición
    private function perfilPropio(): ?array {
        $idMedico = \Auth::idMedico();
        if ($idMedico !== null && $this->perfil === null) {
            $this->perfil = (new RegistroMedicoRepository())->obtener($idMedico);
        }
        return $this->perfil;
    }

    // $seccion: 'datos' muestra el mensaje arriba del formulario; 'archivos', junto a foto y certificado
    private function volverConMensaje(string $tipo, string $texto, string $seccion = 'archivos'): never {
        $_SESSION['perfil_flash'] = ['tipo' => $tipo, 'texto' => $texto, 'seccion' => $seccion];
        header('Location: ' . self::PERFIL . 'editar');
        exit;
    }
}
