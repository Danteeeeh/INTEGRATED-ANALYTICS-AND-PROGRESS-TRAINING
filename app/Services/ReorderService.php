<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReorderService
{
    public function reorder(string|Model $modelClass, array $orderedIds, ?string $scopeColumn = null, mixed $scopeValue = null): void
    {
        if (is_object($modelClass)) {
            $modelClass = get_class($modelClass);
        }

        if (empty($orderedIds)) {
            return;
        }

        DB::transaction(function () use ($modelClass, $orderedIds, $scopeColumn, $scopeValue) {
            foreach ($orderedIds as $index => $id) {
                $query = $modelClass::query();

                if ($scopeColumn !== null) {
                    $query->where($scopeColumn, $scopeValue);
                }

                $model = $query->find($id);

                if ($model) {
                    $model->update(['position' => $index]);
                }
            }
        });
    }
}
