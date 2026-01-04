<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function dataTable($paginated, $resourceClass)
    {
        return [
            'data' => $resourceClass::collection($paginated->items()),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ];
    }
}
