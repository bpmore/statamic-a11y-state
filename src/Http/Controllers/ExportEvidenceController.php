<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Bpmore\A11yState\Evidence\Export;
use Bpmore\A11yState\Evidence\Filter;
use Bpmore\A11yState\Statements;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

final class ExportEvidenceController extends CpController
{
    public function __invoke(Request $request, Statements $statements, string $format)
    {
        abort_unless(in_array($format, Export::FORMATS, true), 404);

        $filter = Filter::fromArray($request->query());
        $records = $statements->log()->filter($filter);
        $name = 'accessibility-evidence-'.date('Y-m-d').($filter->isEmpty() ? '' : '-filtered').'.'.$format;

        return response(Export::render($format, $records), 200, [
            'Content-Type' => Export::contentType($format),
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }
}
