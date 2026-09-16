<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Bpmore\A11yState\Statement\HtmlRenderer;
use Bpmore\A11yState\Statement\IncompleteStatement;
use Bpmore\A11yState\Statement\MarkdownRenderer;
use Bpmore\A11yState\Statements;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

/**
 * The statement as a file: Markdown, or a complete HTML page. A statement
 * that cannot be generated is refused with the reasons, never exported
 * with gaps.
 */
final class ExportStatementController extends CpController
{
    public function __invoke(Request $request, Statements $statements, string $format)
    {
        abort_unless(in_array($format, ['md', 'html'], true), 404);

        $site = StatementPageData::site($request);
        $statement = $statements->for($site);

        try {
            $document = $statements->document($statement);
        } catch (IncompleteStatement $e) {
            return redirect()->to(StatementPageData::urls($site)['index'])->with('error', $e->getMessage());
        }

        $name = 'accessibility-statement-'.$site.'-'.substr($document->generatedAt, 0, 10);

        if ($format === 'md') {
            return response((new MarkdownRenderer)->render($document), 200, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$name}.md\"",
            ]);
        }

        $body = (new HtmlRenderer)->render($document, 1);
        $title = htmlspecialchars($document->title.': '.$document->organization, ENT_QUOTES, 'UTF-8');
        $page = "<!DOCTYPE html>\n<html lang=\"{$document->language}\">\n<head>\n<meta charset=\"utf-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n<title>{$title}</title>\n<style>body{margin:0 auto;max-width:42em;padding:2em 1.25em;font-family:system-ui,sans-serif;line-height:1.55;color:#1a1a1a;background:#fff}a{color:#1a4fa3}a:focus-visible{outline:3px solid currentColor;outline-offset:2px}@media (prefers-color-scheme: dark){body{color:#f2f2f2;background:#121212}a{color:#8ab4f8}}</style>\n</head>\n<body>\n<main>\n{$body}\n</main>\n</body>\n</html>\n";

        return response($page, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$name}.html\"",
        ]);
    }
}
