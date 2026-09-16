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

<ui-header title="Accessibility Statement" icon="list-checklist" />

<div class="space-y-6">

    @include('grange::utilities.nav', ['active' => 'evidence'])

    <ui-card-panel heading="@grangeText($total.' '.($total === 1 ? 'record' : 'records').($shown !== $total ? ', '.$shown.' shown' : ''))">
        <div class="space-y-4">
            <p>Every record is written once. A correction is a new record that names the one it corrects; both stay. Exports carry the whole log, or exactly this filter.</p>

            <form method="get" action="@grangeText($urls['evidence'])" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="site" value="@grangeText($site)">
                <div>
                    <label for="grange-f-action" class="{{ $label }}">Action</label>
                    <select id="grange-f-action" name="action" class="{{ $control }}">
                        <option value="">Any</option>
                        @foreach ($actions as $a)
                            <option value="@grangeText($a)" @if ($f->action === $a) selected @endif>@grangeText(\Bpmore\Grange\Evidence\Actions::label($a))</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="grange-f-criterion" class="{{ $label }}">Criterion</label>
                    <input id="grange-f-criterion" name="criterion" type="text" value="@grangeText($f->criterion ?? '')" class="{{ $control }}" size="8">
                </div>
                <div>
                    <label for="grange-f-actor" class="{{ $label }}">Actor</label>
                    <input id="grange-f-actor" name="actor" type="text" value="@grangeText($f->actor ?? '')" class="{{ $control }}" size="14">
                </div>
                <div>
                    <label for="grange-f-subject" class="{{ $label }}">Page or subject</label>
                    <input id="grange-f-subject" name="subject" type="text" value="@grangeText($f->subject ?? '')" class="{{ $control }}" size="14">
                </div>
                <div>
                    <label for="grange-f-from" class="{{ $label }}">From</label>
                    <input id="grange-f-from" name="from" type="date" value="@grangeText($f->from ?? '')" class="{{ $control }}">
                </div>
                <div>
                    <label for="grange-f-to" class="{{ $label }}">To</label>
                    <input id="grange-f-to" name="to" type="date" value="@grangeText($f->to ?? '')" class="{{ $control }}">
                </div>
                <div class="flex items-center gap-2 pb-1">
                    <input id="grange-f-hide" name="hide_superseded" type="checkbox" value="1" @if ($f->hideSuperseded) checked @endif>
                    <label for="grange-f-hide" class="text-sm">Hide corrected records</label>
                </div>
                <ui-button type="submit" size="sm" text="Filter" />
                @if (! $f->isEmpty())<ui-button size="sm" variant="ghost" href="@grangeText($urls['evidence'])" text="Clear" />@endif
            </form>

            <div class="flex flex-wrap items-center gap-3">
                <ui-button size="sm" href="@grangeText($exportCsv)" text="@grangeText($f->isEmpty() ? 'Export CSV' : 'Export this filter as CSV')" />
                <ui-button size="sm" href="@grangeText($exportJson)" text="@grangeText($f->isEmpty() ? 'Export JSON' : 'Export this filter as JSON')" />
            </div>

            @if ($records === [])
                <p>@grangeText($total === 0 ? 'Nothing has been recorded yet.' : 'No records match this filter.')</p>
            @else
                <table class="w-full text-sm">
                    <caption class="sr-only">Evidence records, newest first</caption>
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
                                <td class="py-1 pr-3 whitespace-nowrap"><time datetime="@grangeText($r->at)">@grangeText(str_replace('T', ' ', substr($r->at, 0, 16)))</time></td>
                                <td class="py-1 pr-3">@grangeText($r->actor)</td>
                                <td class="py-1 pr-3">@grangeText(\Bpmore\Grange\Evidence\Actions::label($r->action))</td>
                                <td class="py-1 pr-3">@grangeText($r->criterion ?? '')</td>
                                <td class="py-1 pr-3">@grangeText($r->subject ?? '')</td>
                                <td class="py-1 pr-3">@grangeText($r->notes)</td>
                                <td class="py-1 text-xs">
                                    <code>@grangeText($r->id)</code>
                                    @if ($r->supersedes)<div>Corrects @grangeText($r->supersedes)</div>@endif
                                    @if (isset($superseded[$r->id]))<div>Corrected by a later record</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($pages > 1)
                    <nav aria-label="Pages of records" class="flex flex-wrap items-center gap-2">
                        @if ($page > 1)<ui-button size="sm" href="@grangeText($pageUrl(['page' => $page - 1]))" text="Newer" />@endif
                        <span class="text-sm">Page {{ $page }} of {{ $pages }}</span>
                        @if ($page < $pages)<ui-button size="sm" href="@grangeText($pageUrl(['page' => $page + 1]))" text="Older" />@endif
                    </nav>
                @endif
            @endif
        </div>
    </ui-card-panel>

    @if ($canRecord)
        <ui-card-panel heading="Record something" id="grange-record">
            <form method="post" action="@grangeText($urls['evidenceAppend'])" class="space-y-3">
                @csrf
                <input type="hidden" name="site" value="@grangeText($site)">
                <p class="text-sm text-gray-600 dark:text-gray-400">Written under your name and the current time. To correct an earlier record, name it under "Corrects": both records stay.</p>
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label for="grange-r-action" class="{{ $label }}">What was done</label>
                        <select id="grange-r-action" name="action" class="{{ $control }} w-full" aria-required="true">
                            @foreach ($actions as $a)
                                @continue($a === \Bpmore\Grange\Evidence\Actions::ATTESTED)
                                <option value="@grangeText($a)" @if ($old('action', 'assessed') === $a) selected @endif>@grangeText(\Bpmore\Grange\Evidence\Actions::label($a))</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="grange-r-criterion" class="{{ $label }}">WCAG success criterion (optional)</label>
                        <input id="grange-r-criterion" name="criterion" type="text" value="@grangeText($old('criterion'))" class="{{ $control }} w-full">
                    </div>
                    <div>
                        <label for="grange-r-subject" class="{{ $label }}">Page or asset (optional)</label>
                        <input id="grange-r-subject" name="subject" type="text" value="@grangeText($old('subject'))" class="{{ $control }} w-full">
                    </div>
                </div>
                <div>
                    <label for="grange-r-notes" class="{{ $label }}">Notes</label>
                    <textarea id="grange-r-notes" name="notes" rows="3" class="{{ $control }} w-full" aria-required="true">@grangeText($old('notes'))</textarea>
                </div>
                <div>
                    <label for="grange-r-supersedes" class="{{ $label }}">Corrects record (optional, the record id)</label>
                    <input id="grange-r-supersedes" name="supersedes" type="text" value="@grangeText($old('supersedes'))" class="{{ $control }} w-full">
                </div>
                <ui-button type="submit" variant="primary" text="Write the record" />
            </form>
        </ui-card-panel>

        @if ($externalAvailable)
            <ui-card-panel heading="@grangeText('Import fixes from '.$externalSource)">
                <form method="post" action="@grangeText($urls['evidenceAppend'])" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="site" value="@grangeText($site)">
                    <input type="hidden" name="import" value="1">
                    <div>
                        <label for="grange-import-since" class="{{ $label }}">Fixed since</label>
                        <input id="grange-import-since" name="since" type="date" value="@grangeText(date('Y-m-d', strtotime('-365 days')))" class="{{ $control }}">
                    </div>
                    <ui-button type="submit" text="Import" />
                    <p class="text-sm text-gray-600 dark:text-gray-400 w-full">Each fix becomes a record keyed on the finding, so importing again writes nothing twice.</p>
                </form>
            </ui-card-panel>
        @endif
    @endif

    @include('grange::utilities.disclaimer')
</div>
