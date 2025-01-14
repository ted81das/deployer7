<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CliCommandSection extends Model
{
    //
use HasFactory, HasUuid;

    protected $fillable = ['name', 'slug', 'description'];

    public function commands()
    {
        return $this->hasMany(CliCommand::class, 'command_section_id');
    }


}
