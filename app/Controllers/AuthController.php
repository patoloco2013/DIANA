<?php
declare(strict_types=1);

namespace Diana\Controllers;

use Diana\Core\Auth;
use Diana\Core\Controller;
use Diana\Core\View;

/** Inicio y cierre de sesión. Rutas públicas (sin RBAC). */
final class AuthController extends Controller
{
    public const MODULO = '';

    public function index(): void
    {
        $this->login();
    }

    public function login(): void
    {
        if (Auth::verificado()) {
            redirigir('dashboard');
        }

        $error = null;
        if ($this->esPost()) {
            $error = Auth::entrar(
                (string) $this->post('usuario', ''),
                (string) ($_POST['password'] ?? ''),
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
            if ($error === null) {
                redirigir('dashboard');
            }
        }

        // Vista sin layout: pantalla completa de acceso
        echo View::parcial('auth/login', ['error' => $error]);
    }

    public function salir(): void
    {
        Auth::salir();
        redirigir('auth/login');
    }

    /** Cambio de colegio activo (solo superadmin). */
    public function colegio(): void
    {
        if ($this->esPost() && Auth::cambiarColegio((int) ($_POST['colegio_id'] ?? 0))) {
            flash('success', 'Colegio activo cambiado.');
        } else {
            flash('warning', 'No fue posible cambiar de colegio.');
        }
        redirigir('dashboard');
    }
}
