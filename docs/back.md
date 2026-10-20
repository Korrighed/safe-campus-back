# Guide développement back-end

Stack : **Laravel 13** · **PHP 8.4** · **Filament 3** · **PostgreSQL 17**

> Filament est autonome : il ne dépend d'aucun asset de ce repo. L'UI principale est [Safe-Campus-front](../../Safe-Campus-front) (Nuxt 4 standalone, repo séparé), qui consomme `routes/api.php`. La route `/` renvoie un simple JSON de statut.

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/        Un contrôleur par ressource (PascalCase)
│   └── Requests/           Form Requests pour la validation
├── Models/                 Un modèle par table (Eloquent)
├── Services/               Logique métier extraite des contrôleurs
├── Policies/                Autorisation par ressource
└── Providers/Filament/     AdminPanelProvider — config du panel Filament

database/
├── migrations/             Une migration par changement de schéma
├── seeders/                Données de test
└── factories/              Factories pour les tests

routes/
├── web.php                 Minimal — health check `/`
├── api.php                 Routes JSON consommées par le front Nuxt
└── console.php             Commandes Artisan custom
```

---

## Conventions

### Contrôleurs

```php
// Un contrôleur = une ressource, 7 méthodes max (CRUD)
// Nommage : NomRessourceController

class HistoireController extends Controller
{
    public function index()   { }  // liste
    public function create()  { }  // formulaire création
    public function store()   { }  // sauvegarde création
    public function show()    { }  // détail
    public function edit()    { }  // formulaire édition
    public function update()  { }  // sauvegarde édition
    public function destroy() { }  // suppression
}
```

### Retour JSON

```php
// Collection via API Resource
return HistoireResource::collection($histoires);

// Réponse simple avec code HTTP
return response()->json(['message' => 'created'], 201);
```

### Modèles Eloquent

```php
class Histoire extends Model
{
    // Toujours définir $fillable ou $guarded
    protected $fillable = ['titre', 'description', 'etat'];

    // Casts pour les types complexes
    protected $casts = [
        'etat'       => EtatHistoire::class,  // Enum PHP 8.1+
        'meta'       => 'array',
    ];

    // Relations nommées explicitement
    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class);
    }
}
```

### Validation

```php
// Toujours via Form Request, jamais inline dans le contrôleur
class StoreHistoireRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'etat'        => ['required', Rule::enum(EtatHistoire::class)],
        ];
    }
}
```

---

## Base de données

### Migrations

```php
// Une migration = un changement atomique
// Nommage : create_xxx_table / add_xxx_to_xxx_table

Schema::create('scenes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('histoire_fk')->constrained('histoires')->cascadeOnDelete();
    $table->text('dialogue_text');
    $table->timestamps();
});
```

### Requêtes récursives (CTE PostgreSQL)

```php
// Pour parcourir le graphe Scene → Choix → Scene
$parcours = DB::select("
    WITH RECURSIVE parcours AS (
        SELECT scene_id, dialogue_text, 0 AS profondeur
        FROM scenes
        WHERE histoire_fk = ?

        UNION ALL

        SELECT s.scene_id, s.dialogue_text, p.profondeur + 1
        FROM scenes s
        JOIN choix c ON c.fk_next_scene = s.scene_id
        JOIN parcours p ON p.scene_id = c.fk_scene
    )
    SELECT * FROM parcours
", [$histoireId]);
```

### JSONB (PostgreSQL)

```php
// Utiliser le cast 'array' pour les colonnes JSONB
protected $casts = [
    'choice_sequence' => 'array',
];

// Requête sur JSONB en Eloquent
Scene::whereJsonContains('meta->tags', 'intro')->get();
```

---

## Routes

```php
// api.php — routes JSON pour le front Nuxt
Route::post('login', [AuthController::class, 'login']);

Route::get('themes', [ThemeController::class, 'index']);
Route::get('sous-themes/{sousTheme:ref}', [SousThemeController::class, 'show']);
Route::get('histoires/{histoire:ref}', [HistoireController::class, 'show']);
```

> Les routes de lecture sont publiques à ce jour. Les placer sous `auth:sanctum` est prévu : mesure S-01 de [securite-rgpd.md](securite-rgpd.md#10-mesures-complémentaires-à-mettre-en-œuvre). Contrats des réponses : [schema_bd.md § Consommation API](schema_bd.md#consommation-api).

---

## Commandes utiles (dans le container)

Depuis l'hôte, à la racine du repo. Le shell hôte n'a ni PHP ni Composer.

```bash
docker compose exec sc_back php artisan migrate
```

> Alias conseillé : `alias sc='docker compose exec sc_back'` → `sc php artisan migrate`.
