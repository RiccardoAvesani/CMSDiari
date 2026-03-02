<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateTypePageType extends Model
{
    use Blameable;

    protected $table = 'template_type_page_type';

    protected $fillable = [
        'template_type_id',
        'page_type_id',
        'position',
        'sort',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'position' => 'integer',
        'sort' => 'integer',
    ];

    public function templateType(): BelongsTo
    {
        return $this->belongsTo(TemplateType::class, 'template_type_id');
    }

    public function pageType(): BelongsTo
    {
        return $this->belongsTo(PageType::class, 'page_type_id');
    }
}
