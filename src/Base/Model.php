<?php

namespace Tdkomplekt\OzonApi\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Model extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;
}
