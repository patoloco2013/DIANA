<?php
declare(strict_types=1);

namespace Diana\Core;

/**
 * Controlador base: expone utilidades comunes a los módulos.
 * Cada controlador declara su módulo para el control de permisos.
 */
abstract class Controller
{
    /** Módulo para RBAC; '' = público (solo Auth). */
    public const MODULO = '';

    protected function vista(string $vista, array $datos = []): void
    {
        $datos['titulo'] ??= cfg('app.nombre');
        View::render($vista, $datos);
    }

    protected function esPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Valor POST recortado (cadena) o nulo si viene vacío. */
    protected function post(string $campo, ?string $porDefecto = null): ?string
    {
        $valor = trim((string) ($_POST[$campo] ?? ''));
        return $valor === '' ? $porDefecto : $valor;
    }

    protected function colegioId(): int
    {
        return Auth::colegioId();
    }

    protected function usuarioId(): int
    {
        return (int) (Auth::usuario()['id'] ?? 0);
    }
}
