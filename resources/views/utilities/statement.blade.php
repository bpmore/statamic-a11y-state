{{--
    The statement editor. Compiled as a Vue template, not printed as HTML,
    so every data value goes through @grangeText and there is no `{{ }}`
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

<ui-header title="Accessibility Statement" icon="list-checklist" />

<div class="space-y-6">

    @include('grange::utilities.nav', ['active' => 'statement'])

    @if (count($sites) > 1)
        <nav aria-label="Site" class="flex flex-wrap items-center gap-2">
            @foreach ($sites as $one)
                <ui-button size="sm" href="@grangeText(cp_route('utilities.index').'/grange?site='.$one['handle'])" text="@grangeText($one['name'])" @if ($site === $one['handle']) variant="primary" aria-current="page" @endif />
            @endforeach
        </nav>
    @endif

    {{-- Completeness, first, because it is the answer to "can this be published". --}}
    <ui-card-panel heading="@grangeText($errors === [] ? 'Complete under the '.$profile->name.' profile' : count($errors).' '.(count($errors) === 1 ? 'thing stops' : 'things stop').' this statement being generated')">
        <div class="space-y-3">
            @if ($errors === [] && $warnings === [])
                <p>Every disclosure the profile requires is present, the claim is within what the evidence allows, and the assessment is inside the {{ $staleAfterDays }}-day window.@if ($publicUrl) The statement is published at <a class="{{ $link }}" href="@grangeText($publicUrl)">@grangeText($publicUrl)</a>.@endif</p>
            @endif

            @if ($errors !== [])
                <ul class="ps-5 space-y-1" style="list-style: disc" aria-label="Problems that stop generation">
                    @foreach ($errors as $p)
                        <li><strong>Error:</strong> @grangeText($p->message)@if ($p->field !== null && isset($fieldErrors[$p->field])) <a class="{{ $link }}" href="#grange-field-@grangeText($p->field)">Go to the field</a>@endif</li>
                    @endforeach
                </ul>
            @endif

            @if ($stale !== null)
                <div role="status" class="rounded border border-amber-400 p-3">
                    <p><strong>Stale assessment.</strong> @grangeText($stale->message)</p>
                </div>
            @endif

            @if ($switching)
                <div role="status" class="rounded border border-amber-400 p-3 space-y-2">
                    <p><strong>You are looking at the @grangeText($profile->name) profile. The statement is saved under @grangeText($savedProfileName).</strong> Nothing changes until you save.</p>
                    @if ($orphans !== [])
                        <p>@grangeText(count($orphans) === 1 ? 'One disclosure entered under the current profile has no field under this one and will be kept, unchanged and not shown:' : count($orphans).' disclosures entered under the current profile have no field under this one and will be kept, unchanged and not shown:') @grangeText(implode(', ', $orphans)).</p>
                    @endif
                </div>
            @endif
        </div>
    </ui-card-panel>

    <form method="post" action="@grangeText($urls['save'])" class="space-y-6">
        @csrf
        <input type="hidden" name="site" value="@grangeText($site)">

        <ui-card-panel heading="Profile and claim">
            <div class="space-y-4">
                <div>
                    <label for="grange-profile" class="{{ $label }}">Jurisdiction profile</label>
                    <select id="grange-profile" name="profile" class="{{ $control }}" @if (! $canEdit) disabled @endif>
                        @foreach ($profiles as $p)
                            <option value="@grangeText($p->handle)" @if ($p->handle === $profile->handle) selected @endif>@grangeText($p->name)</option>
                        @endforeach
                    </select>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">@grangeText($profile->description) To see another profile's fields before saving, open it:
                        @foreach ($profiles as $p)
                            @if ($p->handle !== $profile->handle)
                                <a class="{{ $link }}" href="@grangeText($urls['index'].'&profile='.$p->handle)">@grangeText($p->name)</a>@if (! $loop->last),@endif
                            @endif
                        @endforeach
                    </p>
                </div>

                <fieldset>
                    <legend class="{{ $label }}">Conformance status</legend>
                    <div class="space-y-2">
                        @foreach ($statuses as $status)
                            <div class="flex items-start gap-2">
                                <input type="radio" id="grange-status-@grangeText($status->value)" name="status" value="@grangeText($status->value)" class="mt-1" @if ($s->status === $status) checked @endif @if (! $canEdit) disabled @endif>
                                <label for="grange-status-@grangeText($status->value)"><strong>@grangeText($status->label()).</strong> @grangeText($status->definition())</label>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        @if ($s->status === \Bpmore\Grange\Statement\ConformanceStatus::FullyConformant && $s->attestation)
                            Fully conformant, attested by @grangeText($s->attestation->name) on @grangeText($s->attestation->date). Choosing any other status clears the attestation.
                        @else
                            "Fully conformant" is not a choice here. It is made below, under Attest, by a named person on a date, and only when no exception at or above @grangeText($overclaimSeverity) severity is open.
                        @endif
                    </p>
                </fieldset>
            </div>
        </ui-card-panel>

        <ui-card-panel heading="@grangeText('Disclosures: '.$profile->name)">
            <div class="space-y-4">
                @foreach ($profile->disclosures as $d)
                    @continue(in_array($d->key, \Bpmore\Grange\Statement\Disclosure::STRUCTURED, true))
                    @php
                        $id = 'grange-field-'.$d->key;
                        $value = (string) $old('disclosures.'.$d->key, is_string($s->disclosure($d->key)) ? $s->disclosure($d->key) : '');
                        if ($value === '' && $d->key === 'standard' && $profile->standardDefault !== null && ! $exists) { $value = $profile->standardDefault; }
                        $required = $d->isRequiredFor($s->status);
                        $hasError = isset($fieldErrors[$d->key]);
                    @endphp
                    <div>
                        <label for="{{ $id }}" class="{{ $label }}">@grangeText($d->label) <span class="font-normal text-gray-600 dark:text-gray-400">(@grangeText($required ? 'required' : ($d->required === \Bpmore\Grange\Statement\Disclosure::UNLESS_FULLY_CONFORMANT ? 'required unless fully conformant' : 'optional')))</span></label>
                        @if ($d->type === 'textarea')
                            <textarea id="{{ $id }}" name="disclosures[@grangeText($d->key)]" rows="3" class="{{ $control }}" @if ($required) aria-required="true" @endif @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($d->hint || $d->inferred) aria-describedby="{{ $id }}-hint" @endif @if (! $canEdit) readonly @endif>@grangeText($value)</textarea>
                        @else
                            <input id="{{ $id }}" name="disclosures[@grangeText($d->key)]" type="@grangeText($d->type === 'date' ? 'date' : ($d->type === 'url' ? 'url' : 'text'))" value="@grangeText($value)" class="{{ $control }}" @if ($required) aria-required="true" @endif @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($d->hint || $d->inferred) aria-describedby="{{ $id }}-hint" @endif @if (! $canEdit) readonly @endif>
                        @endif
                        @if ($hasError)
                            <p id="{{ $id }}-error" class="text-sm mt-1"><strong>Error:</strong> @grangeText($fieldErrors[$d->key][0])</p>
                        @elseif ($d->hint || $d->inferred)
                            <p id="{{ $id }}-hint" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                @if ($d->hint) @grangeText($d->hint) @endif
                                @if ($d->inferred) This field is not required by the regime's own text; it is here from the W3C model. @endif
                            </p>
                        @endif
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Source: @grangeText($d->source)</p>
                    </div>
                @endforeach

                @if ($orphans !== [] && ! $switching)
                    <div class="rounded border border-gray-300 dark:border-gray-700 p-3 space-y-1">
                        <p><strong>Kept from another profile.</strong> These disclosures have values but no field under @grangeText($profile->name). They stay on the statement and are not printed unless a profile lists them.</p>
                        <ul class="ps-5" style="list-style: disc">
                            @foreach ($orphans as $key)
                                <li><strong>@grangeText($key):</strong> @grangeText(is_string($s->disclosure($key)) ? \Illuminate\Support\Str::limit($s->disclosure($key), 120) : '')</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </ui-card-panel>

        <ui-card-panel heading="Known exceptions">
            <div class="space-y-4">
                @if ($s->exceptions === [] && $external === [])
                    <p>None recorded.</p>
                @else
                    <table class="w-full text-sm">
                        <caption class="text-left font-medium mb-2">@grangeText(count($s->openExceptions()) + count($external)) open, @grangeText(count($s->exceptions) - count($s->openExceptions())) resolved</caption>
                        <thead>
                            <tr class="text-left">
                                <th scope="col" class="py-1 pr-3">Description</th>
                                <th scope="col" class="py-1 pr-3">Severity</th>
                                <th scope="col" class="py-1 pr-3">Criterion</th>
                                <th scope="col" class="py-1 pr-3">Page</th>
                                <th scope="col" class="py-1 pr-3">Planned fix</th>
                                <th scope="col" class="py-1 pr-3">Status</th>
                                @if ($canEdit)<th scope="col" class="py-1">Resolve</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($s->exceptions as $e)
                                <tr class="border-t border-gray-200 dark:border-gray-800 align-middle">
                                    <td class="py-1 pr-3">@grangeText($e->description)@if ($e->reason) <span class="text-gray-600 dark:text-gray-400">Reason: @grangeText($e->reason)</span>@endif</td>
                                    <td class="py-1 pr-3">@grangeText($e->severity->label())</td>
                                    <td class="py-1 pr-3">@grangeText($e->criterion ?? '')</td>
                                    <td class="py-1 pr-3">@grangeText($e->page ?? '')</td>
                                    <td class="py-1 pr-3">@grangeText($e->remediationDate ?? '')</td>
                                    <td class="py-1 pr-3">@grangeText(ucfirst($e->status))</td>
                                    @if ($canEdit)
                                        <td class="py-1">
                                            @if ($e->isOpen())
                                                <input type="checkbox" id="grange-resolve-@grangeText($e->id)" name="resolve[]" value="@grangeText($e->id)">
                                                <label for="grange-resolve-@grangeText($e->id)">Resolved</label>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            @foreach ($external as $e)
                                <tr class="border-t border-gray-200 dark:border-gray-800 align-middle">
                                    <td class="py-1 pr-3">@grangeText($e->description) <span class="text-gray-600 dark:text-gray-400">(from @grangeText($e->source))</span></td>
                                    <td class="py-1 pr-3">@grangeText($e->severity->label())</td>
                                    <td class="py-1 pr-3">@grangeText($e->criterion ?? '')</td>
                                    <td class="py-1 pr-3">@grangeText($e->page ?? '')</td>
                                    <td class="py-1 pr-3"></td>
                                    <td class="py-1 pr-3">Open</td>
                                    @if ($canEdit)<td class="py-1"><span class="text-gray-600 dark:text-gray-400">Closed in @grangeText($e->source)</span></td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($canEdit)
                    <fieldset class="space-y-3">
                        <legend class="font-medium">Add an exception</legend>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Leave the description empty to add nothing. Adding one writes an evidence record.</p>
                        <div>
                            <label for="grange-new-description" class="{{ $label }}">Description</label>
                            <textarea id="grange-new-description" name="new_exception[description]" rows="2" class="{{ $control }}">@grangeText($old('new_exception.description'))</textarea>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label for="grange-new-severity" class="{{ $label }}">Severity</label>
                                <select id="grange-new-severity" name="new_exception[severity]" class="{{ $control }}">
                                    @foreach ($severities as $sev)
                                        <option value="@grangeText($sev->value)" @if ($old('new_exception.severity', 'moderate') === $sev->value) selected @endif>@grangeText($sev->label())</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="grange-new-criterion" class="{{ $label }}">WCAG success criterion (optional, such as 1.1.1)</label>
                                <input id="grange-new-criterion" name="new_exception[criterion]" type="text" value="@grangeText($old('new_exception.criterion'))" class="{{ $control }}">
                            </div>
                            <div>
                                <label for="grange-new-page" class="{{ $label }}">Page or asset (optional)</label>
                                <input id="grange-new-page" name="new_exception[page]" type="text" value="@grangeText($old('new_exception.page'))" class="{{ $control }}">
                            </div>
                            <div>
                                <label for="grange-new-date" class="{{ $label }}">Planned fix date (optional)</label>
                                <input id="grange-new-date" name="new_exception[remediation_date]" type="date" value="@grangeText($old('new_exception.remediation_date'))" class="{{ $control }}">
                            </div>
                        </div>
                        <div>
                            <label for="grange-new-reason" class="{{ $label }}">Reason (optional)</label>
                            <input id="grange-new-reason" name="new_exception[reason]" type="text" value="@grangeText($old('new_exception.reason'))" class="{{ $control }}">
                        </div>
                    </fieldset>
                @endif
            </div>
        </ui-card-panel>

        @if ($canEdit)
            <div class="flex flex-wrap items-center gap-3">
                <ui-button type="submit" variant="primary" text="Save statement" />
                <ui-button href="@grangeText($urls['preview'])" text="Preview" />
                <ui-button href="@grangeText($urls['exportHtml'])" text="Download HTML" />
                <ui-button href="@grangeText($urls['exportMarkdown'])" text="Download Markdown" />
            </div>
        @else
            <p>You can read this statement but not change it. The "Edit the accessibility statement" permission is needed for that.</p>
        @endif
    </form>

    <ui-card-panel heading="Attest to full conformance" id="grange-attest">
        <div class="space-y-3">
            @if ($s->status === \Bpmore\Grange\Statement\ConformanceStatus::FullyConformant && $s->attestation)
                <p>Attested by <strong>@grangeText($s->attestation->name)</strong> on <strong>@grangeText($s->attestation->date)</strong>.@if ($s->attestation->recordId) Evidence record @grangeText($s->attestation->recordId).@endif</p>
                <p>Basis: @grangeText($s->attestation->basis)</p>
            @elseif ($blocking !== [])
                <p><strong>Not available.</strong> @grangeText(count($blocking) === 1 ? 'One open exception at or above '.$overclaimSeverity.' severity stands in the way:' : count($blocking).' open exceptions at or above '.$overclaimSeverity.' severity stand in the way:')</p>
                <ul class="ps-5" style="list-style: disc">
                    @foreach ($blocking as $e)
                        <li>@grangeText($e->summary())</li>
                    @endforeach
                </ul>
            @elseif ($canAttest)
                <p>A statement says "fully conformant" only when a named person confirms it on a date and says on what basis. This is written to the evidence log and printed on the statement. Everything the profile requires must already be filled in.</p>
                <form method="post" action="@grangeText($urls['attest'])" class="space-y-3">
                    @csrf
                    <input type="hidden" name="site" value="@grangeText($site)">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="grange-attest-name" class="{{ $label }}">Name of the person attesting</label>
                            <input id="grange-attest-name" name="attestation[name]" type="text" value="@grangeText($old('attestation.name'))" class="{{ $control }}" aria-required="true">
                        </div>
                        <div>
                            <label for="grange-attest-date" class="{{ $label }}">Date</label>
                            <input id="grange-attest-date" name="attestation[date]" type="date" value="@grangeText($old('attestation.date'))" class="{{ $control }}" aria-required="true">
                        </div>
                    </div>
                    <div>
                        <label for="grange-attest-basis" class="{{ $label }}">Basis: what was evaluated, how, and by whom</label>
                        <textarea id="grange-attest-basis" name="attestation[basis]" rows="3" class="{{ $control }}" aria-required="true">@grangeText($old('attestation.basis'))</textarea>
                    </div>
                    <ui-button type="submit" variant="primary" text="Record the attestation and set fully conformant" />
                </form>
            @else
                <p>Nothing stands in the way, and attesting needs the "Attest to full conformance" permission, which you do not have.</p>
            @endif
        </div>
    </ui-card-panel>

    <ui-card-panel heading="Integrations">
        <p>@if ($externalSource === 'none')No other accessibility addon is feeding this statement. Exceptions are entered by hand above.@else Open findings from <strong>@grangeText($externalSource)</strong> are listed among the exceptions and count against full conformance. Fixes can be imported into the evidence log from the Evidence screen.@endif</p>
    </ui-card-panel>

    @include('grange::utilities.disclaimer')
</div>
