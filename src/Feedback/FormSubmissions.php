<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Feedback;

use Bpmore\A11yState\Statement\Dates;
use Statamic\Facades\Form;

/**
 * The submissions of the configured Statamic form, as the clock needs
 * them. Reads only. The form is the site's own: this addon does not create
 * one, so the fields, the notifications and the spam handling stay where
 * the site already manages them.
 */
final class FormSubmissions
{
    public function __construct(private readonly ?string $formHandle) {}

    public function handle(): ?string
    {
        return is_string($this->formHandle) && $this->formHandle !== '' ? $this->formHandle : null;
    }

    public function isConfigured(): bool
    {
        return $this->handle() !== null;
    }

    public function formExists(): bool
    {
        return $this->handle() !== null && Form::find($this->handle()) !== null;
    }

    /**
     * @return list<Submission>
     */
    public function all(): array
    {
        $form = $this->handle() !== null ? Form::find($this->handle()) : null;

        if ($form === null) {
            return [];
        }

        $submissions = [];

        foreach ($form->submissions() as $submission) {
            $date = $submission->date();
            $data = $submission->data();
            $data = $data instanceof \Illuminate\Support\Collection ? $data->all() : (array) $data;

            $submissions[] = new Submission(
                id: (string) $submission->id(),
                at: Dates::moment(\DateTimeImmutable::createFromInterface($date)),
                summary: self::summary($data),
            );
        }

        return $submissions;
    }

    /**
     * One line to show beside the clock: the first text field with
     * something in it, cut short. Not the whole submission; the form's own
     * screen has that.
     *
     * @param  array<string, mixed>  $data
     */
    private static function summary(array $data): string
    {
        foreach ($data as $value) {
            if (is_string($value) && trim($value) !== '') {
                $text = trim(preg_replace('/\s+/', ' ', $value) ?? '');

                return mb_strlen($text) > 120 ? mb_substr($text, 0, 117).'...' : $text;
            }
        }

        return '';
    }
}
