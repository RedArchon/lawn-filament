<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Actions\PreviewPdfAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PreviewPdfAction::make('previewInvoicePdf')
                ->pdfUrl(fn () => $this->getPdfUrl())
                ->modalTitle('Invoice PDF Preview')
                ->modalWidth('7xl')
                ->minHeight('80vh')
                ->visible(fn () => $this->record->hasPdf()),
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => $this->record->getPdfDownloadUrl())
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->hasPdf()),
            Action::make('generatePdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->action(function () {
                    $this->record->generatePdf();
                    $this->dispatch('$refresh');
                })
                ->visible(fn () => ! $this->record->hasPdf()),
            Action::make('regeneratePdf')
                ->label('Regenerate PDF')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $this->record->generatePdf();
                    $this->dispatch('$refresh');
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->record->hasPdf() && $this->record->status === 'draft'),
            EditAction::make(),
        ];
    }

    public function getPdfUrl(): ?string
    {
        return $this->record->getPdfUrl();
    }
}
