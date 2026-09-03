<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    public function __construct(
        private readonly string $projectDir
    ) {}

    public function creer(): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        return new Dompdf($options);
    }

    public function chargerCss(): string
    {
        $css = file_get_contents(
            $this->projectDir . '/public/assets/css/pdf.css'
        );

        if ($css === false) {
            throw new \RuntimeException(
                'Impossible de charger le fichier CSS des PDF.'
            );
        }

        return $css;
    }

    public function ajouterPagination(Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();

        $font = $dompdf->getFontMetrics()
            ->getFont('DejaVu Sans', 'normal');

        $canvas->page_text(
            500,
            810,
            'Page {PAGE_NUM} / {PAGE_COUNT}',
            $font,
            7
        );
    }
}
