<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Normaliza referencias de encabezado/pie en w:sectPr para exportación PDF con LibreOffice.
 *
 * Al compactar M1/M2 en una sola sección se pierde a menudo headerReference type="default"
 * (logo SENA en header1.xml) y queda solo type="first" sin w:titlePg, que LO ignora.
 */
final class F023SectionHeaderSupport
{
    public static function extractDefaultHeaderReference(string $xml): ?string
    {
        if (preg_match('/<w:headerReference w:type="default"[^>]*\/>/', $xml, $match)) {
            return (string) $match[0];
        }

        return null;
    }

    public static function extractDefaultFooterReference(string $xml): ?string
    {
        if (preg_match('/<w:footerReference w:type="default"[^>]*\/>/', $xml, $match)) {
            return (string) $match[0];
        }

        return null;
    }

    /**
     * Deja una sola referencia de encabezado/pie «default» apta para PDF (sin titlePg ni first).
     */
    public static function normalizeSectPrForPdfExport(string $sectPr, string $sourceXml): string
    {
        $defaultHeader = self::extractDefaultHeaderReference($sourceXml);
        $defaultFooter = self::extractDefaultFooterReference($sourceXml);

        $normalized = $sectPr;
        $normalized = preg_replace('/<w:headerReference\b[^>]*\/>/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/<w:footerReference\b[^>]*\/>/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/<w:titlePg\s*\/>/', '', $normalized) ?? $normalized;

        $refs = '';
        if ($defaultHeader !== null) {
            $refs .= $defaultHeader;
        }
        if ($defaultFooter !== null) {
            $refs .= $defaultFooter;
        }
        if ($refs === '') {
            return $normalized;
        }

        $replaced = preg_replace('/(<w:sectPr>)/', '$1' . $refs, $normalized, 1);

        return is_string($replaced) ? $replaced : $normalized;
    }
}
