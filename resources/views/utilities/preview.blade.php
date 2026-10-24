{{--
    The generated statement as the public page will show it, or the reasons
    it cannot be generated. The document's own markup is trusted here: it
    is this addon's renderer, every value in it already escaped, and it
    contains no braces Vue would read as its own.
--}}
<ui-header title="A11y State" icon="list-checklist" />

<div class="space-y-6">

    @include('a11y-state::utilities.nav', ['active' => 'preview'])

    @if ($html === null)
        <ui-panel><ui-panel-header><ui-heading level="2" text="This statement cannot be generated yet" /></ui-panel-header><ui-card>
            <ul class="ps-5 space-y-1" style="list-style: disc">
                @foreach ($problems as $p)
                    <li>@a11yStateText($p->message)</li>
                @endforeach
            </ul>
            <p class="mt-3"><ui-button href="@a11yStateText($urls['index'])" text="Back to the editor" /></p>
        </ui-card></ui-panel>
    @else
        <ui-panel><ui-panel-header><ui-heading level="2" text="Preview" /></ui-panel-header><ui-card>
            <div class="prose">
                {!! \Bpmore\A11yState\Support\VueSafe::braces($html) !!}
            </div>
        </ui-card></ui-panel>
        <div class="flex flex-wrap items-center gap-3">
            <ui-button href="@a11yStateText($urls['exportHtml'])" text="Download HTML" />
            <ui-button href="@a11yStateText($urls['exportMarkdown'])" text="Download Markdown" />
        </div>
    @endif

    @include('a11y-state::utilities.disclaimer')
</div>
