<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\Dashboard;

final class GetDashboardOverviewService
{
    /**
     * @return array{welcome_title: string, panels: list<array{id: int, title: string}>}
     */
    public function handle(string $userName): array
    {
        return [
            'welcome_title' => 'Hello, '.$userName,
            'panels' => [
                ['id' => 1, 'title' => 'Overview'],
                ['id' => 2, 'title' => 'Activity'],
                ['id' => 3, 'title' => 'Insights'],
            ],
        ];
    }
}
