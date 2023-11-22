<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\product;

class category extends Model
{
    use HasFactory;


    public function product() : HasOne
    {
        return $this->hasOne(product::class,'category_id');
    }

    // ------------------------------------- Another Model -----------------------------
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(category::class,'category_id','id');
    }
}

?>