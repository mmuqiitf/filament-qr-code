<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Contracts\Support\Htmlable;

trait HasAnnouncements
{
    protected bool|Closure $announcementsEnabled = true;

    protected ?Closure $announceUsing = null;

    /** Read accepted and rejected scans aloud through the polite live region (on by default). */
    public function announcements(bool|Closure $enabled = true): static
    {
        $this->announcementsEnabled = $enabled;

        return $this;
    }

    /**
     * Custom announcement text: fn (string $code, bool $accepted, ?string $reason, string $field): ?string.
     * Return null to keep the built-in text.
     */
    public function announceUsing(?Closure $callback): static
    {
        $this->announceUsing = $callback;

        return $this;
    }

    /** Whether scans are announced to screen readers. */
    public function hasAnnouncements(): bool
    {
        return (bool) $this->evaluate($this->announcementsEnabled);
    }

    /**
     * The custom announcement for a scan, or null for the built-in text.
     * Called by the field JS when announceUsing() is set.
     */
    #[ExposedLivewireMethod]
    public function announceScan(string $code, bool $accepted, ?string $reason = null, ?string $field = null): ?string
    {
        if ($this->announceUsing === null || ! $this->hasAnnouncements()) {
            return null;
        }

        $text = $this->evaluate($this->announceUsing, [
            'code' => $code,
            'accepted' => $accepted,
            'reason' => $reason,
            'field' => $this->getAnnouncementFieldLabel($field),
        ]);

        return is_scalar($text) && filled($text) ? strip_tags((string) $text) : null;
    }

    /** @return array{announcements: bool, customAnnouncements: bool} */
    protected function getAnnouncementsScanPayload(): array
    {
        $enabled = $this->hasAnnouncements();

        return [
            'announcements' => $enabled,
            'customAnnouncements' => $enabled && $this->announceUsing !== null,
        ];
    }

    /** The plain-text name read out for a scan; sequences pass the step key. */
    protected function getAnnouncementFieldLabel(?string $field = null): string
    {
        return self::plainAnnouncementText($this->getLabel());
    }

    protected static function plainAnnouncementText(string|Htmlable|null $label): string
    {
        $text = $label instanceof Htmlable ? $label->toHtml() : (string) $label;

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));
    }
}
