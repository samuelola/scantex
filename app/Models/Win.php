<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Win extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $foreignKey = 'prize_id';

    protected $fillable = [
        'name','phone',
        'admin_id','prize_id'
    ];

    public function prize(){
        return $this->hasOne(Prize::class);
    }
}
