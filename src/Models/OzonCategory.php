<?php

namespace Team7745\OzonApi\Models;

use Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Team7745\OzonApi\Base\Model;

class OzonCategory extends Model
{
    use SoftDeletes;

    protected $table = 'ozon_categories';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('sortByName', function (Builder $builder) {
            return $builder->orderBy('name');
        });
    }

    public function parent(): HasOne
    {
        return $this->hasOne(OzonCategory::class, 'id', 'parent_id');
    }

    public function isParent(): bool
    {
        return $this->hasChildren();
    }

    public function children(): HasMany
    {
        return $this->hasMany(OzonCategory::class, 'parent_id', 'id');
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(OzonAttribute::class, 'ozon_category_attribute')
            ->withPivot(Schema::getColumnListing('ozon_category_attribute'));
    }

    public function getFullName($separator = ' > ', $appendSelf = true): string
    {
        $parents = collect($appendSelf ? [$this] : []);
        $this->appendParentFor($parents, $this);
        return implode($separator, $parents->pluck('name')->toArray());
    }

    protected function appendParentFor(Collection &$parents, OzonCategory $category): Collection
    {
        if($category->parent) {
            $parents->prepend($category->parent);

            if ($category->parent->parent) {
                $this->appendParentFor($parents, $category->parent);
            }
        }
        return $parents;
    }

    public function containsAttributeId($attributeId): bool
    {
        return  $this->attributes()->wherePivot('ozon_attribute_id', $attributeId)->count() > 0;
    }
}
