<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'name',
        'color',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
