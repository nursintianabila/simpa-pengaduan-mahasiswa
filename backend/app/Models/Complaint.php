<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id',
    'category_id',
    'type',
    'title',
    'description',
    'attachment',

    'ai_summary',
    'ai_analysis',
    'ai_recommendation',
    'ai_priority',
    'ai_sentiment',

    'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }
}