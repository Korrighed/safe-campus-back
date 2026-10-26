<?php

namespace App\Http\Controllers;

use App\Http\Resources\SousThemeResource;
use App\Models\Contact;
use App\Models\Histoire;
use App\Models\Media;
use App\Models\SousTheme;
use App\Models\Telephone;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SousThemeController extends Controller
{
    public function show(SousTheme $sousTheme): SousThemeResource
    {
        abort_unless($sousTheme->actif, 404);

        $sousTheme->load([
            'theme:id,ref,libelle_court',
            /** @param BelongsToMany<Contact, SousTheme> $query */
            'contacts' => fn ($query) => $query->actif(),
            /** @param HasMany<Telephone, Contact> $query */
            'contacts.telephones' => fn ($query) => $query->actif(),
            /** @param BelongsToMany<Media, SousTheme> $query */
            'documents' => fn ($query) => $query->actif(),
            /** @param BelongsToMany<Histoire, SousTheme> $query */
            'histoires' => fn ($query) => $query->publiee()->orderBy('histoires.titre'),
        ]);

        return new SousThemeResource($sousTheme);
    }
}
