<?php

namespace App\Actions\SportCategories;

use App\Models\SportCategory;
use App\Services\SportCategoryService;

class UpdateSportCategory
{
    public function handle(SportCategory $sportCategory, array $data, ?SportCategoryService $service = null): SportCategory
    {
        $service = $service ?? app(SportCategoryService::class);
        return $service->updateSportCategory($sportCategory, $data);
    }
}
