<?php
declare(strict_types=1);

namespace Diana\Core;

/**
 * Enrutador por convención: /socios/editar/5
 *   → Diana\Controllers\SociosController::editar('5')
 * Antes de despachar exige sesión iniciada, permiso del módulo
 * y token CSRF válido en cualquier POST.
 */
final class Router
{
    public function despachar(string $ruta): void
    {
        $ruta = trim($ruta, '/');
        $partes = $ruta === '' ? [] : explode('/', $ruta);

        $seccion = strtolower($partes[0] ?? 'dashboard');
        $accion  = $partes[1] ?? 'index';
        $params  = array_slice($partes, 2);

        // Solo nombres alfanuméricos: nada de rutas raras
        if (!preg_match('/^[a-z0-9_]+$/', $seccion) || !preg_match('/^[a-zA-Z0-9_]+$/', $accion)) {
            $this->noEncontrado();
        }

        $clase = 'Diana\\Controllers\\' . str_replace('_', '', ucwords($seccion, '_')) . 'Controller';
        if (!class_exists($clase) || !method_exists($clase, $accion)) {
            $this->noEncontrado();
        }
        $metodo = new \ReflectionMethod($clase, $accion);
        if (!$metodo->isPublic() || $metodo->isStatic() || str_starts_with($accion, '__')) {
            $this->noEncontrado();
        }

        // --- Autenticación ------------------------------------------------
        $esPublica = $seccion === 'auth';
        if (!$esPublica && !Auth::verificado()) {
            redirigir('auth/login');
        }

        // --- CSRF en todo POST -------------------------------------------
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !Csrf::validar()) {
            http_response_code(419);
            exit('Sesión expirada o petición no válida. Regrese y vuelva a intentar.');
        }

        // --- Permisos por módulo -----------------------------------------
        $modulo = $clase::MODULO;
        if (!$esPublica && $modulo !== '' && !Auth::puede($modulo)) {
            http_response_code(403);
            View::render('errores/403', ['titulo' => 'Sin permiso']);
            exit;
        }

        try {
            (new $clase())->{$accion}(...$params);
        } catch (\ArgumentCountError) {
            $this->noEncontrado();
        }
    }

    private function noEncontrado(): never
    {
        http_response_code(404);
        if (Auth::verificado()) {
            View::render('errores/404', ['titulo' => 'No encontrado']);
        } else {
            echo 'Página no encontrada.';
        }
        exit;
    }
}
