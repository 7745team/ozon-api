<?php

namespace Team7745\OzonApi\Console\Commands;

use Team7745\OzonApi\Base\Command;
use Team7745\OzonApi\Models\OzonCategory;

class SyncCategories extends Command
{
    protected $signature = 'ozon:sync-categories';

    public function handle()
    {
        $startTime = now();
        $this->syncCategories();
        $this->fillCustomFields();
        $endTime = now();

        return $this->success($startTime, $endTime);
    }

    public function syncCategories()
    {
        $categoriesTreeArray = $this->getCategoriesTreeArray();
        foreach ($categoriesTreeArray as $category) {
            if (!$category['disabled']) {
                $this->addCategory(
                    $category['description_category_id'],
                    $category['category_name'],
                    $category['type_id'],
                    $category['children']
                );
            }
        }
    }

    public function getCategoriesTreeArray()
    {
        $response = $this->ozonApi->getCategoriesTree();

        $dataArray = [];
        if ($response) {
            $dataArray = json_decode($response, true);
        }

        return $dataArray['result'] ?? [];
    }

    private function addCategory($categoryId, $categoryName, $typeId, $childrenArray = null, $parentCategoryId = 0)
    {
        $category = OzonCategory::firstOrCreate(['id' => $categoryId]);
        if ($category->getOriginal('name') != $categoryName || $category->getOriginal(
                'parent_id'
            ) != $parentCategoryId) {
            $category->update([
                'name' => $categoryName,
                'parent_id' => $parentCategoryId,
                'type_id' =>  $typeId,
            ]);
        }

        if ($childrenArray) {
            foreach ($childrenArray as $subCategory) {
                if (!$subCategory['disabled']) {
                    $this->addCategory(
                        $subCategory['description_category_id'] ?: $subCategory['type_id'],
                        $subCategory['category_name'] ?: $subCategory['type_name'],
                        $subCategory['type_id'],
                        $subCategory['children'],
                        $categoryId
                    );
                }
            }
        }
    }

    public function fillCustomFields()
    {
        $categories = OzonCategory::with(['children', 'parent.parent'])->get();

        $categories->each(function ($category) {
            $category->full_name = $category->getFullName(';');
            $category->last_node = $category->children->count() == 0;
            $category->save();
        });
    }
}
