<?php

// app/Models/Team.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'color'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
