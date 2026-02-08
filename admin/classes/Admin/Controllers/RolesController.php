<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\View;
use Admin\Repositories\RolesRepository;

final class RolesController
{
    private RolesRepository $roles;

    public function __construct(RolesRepository $roles)
    {
        $this->roles = $roles;
    }

    public function index(): void
    {
        View::render('roles.php', [
            'title' => 'Rollen',
            'roles' => $this->roles->getAll(),
        ]);
    }
}
