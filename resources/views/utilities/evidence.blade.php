{{--
    The evidence log. Newest first, filtered by the query string, exported
    as what is on screen. Nothing on this page edits or deletes a record,
    and nothing anywhere else does either.
--}}
@php
    $f = $filter;
    $control = 'rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-sm';
    $label = 'block text-xs font-medium mb-1';
    $old = fn (string $key, $default = '') => old($key, $default);
@endphp

<ui-header title="A11y State" icon="list-checklist" />

<div class="space-y-6">

    @include('a11y-state::utilities.nav', ['active' => 'evidence'])

    <ui-panel><ui-panel-header><ui-heading level="2" text="@a11yStateText($total.' '.($total === 1 ? 'record' : 'records').($shown !== $total ? ', '.$shown.' shown' : ''))" /></ui-panel-header><ui-card>
        <div class="space-y-4">
            <p>Every record is written once. A correction is a new record that names the one it corrects; both stay. Exports carry the whole log, or exactly this filter.</p>

            <form method="get" action="@a11yStateText($urls['evidence'])" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="site" value="@a11yStateText($site)">
                <div>
                    <label for="a11y-state-f-action" class="{{ $label }}">Action</label>
                    <select id="a11y-state-f-action" name="action" class="{{ $control }}">
                        <option value="">Any</option>
                        @foreach ($actions as $a)
                            <option value="@a11yStateText($a)" @if ($f->action === $a) selected @endif>@a11yStateText(\Bpmore\A11yState\Evidence\Actions::label($a))</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="a11y-state-f-criterion" class="{{ $label }}">Criterion</label>
                    <input id="a11y-state-f-criterion" name="criterion" type="text" value="@a11yStateText($f->criterion ?? '')" class="{{ $control }}" size="8">
                </div>
                <div>
                    <label for="a11y-state-f-actor" class="{{ $label }}">Actor</label>
                    <input id="a11y-state-f-actor" name="actor" type="text" value="@a11yStateText($f->actor ?? '')" class="{{ $control }}" size="14">
                </div>
                <div>
                    <label for="a11y-state-f-subject" class="{{ $label }}">Page or subject</label>
                    <input id="a11y-state-f-subject" name="subject" type="text" value="@a11yStateText($f->subject ?? '')" class="{{ $control }}" size="14">
                </div>
                <div>
                    <label for="a11y-state-f-from" class="{{ $label }}">From</label>
                    <input id="a11y-state-f-from" name="from" type="date" value="@a11yStateText($f->from ?? '')" class="{{ $control }}">
                </div>
                <div>
                    <label for="a11y-state-f-to" class="{{ $label }}">To</label>
                    <input id="a11y-state-f-to" name="to" type="date" value="@a11yStateText($f->to ?? '')" class="{{ $control }}">
                </div>
                <div class="flex items-center gap-2 pb-1">
                    <input id="a11y-state-f-hide" name="hide_superseded" type="checkbox" value="1" @if ($f->hideSuperseded) checked @endif>
                    <label for="a11y-state-f-hide" class="text-sm">Hide corrected records</label>
                </div>
                <ui-button type="submit" size="sm" text="Filter" />
                @if (! $f->isEmpty())<ui-button size="sm" variant="ghost" href="@a11yStateText($urls['evidence'])" text="Clear" />@endif
            </form>

            <div class="flex flex-wrap items-center gap-3">
                <ui-button size="sm" href="@a11yStateText($exportCsv)" text="@a11yStateText($f->isEmpty() ? 'Export CSV' : 'Export this filter as CSV')" />
                <ui-button size="sm" href="@a11yStateText($exportJson)" text="@a11yStateText($f->isEmpty() ? 'Export JSON' : 'Export this filter as JSON')" />
            </div>

            @if ($records === [])
                <p>@a11yStateText($total === 0 ? 'Nothing has been recorded yet.' : 'No records match this filter.')</p>
            @else
                {{-- Scrolls sideways on a narrow screen instead of pushing the page out: a seven-column table at phone width otherwise clips or overflows. Focusable and named by its caption because Safari keeps the keyboard out of a scroll area otherwise, which left the columns past the edge out of reach. --}}
<div class="overflow-x-auto" tabindex="0" role="region" aria-labelledby="a11y-state-records-caption">
<table class="w-full text-sm">
                    <caption id="a11y-state-records-caption" class="sr-only">Evidence records, newest first</caption>
                    <thead>
                        <tr class="text-left">
                            <th scope="col" class="py-1 pr-3">When</th>
                            <th scope="col" class="py-1 pr-3">Actor</th>
                            <th scope="col" class="py-1 pr-3">Action</th>
                            <th scope="col" class="py-1 pr-3">Criterion</th>
                            <th scope="col" class="py-1 pr-3">Page or subject</th>
                            <th scope="col" class="py-1 pr-3">Notes</th>
                            <th scope="col" class="py-1">Record</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $r)
                            <tr class="border-t border-gray-200 dark:border-gray-800 align-middle">
                                <td class="py-1 pr-3 whitespace-nowrap"><time datetime="@a11yStateText($r->at)">@a11yStateText(str_replace('T', ' ', substr($r->at, 0, 16)))</time></td>
                                <td class="py-1 pr-3">@a11yStateText($r->actor)</td>
                                <td class="py-1 pr-3">@a11yStateText(\Bpmore\A11yState\Evidence\Actions::label($r->action))</td>
                                <td class="py-1 pr-3">@a11yStateText($r->criterion ?? '')</td>
                                <td class="py-1 pr-3">@a11yStateText($r->subject ?? '')</td>
                                <td class="py-1 pr-3">@a11yStateText($r->notes)</td>
                                <td class="py-1 text-xs">
                                    <code>@a11yStateText($r->id)</code>
                                    @if ($r->supersedes)<div>Corrects @a11yStateText($r->supersedes)</div>@endif
                                    @if (isset($superseded[$r->id]))<div>Corrected by a later record</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
</div>

                @if ($pages > 1)
                    <nav aria-label="Pages of records" class="flex flex-wrap items-center gap-2">
                        @if ($page > 1)<ui-button size="sm" href="@a11yStateText($pageUrl(['page' => $page - 1]))" text="Newer" />@endif
                        <span class="text-sm">Page {{ $page }} of {{ $pages }}</span>
                        @if ($page < $pages)<ui-button size="sm" href="@a11yStateText($pageUrl(['page' => $page + 1]))" text="Older" />@endif
                    </nav>
                @endif
            @endif
        </div>
    </ui-card></ui-panel>

    @if ($canRecord)
        <ui-panel id="a11y-state-record"><ui-panel-header><ui-heading level="2" text="Record something" /></ui-panel-header><ui-card>
            <form method="post" action="@a11yStateText($urls['evidenceAppend'])" class="space-y-3">
                @csrf
                <input type="hidden" name="site" value="@a11yStateText($site)">
                <p class="text-sm text-gray-600 dark:text-gray-400">Written under your name and the current time. To correct an earlier record, name it under "Corrects": both records stay.</p>
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label for="a11y-state-r-action" class="{{ $label }}">What was done</label>
                        <select id="a11y-state-r-action" name="action" class="{{ $control }} w-full" aria-required="true">
                            @foreach ($actions as $a)
                                @continue($a === \Bpmore\A11yState\Evidence\Actions::ATTESTED)
                                <option value="@a11yStateText($a)" @if ($old('action', 'assessed') === $a) selected @endif>@a11yStateText(\Bpmore\A11yState\Evidence\Actions::label($a))</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="a11y-state-r-criterion" class="{{ $label }}">WCAG success criterion (optional)</label>
                        <input id="a11y-state-r-criterion" name="criterion" type="text" value="@a11yStateText($old('criterion'))" class="{{ $control }} w-full">
                    </div>
                    <div>
                        <label for="a11y-state-r-subject" class="{{ $label }}">Page or asset (optional)</label>
                        <input id="a11y-state-r-subject" name="subject" type="text" value="@a11yStateText($old('subject'))" class="{{ $control }} w-full">
                    </div>
                </div>
                <div>
                    <label for="a11y-state-r-notes" class="{{ $label }}">Notes</label>
                    <textarea id="a11y-state-r-notes" name="notes" rows="3" class="{{ $control }} w-full" aria-required="true">@a11yStateText($old('notes'))</textarea>
                </div>
                <div>
                    <label for="a11y-state-r-supersedes" class="{{ $label }}">Corrects record (optional, the record id)</label>
                    <input id="a11y-state-r-supersedes" name="supersedes" type="text" value="@a11yStateText($old('supersedes'))" class="{{ $control }} w-full">
                </div>
                <ui-button type="submit" variant="primary" text="Write the record" />
            </form>
        </ui-card></ui-panel>

        @if ($externalAvailable)
            <ui-panel><ui-panel-header><ui-heading level="2" text="@a11yStateText('Import fixes from '.$externalSource)" /></ui-panel-header><ui-card>
                <form method="post" action="@a11yStateText($urls['evidenceAppend'])" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="site" value="@a11yStateText($site)">
                    <input type="hidden" name="import" value="1">
                    <div>
                        <label for="a11y-state-import-since" class="{{ $label }}">Fixed since</label>
                        <input id="a11y-state-import-since" name="since" type="date" value="@a11yStateText(date('Y-m-d', strtotime('-365 days')))" class="{{ $control }}">
                    </div>
                    <ui-button type="submit" text="Import" />
                    <p class="text-sm text-gray-600 dark:text-gray-400 w-full">Each fix becomes a record keyed on the finding, so importing again writes nothing twice.</p>
                </form>
            </ui-card></ui-panel>
        @endif
    @endif

    @include('a11y-state::utilities.disclaimer')
</div>
