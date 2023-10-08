<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'title',
        'quantity',
        'current_quantity',
        'image',
        'allocate_qty'
    ];
    protected $primaryKey = 'id';


    public function win(){
        return $this->belongsTo(Win::class);
    }
}
