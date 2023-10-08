<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $primaryKey = 'id';
    protected $foreignKey = 'admin_id';

    protected $fillable = [
        'brand_name',
        'admin_id',
        'brand_background_color',
        'brand_background_image',
        'brand_theme_color',
        'brand_logo',
        'message',
        'redeeming_point',
        'custom_message',
        'form_message',
        'show_try_again',
        'try_again_text',
        'limit_scan'
    ];
    public function admin(){
        return $this->hasOne(Admin::class);
    }

}
