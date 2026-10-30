<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/**
 * @property Schema $form
 */
class AnnounceSmoke extends Page
{
    protected string $view = 'workbench::scan-smoke';

    protected static ?string $slug = 'announce-smoke';

    protected static ?string $title = 'Announce smoke';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrScanner::make('custom')
                    ->label('Custom')
                    ->validateUsing(['string', 'starts_with:OK-'])
                    ->announceUsing(fn (string $code, bool $accepted, string $field): ?string => $accepted ? "Checked in {$code} at {$field}" : null),
                QrScanner::make('quiet')->label('Quiet')->announcements(false),
            ]);
    }
}
