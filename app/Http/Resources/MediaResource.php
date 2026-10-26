<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'libelle' => $this->libelle,
            'description' => $this->description,
            'url' => $this->url,
            'type' => $this->type->value,
        ];
    }
}
