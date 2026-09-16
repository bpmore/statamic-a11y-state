<?php

declare(strict_types=1);

namespace Bpmore\Grange\Http\Controllers;

use Bpmore\Grange\Statement\StatementRoute;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\FrontendController;
use Statamic\View\View;

/**
 * The public page. Extends Statamic's front-end controller for its
 * middleware, so the statement is a Statamic page like any other: the
 * right site, the site's protection, and the site's static cache.
 *
 * Whether a page is served at all is the tag's decision: an incomplete
 * statement renders a short, honest notice rather than a document with
 * holes in it, and never a 500. See `Tags\Grange`.
 */
final class StatementController extends FrontendController
{
    public function __invoke(Request $request)
    {
        $data = StatementRoute::data();

        $view = app(View::class)
            ->template(StatementRoute::view())
            ->layout($data['layout'])
            ->with($data);

        return response($view->render(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
