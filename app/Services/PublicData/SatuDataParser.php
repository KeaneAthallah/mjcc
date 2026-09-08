<?php

namespace App\Services\PublicData;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses the server-rendered pages of the Satu Data Morowali portal into plain
 * data structures. Parsing never follows the portal's own links further; it
 * only reads what is already in the fetched HTML.
 */
class SatuDataParser
{
    /**
     * Extracts catalog items from a `/dataset?page=N` listing.
     *
     * Each item carries the detail URL, dataset title, publishing organization
     * and the "Bidang" topic label the portal assigns to it.
     *
     * @return array<int, array{url: string, title: string, org: string, topic: string}>
     */
    public function catalog(string $html): array
    {
        $xpath = $this->xpath($html);

        $items = [];

        foreach ($xpath->query('//a[contains(@href, "/dataset/detail/")]') as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $url = trim((string) $anchor->getAttribute('href'));
            $titleNode = $xpath->query('.//h5', $anchor)->item(0);

            $title = $titleNode !== null ? $this->clean($titleNode->textContent) : '';
            $org = $this->iconLabel($xpath, $anchor, 'building');
            $topic = $this->iconLabel($xpath, $anchor, 'book');

            if ($url === '' || $title === '') {
                continue;
            }

            $items[] = [
                'url' => $url,
                'title' => $title,
                'org' => $org,
                'topic' => $topic,
            ];
        }

        return $items;
    }

    /**
     * Extracts the header + body of the `#detail_data` table plus page title
     * and best-effort metadata (published, period, producer, source).
     *
     * @return array{
     *     title: string,
     *     headers: array<int, string>,
     *     rows: array<int, array<int, string>>,
     *     metadata: array<string, string>,
     * }
     */
    public function detail(string $html): array
    {
        $xpath = $this->xpath($html);

        $title = $this->clean($xpath->query('//h1|//h3[contains(@class, "slideInLeft")]')->item(0)?->textContent ?? '');

        $headers = [];
        $rows = [];

        $table = $xpath->query('//table[@id="detail_data"]')->item(0);

        if ($table instanceof DOMElement) {
            foreach ($xpath->query('.//thead//th', $table) as $th) {
                $headers[] = $this->clean($th->textContent);
            }

            $bodyRows = $xpath->query('.//tbody//tr', $table);

            if (count($headers) === 0 && $bodyRows->length > 0) {
                foreach ($xpath->query('.//th', $table) as $th) {
                    $headers[] = $this->clean($th->textContent);
                }
            }

            foreach ($bodyRows as $tr) {
                if (! $tr instanceof DOMElement) {
                    continue;
                }

                $cells = [];

                foreach ($xpath->query('./td', $tr) as $td) {
                    $cells[] = $this->clean($td->textContent);
                }

                if ($cells !== []) {
                    $rows[] = $cells;
                }
            }
        }

        return [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'metadata' => $this->metadata($html),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function metadata(string $html): array
    {
        $meta = [];

        $labels = [
            'published' => 'Publikasi',
            'period' => 'Periode Dataset',
            'producer' => 'Produsen',
            'source' => 'Sumber Dataset',
            'created' => 'Dataset Dibuat',
        ];

        foreach ($labels as $key => $label) {
            $value = $this->dtDdValue($html, $label);

            if ($value !== null) {
                $meta[$key] = $value;
            }
        }

        $patterns = [
            'published' => '/Publikasi\s*:?\s*<\/?[^>]+>\s*([^<]{1,120})/i',
            'period' => '/Periode\s*[Dd]ataset\s*:?\s*<\/?[^>]+>\s*([^<]{1,120})/i',
            'producer' => '/Produsen\s*:?\s*<\/?[^>]+>\s*([^<]{1,120})/i',
            'source' => '/Sumber\s*[Dd]ataset\s*:?\s*<\/?[^>]+>\s*([^<]{1,120})/i',
            'created' => '/[Dd]ataset\s+[Dd]ibuat\s*:?\s*<\/?[^>]+>\s*([^<]{1,120})/i',
        ];

        foreach ($patterns as $key => $pattern) {
            if (array_key_exists($key, $meta)) {
                continue;
            }

            if (preg_match($pattern, $html, $m) === 1) {
                $meta[$key] = trim(strip_tags(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
            }
        }

        return $meta;
    }

    /**
     * Reads the value of a `<dt>/<dd>` metadata pair, or null when absent.
     */
    private function dtDdValue(string $html, string $label): ?string
    {
        $xpath = $this->xpath($html);

        $dt = $xpath->query(
            sprintf('//dt[normalize-space(translate(normalize-space(.), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")) = "%s"]/following-sibling::dd[1]', strtolower($label)),
        )->item(0);

        if (! $dt instanceof DOMElement) {
            return null;
        }

        $value = $this->clean($dt->textContent);

        return $value === '' ? null : $value;
    }

    /**
     * Reads the text of an element that carries a given FontAwesome icon class
     * (e.g. `fa fa-building` for the publisher, `fa fa-book` for the topic).
     * The label is the element's own text, minus the empty `<i>` icon.
     */
    private function iconLabel(DOMXPath $xpath, DOMElement $context, string $iconClass): string
    {
        $node = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " fa-'.$iconClass.' ")]', $context)->item(0);

        if (! $node instanceof DOMElement) {
            return '';
        }

        $text = $this->clean($node->textContent);

        return $text === '' ? $this->clean($node->parentNode?->textContent ?? '') : $text;
    }

    private function clean(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;

        $internalErrors = libxml_use_internal_errors(true);

        try {
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
        }

        return new DOMXPath($dom);
    }
}
