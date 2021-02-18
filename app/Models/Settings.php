<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'free_quality_level',
        'pro_quality_level',
        'free_upload_count',
        'pro_upload_count',
        'free_upload_size_limit',
        'pro_upload_size_limit',
    ];
}
