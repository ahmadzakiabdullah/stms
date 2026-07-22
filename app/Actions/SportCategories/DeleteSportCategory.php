<?php

namespace App\Actions\SportCategories;

use App\Models\SportCategory;
use App\Services\SportCategoryService;

class DeleteSportCategory
{
    public function handle(SportCategory $sportCategory, ?SportCategoryService $service = null): void
    {
        $service = $service ?? app(SportCategoryService::class);
        $service->deleteSportCategory($sportCategory);
    }
}
