<?php
declare(strict_types=1);

namespace Controller;

final class PageController
{
    public function handleRequest(): void
    {
        $page = $_GET['page'] ?? 'dashboard';

        if ($page === 'mecaniciens') {
            $this->backMecaniciens();
            return;
        }

        if ($page === 'formations') {
            $this->backFormations();
            return;
        }

        $this->frontDashboard();
    }

    public function frontDashboard(): void
    {
        include __DIR__ . '/../view/front/dashboard.html';
    }

    public function backMecaniciens(): void
    {
        include __DIR__ . '/../view/back/mecaniciens.html';
    }

    public function backFormations(): void
    {
        include __DIR__ . '/../view/back/formations.html';
    }
}
