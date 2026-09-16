{{-- The utility's own sections, as a row of links. Tabs need state a static template does not have; links do not. --}}
<nav aria-label="Accessibility statement sections" class="flex flex-wrap items-center gap-2">
    <ui-button size="sm" href="@a11yStateText($urls['index'])" text="Statement" @if ($active === 'statement') variant="primary" aria-current="page" @endif />
    <ui-button size="sm" href="@a11yStateText($urls['preview'])" text="Preview" @if ($active === 'preview') variant="primary" aria-current="page" @endif />
    <ui-button size="sm" href="@a11yStateText($urls['evidence'])" text="Evidence" @if ($active === 'evidence') variant="primary" aria-current="page" @endif />
    <ui-button size="sm" href="@a11yStateText($urls['feedback'])" text="Feedback" @if ($active === 'feedback') variant="primary" aria-current="page" @endif />
</nav>
