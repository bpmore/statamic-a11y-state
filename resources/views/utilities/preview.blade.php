{{--
    The generated statement as the public page will show it, or the reasons
    it cannot be generated. The document's own markup is trusted here: it
    is this addon's renderer, every value in it already escaped, and it
    contains no braces Vue would read as its own.
--}}
<ui-header title="Accessibility Statement" icon="list-checklist" />

<div class="space-y-6">

    @include('grange::utilities.nav', ['active' => 'preview'])

    @if ($html === null)
        <ui-card-panel heading="This statement cannot be generated yet">
            <ul class="ps-5 space-y-1" style="list-style: disc">
                @foreach ($problems as $p)
                    <li>@grangeText($p->message)</li>
                @endforeach
            </ul>
            <p class="mt-3"><ui-button href="@grangeText($urls['index'])" text="Back to the editor" /></p>
        </ui-card-panel>
    @else
        <ui-card-panel heading="Preview">
            <div class="prose">
                {!! \Bpmore\Grange\Support\VueSafe::braces($html) !!}
            </div>
        </ui-card-panel>
        <div class="flex flex-wrap items-center gap-3">
            <ui-button href="@grangeText($urls['exportHtml'])" text="Download HTML" />
            <ui-button href="@grangeText($urls['exportMarkdown'])" text="Download Markdown" />
        </div>
    @endif

    @include('grange::utilities.disclaimer')
</div>
