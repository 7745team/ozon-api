<?php

namespace Team7745\OzonApi\Traits;

use Team7745\OzonApi\Models\OzonCategory;

trait HasOzonCategory
{
    public function ozonCategory()
    {
        return $this->hasOne(OzonCategory::class);
    }
}
