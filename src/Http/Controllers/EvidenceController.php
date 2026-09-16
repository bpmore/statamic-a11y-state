<?php

declare(strict_types=1);

namespace Bpmore\Grange\Http\Controllers;

use Bpmore\Grange\Evidence\Actions;
use Bpmore\Grange\Evidence\Filter;
use Bpmore\Grange\Evidence\Ordering;
use Bpmore\Grange\Statements;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * The evidence log viewer: every record, newest first, with filters that
 * narrow the view and change nothing. The export links carry the same
 * filter, so what is exported is what is on screen, or everything when
 * nothing is filtered.
 */
final class EvidenceController extends CpController
{
    public const PER_PAGE = 50;

    public function __invoke(Request $request, Statements $statements)
    {
        $site = StatementPageData::site($request);
        $filter = Filter::fromArray($request->query());
        $all = $statements->log()->all();
        $records = array_reverse(Ordering::apply($all, $filter));
        $superseded = Ordering::superseded($all);

        $page = max(1, (int) $request->query('page', 1));
        $pages = max(1, (int) ceil(count($records) / self::PER_PAGE));
        $page = min($page, $pages);
        $slice = array_slice($records, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        $urls = StatementPageData::urls($site);
        $query = array_merge(['site' => $site], $filter->toQuery());
        $link = fn (array $extra = []) => cp_route('utilities.grange.evidence').'?'.http_build_query(array_filter(array_merge($query, $extra), fn ($v) => $v !== null && $v !== ''));

        return Inertia::render('utilities/Show', [
            'title' => 'Accessibility Statement: Evidence',
            'html' => view('grange::utilities.evidence', [
                'site' => $site,
                'records' => $slice,
                'total' => count($all),
                'shown' => count($records),
                'superseded' => $superseded,
                'filter' => $filter,
                'actions' => Actions::ALL,
                'page' => $page,
                'pages' => $pages,
                'pageUrl' => $link,
                'exportCsv' => cp_route('utilities.grange.evidence.export', ['format' => 'csv']).'?'.http_build_query($filter->toQuery()),
                'exportJson' => cp_route('utilities.grange.evidence.export', ['format' => 'json']).'?'.http_build_query($filter->toQuery()),
                'urls' => $urls,
                'canRecord' => (bool) User::current()?->can('record accessibility evidence'),
                'externalSource' => $statements->findings()->name(),
                'externalAvailable' => $statements->findings()->isAvailable(),
            ])->render(),
        ]);
    }
}
