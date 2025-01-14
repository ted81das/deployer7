<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GitProvider extends Model
{
    //
   use SoftDeletes;

    protected $fillable = [
        'name',
        'type', // github, gitlab, bitbucket
'label',       
 'base_url',
        'api_url',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function applicationTypes()
    {
        return $this->hasMany(ApplicationType::class);
    }

}
