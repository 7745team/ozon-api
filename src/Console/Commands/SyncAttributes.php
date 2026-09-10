<?php

namespace Team7745\OzonApi\Console\Commands;

use DB;
use Team7745\OzonApi\Base\Command;
use Team7745\OzonApi\Models\OzonAttribute;
use Team7745\OzonApi\Models\OzonCategory;

class SyncAttributes extends Command
{
    protected $signature = 'ozon:sync-attributes';

    public function handle(): int
    {
        $startTime = now();
        $this->syncAttributes();
        $endTime = now();

        return $this->success($startTime, $endTime);
    }

    protected function syncAttributes()
    {
        foreach (OzonCategory::where('type_id', '>', 0)->get() as $category) {
            $attributesTreeArray = $this->getAttributes($category->parent_id, $category->type_id);

            foreach ($attributesTreeArray as $attributeData) {
                $attribute = OzonAttribute::firstOrCreate([
                    'id' => $attributeData['id'],
                ], [
                    'name' => $attributeData['name'],
                    'description' => $attributeData['description'],
                    'type' => $attributeData['type'],
                    'is_collection' => $attributeData['is_collection'],
                    'dictionary_id' => $attributeData['dictionary_id'],
                ]);

                DB::table('ozon_category_attribute')->updateOrInsert([
                    'ozon_category_id' => $category->type_id,
                    'ozon_attribute_id' => $attribute->id,
                ], [
                    'is_required' => $attributeData['is_required'],
                    'group_id' => $attributeData['group_id'],
                    'group_name' => $attributeData['group_name'],
                ]);
            }
        }
    }

    protected function getAttributes($categoryId, $typeId): ?array
    {
        $response = $this->ozonApi->getCategoryAttributes($categoryId, $typeId);

        $dataArray = [];
        if ($response) {
            $dataArray = json_decode($response, true);
        }

        return $dataArray['result'] ?? [];
    }
}
