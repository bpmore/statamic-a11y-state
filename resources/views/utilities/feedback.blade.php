{{-- The acknowledgment clock for the feedback form. --}}
@php
    $control = 'rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1 text-sm';
@endphp

<ui-header title="A11y State" icon="list-checklist" />

<div class="space-y-6">

    @include('a11y-state::utilities.nav', ['active' => 'feedback'])

    @if ($form === null)
        <ui-panel><ui-panel-header><ui-heading level="2" text="No feedback form configured" /></ui-panel-header><ui-card>
            <p>Set <code>feedback.form</code> in <code>config/statamic-a11y-state.php</code> to the handle of the Statamic form that receives accessibility feedback. Acknowledging a submission here writes an evidence record and stops its clock.</p>
        </ui-card></ui-panel>
    @elseif (! $formExists)
        <ui-panel><ui-panel-header><ui-heading level="2" text="The configured form does not exist" /></ui-panel-header><ui-card>
            <p>The config file names a form called <code>@a11yStateText($form)</code> and there is no such form.</p>
        </ui-card></ui-panel>
    @else
        <ui-panel><ui-panel-header><ui-heading level="2" text="@a11yStateText(count($report->rows).' '.(count($report->rows) === 1 ? 'submission' : 'submissions').', '.count($report->awaiting()).' awaiting acknowledgment'.($report->hasWindow() ? ', '.count($report->overdue()).' past the '.$report->responseDays.'-day window' : ''))" /></ui-panel-header><ui-card>
            <div class="space-y-4">
                @if (! $report->hasWindow())
                    <p>No response window is configured, so nothing is overdue and the statement makes no claim about response times. Set <code>feedback.response_days</code> to start the clock.</p>
                @endif

                @if ($report->rows === [])
                    <p>The form <code>@a11yStateText($form)</code> has no submissions.</p>
                @else
                    {{-- Scrolls sideways on a narrow screen instead of pushing the page out: a seven-column table at phone width otherwise clips or overflows. Focusable and named by its caption because Safari keeps the keyboard out of a scroll area otherwise, which left the columns past the edge out of reach. --}}
<div class="overflow-x-auto" tabindex="0" role="region" aria-labelledby="a11y-state-feedback-caption">
<table class="w-full text-sm">
                        <caption id="a11y-state-feedback-caption" class="sr-only">Feedback submissions, newest first</caption>
                        <thead>
                            <tr class="text-left">
                                <th scope="col" class="py-1 pr-3">Received</th>
                                <th scope="col" class="py-1 pr-3">Summary</th>
                                <th scope="col" class="py-1 pr-3">Waited</th>
                                <th scope="col" class="py-1 pr-3">Status</th>
                                @if ($canRecord)<th scope="col" class="py-1">Acknowledge</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report->rows as $row)
                                <tr class="border-t border-gray-200 dark:border-gray-800 align-middle">
                                    <td class="py-1 pr-3 whitespace-nowrap"><time datetime="@a11yStateText($row->submission->at)">@a11yStateText(substr($row->submission->at, 0, 10))</time></td>
                                    <td class="py-1 pr-3">@a11yStateText($row->submission->summary)</td>
                                    <td class="py-1 pr-3">{{ $row->days }} {{ $row->days === 1 ? 'day' : 'days' }}</td>
                                    <td class="py-1 pr-3">
                                        @if ($row->isAcknowledged())
                                            Acknowledged by @a11yStateText($row->acknowledgedBy) on @a11yStateText(substr((string) $row->acknowledgedAt, 0, 10))
                                        @elseif ($row->overdue)
                                            <strong>Past the window</strong>
                                        @else
                                            Awaiting acknowledgment
                                        @endif
                                    </td>
                                    @if ($canRecord)
                                        <td class="py-1">
                                            @if (! $row->isAcknowledged())
                                                <form method="post" action="@a11yStateText($urls['feedbackAcknowledge'])" class="flex flex-wrap items-end gap-2">
                                                    @csrf
                                                    <input type="hidden" name="site" value="@a11yStateText($site)">
                                                    <input type="hidden" name="submission" value="@a11yStateText($row->submission->id)">
                                                    <div>
                                                        <label for="a11y-state-ack-@a11yStateText($row->submission->id)" class="block text-xs font-medium mb-1">How it was answered</label>
                                                        <input id="a11y-state-ack-@a11yStateText($row->submission->id)" name="notes" type="text" class="{{ $control }}" size="20">
                                                    </div>
                                                    <ui-button type="submit" size="sm" text="Acknowledge" />
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
</div>
                @endif
            </div>
        </ui-card></ui-panel>
    @endif

    @include('a11y-state::utilities.disclaimer')
</div>
