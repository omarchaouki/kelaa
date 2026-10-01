<?php

namespace App\Models;

use App\Models\Contracts\Translatable;
use App\Support\Security\RichText;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $body
 * @property string|null $meta_description
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'title', 'body', 'meta_description', 'is_published'])]
class Page extends Model implements Translatable
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'body', 'meta_description'];

    protected static function booted(): void
    {
        // Stored HTML is always sanitized, whatever wrote it (panel, seeder, tinker).
        static::saving(function (Page $page): void {
            foreach ($page->getTranslations('body') as $locale => $html) {
                $page->setTranslation('body', $locale, RichText::sanitize($html));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * Body HTML for the current locale, sanitized again at render time.
     */
    public function safeBody(): string
    {
        // Untranslated legal pages show the French text rather than an empty page.
        $body = $this->body ?: $this->getTranslation('body', (string) config('directory.default_locale'), false);

        return RichText::sanitize((string) $body);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
