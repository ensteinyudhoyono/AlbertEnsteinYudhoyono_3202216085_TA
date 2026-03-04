<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'quantity',
        'description'
    ];

    public function rents()
    {
        return $this->belongsToMany(Rent::class, 'rent_items')->withPivot('quantity');
    }
}
