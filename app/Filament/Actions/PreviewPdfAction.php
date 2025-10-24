<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanOpenUrl;
use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Support\Enums\Width;

class PreviewPdfAction extends Action
{
    use CanOpenUrl;
    use InteractsWithRecord;

    protected string|\Closure $pdfUrl = '';

    protected string $modalTitle = 'PDF Preview';

    protected Width|\Closure|string|null $modalWidth = '7xl';

    protected string $minHeight = '70vh';

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'previewPdf');
    }

    public function pdfUrl(string|\Closure $url): static
    {
        $this->pdfUrl = $url;

        return $this;
    }

    public function getPdfUrl(): string
    {
        return $this->evaluate($this->pdfUrl);
    }

    public function modalTitle(string $title): static
    {
        $this->modalTitle = $title;

        return $this;
    }

    public function getModalTitle(): string
    {
        return $this->modalTitle;
    }

    public function modalWidth(Width|\Closure|string|null $width = null): static
    {
        $this->modalWidth = $width;

        return $this;
    }

    public function getModalWidth(): Width|string
    {
        return $this->modalWidth;
    }

    public function minHeight(string $height): static
    {
        $this->minHeight = $height;

        return $this;
    }

    public function getMinHeight(): string
    {
        return $this->minHeight;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Preview PDF')
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->modalHeading($this->getModalTitle())
            ->modalWidth($this->getModalWidth())
            ->modalContent(fn () => view('filament.actions.preview-pdf-content', [
                'pdfUrl' => $this->getPdfUrl(),
                'minHeight' => $this->getMinHeight(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }
}
