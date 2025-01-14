<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
//App\Models\HasFactory;
class CliFramework extends Model
{
    //

 //use HasUuids;

    protected $fillable = ['uuid','name', 'slug','startcharacters' ,'description'];

    public function commands()
    {
        return $this->hasMany(CliCommand::class);
    }

}
