<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CliCommand extends Model
{
    //

se HasFactory, HasUuid;

    protected $fillable = [
        'cli_framework',
        'slug',
        'label',
        'command_section_id',
        'command',
        'allowed_server_types',
        'is_dangerous',
        'requires_sudo',
        'parameters'
    ];

    protected $casts = [
        'allowed_server_types' => 'array',
        'parameters' => 'array',
        'is_dangerous' => 'boolean',
        'requires_sudo' => 'boolean'
    ];

    public function section()
    {
        return $this->belongsTo(CliCommandSection::class, 'command_section_id');
    }



}
