<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'src',
        'length',
        'extension',
        'slug',
        's3_url',
        'dimensions'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subtitles()
    {
        return $this->hasOne(Subtitles::class);
    }
}
