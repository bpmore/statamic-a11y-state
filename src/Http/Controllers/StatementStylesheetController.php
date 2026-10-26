<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The statement's stylesheet, served by the addon so there is nothing to
 * publish or rebuild. A route rather than a <style> element in the tag's
 * output, because a <style> element is not allowed in the body of a page
 * and a <link rel="stylesheet"> is: the statement page should validate.
 */
final class StatementStylesheetController
{
    public const PATH = __DIR__.'/../../../resources/css/statement.css';

    public function __invoke(Request $request): Response
    {
        $css = (string) file_get_contents(self::PATH);

        $response = new Response($css, 200, ['Content-Type' => 'text/css; charset=UTF-8']);
        $response->setPublic()->setMaxAge(86400)->setEtag(self::version());
        $response->isNotModified($request);

        return $response;
    }

    /** Changes when the file does, for the link's query string and the ETag. */
    public static function version(): string
    {
        return substr((string) md5_file(self::PATH), 0, 8);
    }
}
