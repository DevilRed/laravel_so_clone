<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Question extends Model
{
    use HasFactory;

    public $fillable = [
        'title',
        'slug',
        'body',
        'viewCount',
        'score',
        'tags',
        'user_id',
    ];
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class)->with('user');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
