{{--
    The statement editor. Compiled as a Vue template, not printed as HTML,
    so every data value goes through @a11yStateText and there is no `{{ }}`
    on anything that could contain a brace. Numbers are safe as they are.

    Native form controls on purpose. Statamic's field components are Vue
    components bound to state this template does not have, and a plain
    <textarea> with a <label> inside a plain <form> is the most accessible
    control there is. Every control has a visible label, every error is
    text, nothing means anything by colour alone, and the whole page works
    with no script.
--}}
@php
    $s = $statement;
    $old = fn (string $key, $default = '') => old($key, $default);
    $control = 'w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-sm';
    $label = 'block text-sm font-medium mb-1';
    $fieldErrors = [];
    foreach ($errors as $p) { if ($p->field !== null) { $fieldErrors[$p->field][] = $p->message; } }
    $link = 'underline underline-offset-2 text-blue-700 dark:text-blue-300 focus:focus-outline rounded-sm';
    $savedProfileName = $s->profile;
    foreach ($profiles as $p) { if ($p->handle === $s->profile) { $savedProfileName = $p->name; } }
@endphp

<ui-header title="A11y State" icon="document-certificate" />

<div class="space-y-6">

    @include('a11y-state::utilities.nav', ['active' => 'statement'])

    @if (count($sites) > 1)
        <nav aria-label="Site" class="flex flex-wrap items-center gap-2">
            @foreach ($sites as $one)
                <ui-button size="sm" href="@a11yStateText(cp_route('utilities.index').'/a11y-state?site='.$one['handle'])" text="@a11yStateText($one['name'])" @if ($site === $one['handle']) variant="primary" aria-current="page" @endif />
            @endforeach
        </nav>
    @endif

    {{-- Completeness, first, because it is the answer to "can this be published". --}}
    <ui-panel><ui-panel-header><ui-heading level="2" text="@a11yStateText($errors === [] ? 'Complete under the '.$profile->name.' profile' : count($errors).' '.(count($errors) === 1 ? 'thing stops' : 'things stop').' this statement being generated')" /></ui-panel-header><ui-card>
        <div class="space-y-3">
            @if ($errors === [] && $warnings === [])
                <p>Every disclosure the profile requires is present, the claim is within what the evidence allows, and the assessment is inside the {{ $staleAfterDays }}-day window.@if ($publicUrl) The statement is published at <a class="{{ $link }}" href="@a11yStateText($publicUrl)">@a11yStateText($publicUrl)</a>.@endif</p>
            @endif

            @if ($errors !== [])
                <ul class="ps-5 space-y-1" style="list-style: disc" aria-label="Problems that stop generation">
                    @foreach ($errors as $p)
                        <li><strong>Error:</strong> @a11yStateText($p->message)@if ($p->field !== null && isset($fieldErrors[$p->field])) <a class="{{ $link }}" href="#a11y-state-field-@a11yStateText($p->field)">Go to the field</a>@endif</li>
                    @endforeach
                </ul>
            @endif

            @if ($stale !== null)
                <div role="status" class="rounded border border-amber-400 p-3">
                    <p><strong>Stale assessment.</strong> @a11yStateText($stale->message)</p>
                </div>
            @endif

            @if ($switching)
                <div role="status" class="rounded border border-amber-400 p-3 space-y-2">
                    <p><strong>You are looking at the @a11yStateText($profile->name) profile. The statement is saved under @a11yStateText($savedProfileName).</strong> Nothing changes until you save.</p>
                    @if ($orphans !== [])
                        <p>@a11yStateText(count($orphans) === 1 ? 'One disclosure entered under the current profile has no field under this one and will be kept, unchanged and not shown:' : count($orphans).' disclosures entered under the current profile have no field under this one and will be kept, unchanged and not shown:') @a11yStateText(implode(', ', $orphans)).</p>
                    @endif
                </div>
            @endif
        </div>
    </ui-card></ui-panel>

    <form method="post" action="@a11yStateText($urls['save'])" class="space-y-6">
        @csrf
        <input type="hidden" name="site" value="@a11yStateText($site)">

        <ui-panel><ui-panel-header><ui-heading level="2" text="Profile and claim" /></ui-panel-header><ui-card>
            <div class="space-y-4">
                <div>
                    <label for="a11y-state-profile" class="{{ $label }}">Jurisdiction profile</label>
                    <select id="a11y-state-profile" name="profile" class="{{ $control }}" @if (! $canEdit) disabled @endif>
                        @foreach ($profiles as $p)
                            <option value="@a11yStateText($p->handle)" @if ($p->handle === $profile->handle) selected @endif>@a11yStateText($p->name)</option>
                        @endforeach
                    </select>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">@a11yStateText($profile->description) To see another profile's fields before saving, open it:
                        @foreach ($profiles as $p)
                            @if ($p->handle !== $profile->handle)
                                <a class="{{ $link }}" href="@a11yStateText($urls['index'].'&profile='.$p->handle)">@a11yStateText($p->name)</a>@if (! $loop->last),@endif
                            @endif
                        @endforeach
                    </p>
                </div>

                <fieldset>
                    <legend class="{{ $label }}">Conformance status</legend>
                    <div class="space-y-2">
                        @foreach ($statuses as $status)
                            <div class="flex items-start gap-2">
                                <input type="radio" id="a11y-state-status-@a11yStateText($status->value)" name="status" value="@a11yStateText($status->value)" class="mt-1" @if ($s->status === $status) checked @endif @if (! $canEdit) disabled @endif>
                                <label for="a11y-state-status-@a11yStateText($status->value)"><strong>@a11yStateText($status->label()).</strong> @a11yStateText($status->definition())</label>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        @if ($s->status === \Bpmore\A11yState\Statement\ConformanceStatus::FullyConformant && $s->attestation)
                            Fully conformant, attested by @a11yStateText($s->attestation->name) on @a11yStateText($s->attestation->date). Choosing any other status clears the attestation.
                        @else
                            "Fully conformant" is not a choice here. It is made below, under Attest, by a named person on a date, and only when no exception at or above @a11yStateText($overclaimSeverity) severity is open.
                        @endif
                    </p>
                </fieldset>
            </div>
        </ui-card></ui-panel>

        <ui-panel><ui-panel-header><ui-heading level="2" text="@a11yStateText('Disclosures: '.$profile->name)" /></ui-panel-header><ui-card>
            <div class="space-y-4">
                @foreach ($profile->disclosures as $d)
                    @continue(in_array($d->key, \Bpmore\A11yState\Statement\Disclosure::STRUCTURED, true))
                    @php
                        $id = 'a11y-state-field-'.$d->key;
                        $value = (string) $old('disclosures.'.$d->key, is_string($s->disclosure($d->key)) ? $s->disclosure($d->key) : '');
                        if ($value === '' && $d->key === 'standard' && $profile->standardDefault !== null && ! $exists) { $value = $profile->standardDefault; }
                        $required = $d->isRequiredFor($s->status);
                        $hasError = isset($fieldErrors[$d->key]);
                    @endphp
                    <div>
                        <label for="{{ $id }}" class="{{ $label }}">@a11yStateText($d->label) <span class="font-normal text-gray-600 dark:text-gray-400">(@a11yStateText($required ? 'required' : ($d->required === \Bpmore\A11yState\Statement\Disclosure::UNLESS_FULLY_CONFORMANT ? 'required unless fully conformant' : 'optional')))</span></label>
                        @if ($d->type === 'textarea')
                            <textarea id="{{ $id }}" name="disclosures[@a11yStateText($d->key)]" rows="3" class="{{ $control }}" @if ($required) aria-required="true" @endif @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($d->hint || $d->inferred) aria-describedby="{{ $id }}-hint" @endif @if (! $canEdit) readonly @endif>@a11yStateText($value)</textarea>
                        @else
                            <input id="{{ $id }}" name="disclosures[@a11yStateText($d->key)]" type="@a11yStateText($d->type === 'date' ? 'date' : ($d->type === 'url' ? 'url' : 'text'))" value="@a11yStateText($value)" class="{{ $control }}" @if ($required) aria-required="true" @endif @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($d->hint || $d->inferred) aria-describedby="{{ $id }}-hint" @endif @if (! $canEdit) readonly @endif>
                        @endif
                        @if ($hasError)
                            <p id="{{ $id }}-error" class="text-sm mt-1"><strong>Error:</strong> @a11yStateText($fieldErrors[$d->key][0])</p>
                        @elseif ($d->hint || $d->inferred)
                            <p id="{{ $id }}-hint" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                @if ($d->hint) @a11yStateText($d->hint) @endif
                                @if ($d->inferred) This field is not required by the regime's own text; it is here from the W3C model. @endif
                            </p>
                        @endif
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Source: @a11yStateText($d->source)</p>
                    </div>
                @endforeach

                @if ($orphans !== [] && ! $switching)
                    <div class="rounded border border-gray-300 dark:border-gray-700 p-3 space-y-1">
                        <p><strong>Kept from another profile.</strong> These disclosures have values but no field under @a11yStateText($profile->name). They stay on the statement and are not printed unless a profile lists them.</p>
                        <ul class="ps-5" style="list-style: disc">
                            @foreach ($orphans as $key)
                                <li><strong>@a11yStateText($key):</strong> @a11yStateText(is_string($s->disclosure($key)) ? \Illuminate\Support\Str::limit($s->disclosure($key), 120) : '')</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </ui-card></ui-panel>

        <ui-panel><ui-panel-header><ui-heading level="2" text="Known exceptions" /></ui-panel-header><ui-card>
            <div class="space-y-4">
                @if ($s->exceptions === [] && $external === [])
                    <p>None recorded.</p>
                @else
                    {{-- Scrolls sideways on a narrow screen instead of pushing the page out: a seven-column table at phone width otherwise clips or overflows. Focusable and named by its caption because Safari keeps the keyboard out of a scroll area otherwise, which left the columns past the edge out of reach. --}}
<div class="overflow-x-auto" tabindex="0" role="region" aria-labelledby="a11y-state-exceptions-caption">
<table class="w-full text-sm">
                        <caption id="a11y-state-exceptions-caption" class="text-left font-medium mb-2"><span class="sr-only">Known exceptions: </span>@a11yStateText(count($s->openExceptions()) + count($external)) open, @a11yStateText(count($s->exceptions) - count($s->openExceptions())) resolved</caption>
                        <thead>
                            <tr class="text-left">
                                <th scope="col" class="py-1 pe-3">Description</th>
                                <th scope="col" class="py-1 pe-3">Severity</th>
                                <th scope="col" class="py-1 pe-3">Criterion</th>
                                <th scope="col" class="py-1 pe-3">Page</th>
                                <th scope="col" class="py-1 pe-3">Planned fix</th>
                                <th scope="col" class="py-1 pe-3">Status</th>
                                @if ($canEdit)<th scope="col" class="py-1">Resolve</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($s->exceptions as $e)
                                <tr class="border-t border-gray-200 dark:border-gray-800 align-middle">
                                    <td class="py-1 pe-3">@a11yStateText($e->description)@if ($e->reason) <span class="text-gray-600 dark:text-gray-400">Reason: @a11yStateText($e->reason)</span>@endif</td>
                                    <td class="py-1 pe-3">@a11yStateText($e->severity->label())</td>
                                    <td class="py-1 pe-3">@a11yStateText($e->criterion ?? '')</td>
                                    <td class="py-1 pe-3">@a11yStateText($e->page ?? '')</td>
                                    <td class="py-1 pe-3">@a11yStateText($e->remediationDate ?? '')</td>
                                    <td class="py-1 pe-3">@a11yStateText(ucfirst($e->status))</td>
                                    @if ($canEdit)
                                        <td class="py-1">
                                            @if ($e->isOpen())
                                                <input type="checkbox" id="a11y-state-resolve-@a11yStateText($e->id)" name="resolve[]" value="@a11yStateText($e->id)">
                                                <label for="a11y-state-resolve-@a11yStateText($e->id)">Resolved</label>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            @foreach ($external as $e)
                                <tr class="border-t border-gray-200 dark:border-gray-800 align-middle">
                                    <td class="py-1 pe-3">@a11yStateText($e->description) <span class="text-gray-600 dark:text-gray-400">(from @a11yStateText($e->source))</span></td>
                                    <td class="py-1 pe-3">@a11yStateText($e->severity->label())</td>
                                    <td class="py-1 pe-3">@a11yStateText($e->criterion ?? '')</td>
                                    <td class="py-1 pe-3">@a11yStateText($e->page ?? '')</td>
                                    <td class="py-1 pe-3"></td>
                                    <td class="py-1 pe-3">Open</td>
                                    @if ($canEdit)<td class="py-1"><span class="text-gray-600 dark:text-gray-400">Closed in @a11yStateText($e->source)</span></td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
</div>
                @endif

                @if ($canEdit)
                    <fieldset class="space-y-3">
                        <legend class="font-medium">Add an exception</legend>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Leave the description empty to add nothing. Adding one writes an evidence record.</p>
                        <div>
                            <label for="a11y-state-new-description" class="{{ $label }}">Description</label>
                            <textarea id="a11y-state-new-description" name="new_exception[description]" rows="2" class="{{ $control }}">@a11yStateText($old('new_exception.description'))</textarea>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="a11y-state-new-severity" class="{{ $label }}">Severity</label>
                                <select id="a11y-state-new-severity" name="new_exception[severity]" class="{{ $control }}">
                                    @foreach ($severities as $sev)
                                        <option value="@a11yStateText($sev->value)" @if ($old('new_exception.severity', 'moderate') === $sev->value) selected @endif>@a11yStateText($sev->label())</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="a11y-state-new-criterion" class="{{ $label }}">WCAG success criterion (optional, such as 1.1.1)</label>
                                <input id="a11y-state-new-criterion" name="new_exception[criterion]" type="text" value="@a11yStateText($old('new_exception.criterion'))" class="{{ $control }}">
                            </div>
                            <div>
                                <label for="a11y-state-new-page" class="{{ $label }}">Page or asset (optional)</label>
                                <input id="a11y-state-new-page" name="new_exception[page]" type="text" value="@a11yStateText($old('new_exception.page'))" class="{{ $control }}">
                            </div>
                            <div>
                                <label for="a11y-state-new-date" class="{{ $label }}">Planned fix date (optional)</label>
                                <input id="a11y-state-new-date" name="new_exception[remediation_date]" type="date" value="@a11yStateText($old('new_exception.remediation_date'))" class="{{ $control }}">
                            </div>
                        </div>
                        <div>
                            <label for="a11y-state-new-reason" class="{{ $label }}">Reason (optional)</label>
                            <input id="a11y-state-new-reason" name="new_exception[reason]" type="text" value="@a11yStateText($old('new_exception.reason'))" class="{{ $control }}">
                        </div>
                    </fieldset>
                @endif
            </div>
        </ui-card></ui-panel>

        @if ($canEdit)
            <div class="flex flex-wrap items-center gap-3">
                <ui-button type="submit" variant="primary" text="Save statement" />
                <ui-button href="@a11yStateText($urls['preview'])" text="Preview" />
                <ui-button href="@a11yStateText($urls['exportHtml'])" text="Download HTML" />
                <ui-button href="@a11yStateText($urls['exportMarkdown'])" text="Download Markdown" />
            </div>
        @else
            <p>You can read this statement but not change it. The "Edit the accessibility statement" permission is needed for that.</p>
        @endif
    </form>

    <ui-panel id="a11y-state-attest"><ui-panel-header><ui-heading level="2" text="Attest to full conformance" /></ui-panel-header><ui-card>
        <div class="space-y-3">
            @if ($s->status === \Bpmore\A11yState\Statement\ConformanceStatus::FullyConformant && $s->attestation)
                <p>Attested by <strong>@a11yStateText($s->attestation->name)</strong> on <strong>@a11yStateText($s->attestation->date)</strong>.@if ($s->attestation->recordId) Evidence record @a11yStateText($s->attestation->recordId).@endif</p>
                <p>Basis: @a11yStateText($s->attestation->basis)</p>
            @elseif ($blocking !== [])
                <p><strong>Not available.</strong> @a11yStateText(count($blocking) === 1 ? 'One open exception at or above '.$overclaimSeverity.' severity stands in the way:' : count($blocking).' open exceptions at or above '.$overclaimSeverity.' severity stand in the way:')</p>
                <ul class="ps-5" style="list-style: disc">
                    @foreach ($blocking as $e)
                        <li>@a11yStateText($e->summary())</li>
                    @endforeach
                </ul>
            @elseif ($canAttest)
                <p>A statement says "fully conformant" only when a named person confirms it on a date and says on what basis. This is written to the evidence log and printed on the statement. Everything the profile requires must already be filled in.</p>
                <form method="post" action="@a11yStateText($urls['attest'])" class="space-y-3">
                    @csrf
                    <input type="hidden" name="site" value="@a11yStateText($site)">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="a11y-state-attest-name" class="{{ $label }}">Name of the person attesting</label>
                            <input id="a11y-state-attest-name" name="attestation[name]" type="text" value="@a11yStateText($old('attestation.name'))" class="{{ $control }}" aria-required="true">
                        </div>
                        <div>
                            <label for="a11y-state-attest-date" class="{{ $label }}">Date</label>
                            <input id="a11y-state-attest-date" name="attestation[date]" type="date" value="@a11yStateText($old('attestation.date'))" class="{{ $control }}" aria-required="true">
                        </div>
                    </div>
                    <div>
                        <label for="a11y-state-attest-basis" class="{{ $label }}">Basis: what was evaluated, how, and by whom</label>
                        <textarea id="a11y-state-attest-basis" name="attestation[basis]" rows="3" class="{{ $control }}" aria-required="true">@a11yStateText($old('attestation.basis'))</textarea>
                    </div>
                    <ui-button type="submit" variant="primary" text="Record the attestation and set fully conformant" />
                </form>
            @else
                <p>Nothing stands in the way, and attesting needs the "Attest to full conformance" permission, which you do not have.</p>
            @endif
        </div>
    </ui-card></ui-panel>

    <ui-panel><ui-panel-header><ui-heading level="2" text="Integrations" /></ui-panel-header><ui-card>
        <p>@if ($externalSource === 'none')No other accessibility addon is feeding this statement. Exceptions are entered by hand above.@else Open findings from <strong>@a11yStateText($externalSource)</strong> are listed among the exceptions and count against full conformance. Fixes can be imported into the evidence log from the Evidence screen.@endif</p>
    </ui-card></ui-panel>

    @include('a11y-state::utilities.disclaimer')
</div>
