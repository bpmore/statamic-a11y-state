<?php

declare(strict_types=1);

/*
 * Grange keeps a site's accessibility statement and the evidence behind it.
 * Nothing in this file is legal advice. The profiles below say which
 * disclosures a regime expects a statement to contain and where that
 * expectation is written down; they do not say what the law means, and
 * this addon cannot tell you whether a site meets it.
 */

return [

    /*
    |---------------------------------------------------------------------------
    | The statement's page
    |---------------------------------------------------------------------------
    |
    | Every site gets its statement at 'route'. Not '/accessibility', which
    | Accessibility Report already serves when it is installed; the two can
    | run side by side and a site with both picks one to link to. Set to
    | null for no page, and use the `{{ grange:statement }}` tag inside a
    | page of your own instead.
    |
    | 'view' and 'layout' work as they do in Accessibility Report: null means
    | this addon's own one-line template inside the site's system layout, or
    | inside a plain shell of its own when the site has no such layout.
    |
    */

    'route' => '/accessibility-statement',
    'view' => null,
    'layout' => null,

    /*
    |---------------------------------------------------------------------------
    | Storage
    |---------------------------------------------------------------------------
    |
    | Both paths are relative to the project root and default to the content
    | directory, so the statement and its evidence live in the repository
    | beside the content they describe and their history is git's.
    |
    | The evidence log is one YAML file per record, written once and never
    | rewritten. There is no setting that changes that.
    |
    */

    'storage' => [
        'statements' => 'content/grange/statements',
        'evidence' => 'content/grange/evidence',
    ],

    /*
    |---------------------------------------------------------------------------
    | What a statement may claim
    |---------------------------------------------------------------------------
    |
    | 'overclaim_severity': an open exception at or above this severity means
    | "fully conformant" is refused, and the refusal names the exception.
    | 'minor' is every open exception, which is what the W3C definition of
    | fully conformant means ("without any exceptions"). Raising it lets a
    | site call itself fully conformant with minor exceptions open; that is
    | the site's decision and the statement will still list them.
    |
    | 'stale_after_days': an assessment older than this is flagged in the
    | control panel and by `grange:check`. The published statement is not
    | changed. 365 follows the EU model statement's recommendation that the
    | claims in a statement are reviewed "at least once per year".
    |
    */

    'overclaim_severity' => 'minor',
    'stale_after_days' => 365,

    /*
    |---------------------------------------------------------------------------
    | Feedback
    |---------------------------------------------------------------------------
    |
    | 'form' is the handle of the Statamic form that receives accessibility
    | feedback. Acknowledging a submission writes an evidence record, which
    | is what stops its clock. 'response_days' is how long a submission may
    | wait for an acknowledgment before the report calls it overdue, and is
    | the only response time the statement will ever mention. Null means
    | none is configured: nothing is overdue and the statement says nothing
    | about response times, because a promise nobody made is not a promise.
    |
    */

    'feedback' => [
        'form' => null,
        'response_days' => null,
    ],

    /*
    |---------------------------------------------------------------------------
    | Profiles
    |---------------------------------------------------------------------------
    |
    | Which regime the statement is written under, and what each expects.
    | 'profile' is the default for a new statement; each site's statement
    | records its own.
    |
    | Each profile lists disclosures by key. 'required' is true, false, or
    | 'unless-fully-conformant'. 'source' is where the expectation is
    | written down, and 'inferred' marks a requirement the regime's own text
    | does not state and this addon has taken from the W3C model instead.
    | docs/profiles.md walks through every one. Add a disclosure of your own
    | with a key, a label and a source, and the editor, the completeness
    | check and the document all pick it up.
    |
    | 'conformance_status' and 'exceptions' are structured fields on the
    | statement rather than text, and are listed here so a profile can say
    | whether it requires them.
    |
    */

    'profile' => 'w3c',

    'profiles' => [

        'w3c' => [
            'name' => 'Generic (W3C)',
            'title' => 'Accessibility statement',
            'description' => 'The W3C\'s model accessibility statement. The fallback for a site under no particular regime, and the base the other profiles inherit their optional content from.',
            'standard_default' => 'WCAG 2.2 Level AA',
            'disclosures' => [
                'organization' => ['label' => 'Organisation name', 'type' => 'text', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information'],
                'commitment' => ['label' => 'Commitment to accessibility', 'required' => true, 'source' => 'W3C WAI, Developing an Accessibility Statement, Minimal content: "A commitment to accessibility for people with disabilities"'],
                'scope' => ['label' => 'Scope of the statement', 'required' => false, 'hint' => 'Which websites, URLs or content types the statement covers.', 'source' => 'W3C Accessibility Statement Generator, Basic information: "Address of your website or mobile application"'],
                'standard' => ['label' => 'Standard, version and level', 'type' => 'text', 'required' => true, 'source' => 'W3C WAI, Developing an Accessibility Statement, Minimal content: "The accessibility standard applied, such as WCAG 2.2"'],
                'conformance_status' => ['label' => 'Conformance status', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information: "Conformance status"'],
                'exceptions' => ['label' => 'Known exceptions', 'required' => false, 'source' => 'W3C WAI, Developing an Accessibility Statement, Optional content: "Any known limitations, to avoid frustration of your users"'],
                'feedback_mechanism' => ['label' => 'How to send feedback', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information: contact options and "Typical duration for response"'],
                'contact' => ['label' => 'Contact details', 'required' => true, 'source' => 'W3C WAI, Developing an Accessibility Statement, Minimal content: "Contact information in case users encounter problems"'],
                'evaluation_method' => ['label' => 'How the site was evaluated', 'required' => false, 'hint' => 'Self-evaluation, external evaluation, or both, and what was done.', 'source' => 'W3C Accessibility Statement Generator, Technical information: "Assessment approach"'],
                'assessment_date' => ['label' => 'Date of the most recent assessment', 'type' => 'date', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information: evaluation report and statement links (the date is this addon\'s, for the staleness check)', 'inferred' => true],
                'preparation_date' => ['label' => 'Date this statement was prepared', 'type' => 'date', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information: "Publication date of this accessibility statement"'],
                'review_date' => ['label' => 'Date this statement was last reviewed', 'type' => 'date', 'required' => false, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, endnote xiii (reviewed "at least once per year"); not in the W3C model', 'inferred' => true],
                'evaluation_report' => ['label' => 'Link to the evaluation report', 'type' => 'url', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information: "Link to evaluation report"'],
            ],
        ],

        'en301549' => [
            'name' => 'EN 301 549 (EU)',
            'title' => 'Accessibility statement',
            'description' => 'Directive (EU) 2016/2102 for public sector bodies, using the Commission\'s model statement, with an optional disclosure for services under the European Accessibility Act.',
            'standard_default' => 'EN 301 549 v3.2.1 (WCAG 2.1 Level AA)',
            'disclosures' => [
                'organization' => ['label' => 'Name of the public sector body', 'type' => 'text', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1: "[Name of public sector body] is committed to making its website(s) accessible"'],
                'commitment' => ['label' => 'Commitment, and the national legislation transposing the Directive', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1: opening sentence, "in accordance with [national legislation transposing Directive (EU) 2016/2102]"'],
                'scope' => ['label' => 'Scope of the statement', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1: "This accessibility statement applies to [insert scope of statement]"'],
                'standard' => ['label' => 'Standard or technical specification, and national legislation', 'type' => 'text', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, endnote iv: "Insert reference to standards and/or technical specifications; or reference to national legislation transposing the Directive"'],
                'conformance_status' => ['label' => 'Compliance status', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1, "Compliance status": fully compliant, partially compliant, not compliant'],
                'exceptions' => ['label' => 'Non-accessible content', 'required' => 'unless-fully-conformant', 'source' => 'Directive (EU) 2016/2102, Article 7(1)(a); Commission Implementing Decision (EU) 2018/1523, Annex, Section 1, "Non-accessible content" (endnote x: "Can be deleted if not applicable")'],
                'preparation_date' => ['label' => 'Date this statement was prepared', 'type' => 'date', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1: "This statement was prepared on [date]"'],
                'evaluation_method' => ['label' => 'Method used to prepare the statement', 'required' => true, 'hint' => 'A self-assessment by the body, an assessment by a third party, or another measure the Member State accepts.', 'source' => 'Commission Implementing Decision (EU) 2018/1523, Article 3(2): "The statement shall indicate the method used"'],
                'review_date' => ['label' => 'Date this statement was last reviewed', 'type' => 'date', 'required' => false, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1: "[The statement was last reviewed on [insert date of latest review]]" (bracketed, endnote xiii)'],
                'assessment_date' => ['label' => 'Date of the most recent evaluation', 'type' => 'date', 'required' => false, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, endnote xii: the preparation date follows "an evaluation of the websites"; the evaluation\'s own date is this addon\'s, for the staleness check', 'inferred' => true],
                'feedback_mechanism' => ['label' => 'Feedback mechanism: description and link', 'required' => true, 'source' => 'Directive (EU) 2016/2102, Article 7(1)(b); Commission Implementing Decision (EU) 2018/1523, Annex, Section 1, "Feedback and contact information"'],
                'contact' => ['label' => 'Contact for accessibility and for feedback', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1: "the contact information of the relevant entity(ies)/unit(s)/person(s) responsible for accessibility"'],
                'enforcement_procedure' => ['label' => 'Enforcement procedure: description and link', 'required' => true, 'source' => 'Directive (EU) 2016/2102, Article 7(1)(c); Commission Implementing Decision (EU) 2018/1523, Annex, Section 1, "Enforcement procedure"'],
                'enforcement_body' => ['label' => 'Contact information of the enforcement body', 'required' => true, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 1: "Provide contact information of the relevant enforcement body"'],
                'evaluation_report' => ['label' => 'Link to the evaluation report', 'type' => 'url', 'required' => false, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, Section 2, optional content (5)'],
                'eaa_service_information' => ['label' => 'How the service meets the European Accessibility Act', 'required' => false, 'hint' => 'For services under Directive (EU) 2019/882: the information Annex V asks for, or where in the terms and conditions it is.', 'source' => 'Directive (EU) 2019/882, Article 13(2) and Annex V'],
            ],
        ],

        'section508' => [
            'name' => 'Section 508 (US federal)',
            'title' => 'Digital accessibility statement',
            'description' => 'The digital accessibility statement OMB M-24-08 requires of federal agencies, which Section508.gov recommends to recipients.',
            'standard_default' => 'Section 508 (36 CFR 1194), WCAG 2.0 Level AA',
            'disclosures' => [
                'organization' => ['label' => 'Agency or organisation name', 'type' => 'text', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'commitment' => ['label' => 'Commitment to accessibility', 'required' => false, 'source' => 'W3C WAI, Developing an Accessibility Statement, Minimal content; not listed by M-24-08', 'inferred' => true],
                'scope' => ['label' => 'Scope of the statement', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information; not listed by M-24-08', 'inferred' => true],
                'standard' => ['label' => 'Accessibility standard applied', 'type' => 'text', 'required' => true, 'source' => 'OMB M-24-08, "Agencies shall maintain a digital accessibility statement": "The accessibility standard applied to the website"'],
                'conformance_status' => ['label' => 'Conformance status', 'required' => false, 'source' => 'W3C Accessibility Statement Generator; not listed by M-24-08', 'inferred' => true],
                'exceptions' => ['label' => 'Known limitations or alternative versions', 'required' => false, 'source' => 'OMB M-24-08: "any known limitations or alternative versions, as appropriate"'],
                'contact' => ['label' => 'Section 508 program manager: name and email address', 'required' => true, 'source' => 'OMB M-24-08: "The contact information for the Section 508 program manager (name and email address)"'],
                'feedback_mechanism' => ['label' => 'Public feedback mechanism', 'required' => true, 'source' => 'OMB M-24-08: "A public feedback mechanism that allows members of the public to report accessibility problems"'],
                'complaint_instructions' => ['label' => 'How to file a Section 508 complaint', 'required' => true, 'source' => 'OMB M-24-08: "Instructions for filing a complaint alleging a violation of Section 508"'],
                'reasonable_accommodations' => ['label' => 'Reasonable accommodations procedures (Section 501)', 'required' => true, 'source' => 'OMB M-24-08: "Information about the agency\'s reasonable accommodations procedures for Federal employees and job applicants, consistent with Section 501"'],
                'relay_service' => ['label' => 'Telecommunications relay service instructions', 'required' => true, 'source' => 'OMB M-24-08: "Instructions on the use of the telecommunications relay service"'],
                'policy_links' => ['label' => 'Links to digital accessibility policies', 'required' => false, 'hint' => 'Links to any relevant, publicly available organisational policies or procedures on digital accessibility.', 'source' => 'OMB M-24-08: "Links to any relevant, publicly available organizational policies or procedures on digital accessibility" ("any" admits none, so not required here)'],
                'review_date' => ['label' => 'Date last updated or reviewed', 'type' => 'date', 'required' => true, 'source' => 'OMB M-24-08: "Date that the digital accessibility statement was last updated or reviewed"'],
                'evaluation_method' => ['label' => 'How the site was evaluated', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information; not listed by M-24-08', 'inferred' => true],
                'assessment_date' => ['label' => 'Date of the most recent assessment', 'type' => 'date', 'required' => false, 'source' => 'This addon\'s, for the staleness check; not listed by M-24-08', 'inferred' => true],
                'preparation_date' => ['label' => 'Date this statement was prepared', 'type' => 'date', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information; not listed by M-24-08', 'inferred' => true],
                'evaluation_report' => ['label' => 'Link to the evaluation report', 'type' => 'url', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information', 'inferred' => true],
            ],
        ],

        'ada_title_ii' => [
            'name' => 'ADA Title II (US state and local government)',
            'title' => 'Accessibility statement',
            'description' => '28 CFR Part 35 Subpart H sets the technical standard and does not itself require a statement. The disclosures here are the W3C model\'s, with the standard the rule names.',
            'standard_default' => 'WCAG 2.1 Level AA',
            'disclosures' => [
                'organization' => ['label' => 'Public entity name', 'type' => 'text', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'commitment' => ['label' => 'Commitment to accessibility', 'required' => true, 'source' => 'W3C WAI, Developing an Accessibility Statement, Minimal content; 28 CFR 35 Subpart H does not require a statement', 'inferred' => true],
                'scope' => ['label' => 'Scope: which web content and mobile apps', 'required' => false, 'source' => '28 CFR 35.200(b) applies to "web content and mobile apps that a public entity provides or makes available"; W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'standard' => ['label' => 'Standard, version and level', 'type' => 'text', 'required' => true, 'source' => '28 CFR 35.200(b): "Level A and Level AA success criteria and conformance requirements specified in WCAG 2.1"; that the statement names it is inferred from the W3C minimal content', 'inferred' => true],
                'conformance_status' => ['label' => 'Conformance status', 'required' => true, 'source' => 'W3C Accessibility Statement Generator, Basic information; 28 CFR 35 Subpart H does not require a statement', 'inferred' => true],
                'exceptions' => ['label' => 'Known exceptions', 'required' => 'unless-fully-conformant', 'hint' => 'Content that does not conform. The rule\'s own exceptions (28 CFR 35.201) and its minimal-impact provision (35.205) are legal questions; record the content here and take the legal question to counsel.', 'source' => 'W3C WAI, Developing an Accessibility Statement, Optional content: "Any known limitations"', 'inferred' => true],
                'feedback_mechanism' => ['label' => 'How to report an accessibility problem', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'contact' => ['label' => 'Contact details', 'required' => true, 'source' => 'W3C WAI, Developing an Accessibility Statement, Minimal content: "Contact information in case users encounter problems"', 'inferred' => true],
                'evaluation_method' => ['label' => 'How the site was evaluated', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information', 'inferred' => true],
                'assessment_date' => ['label' => 'Date of the most recent assessment', 'type' => 'date', 'required' => false, 'source' => 'This addon\'s, for the staleness check', 'inferred' => true],
                'preparation_date' => ['label' => 'Date this statement was prepared', 'type' => 'date', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'review_date' => ['label' => 'Date this statement was last reviewed', 'type' => 'date', 'required' => false, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, endnote xiii; not in any US source', 'inferred' => true],
                'evaluation_report' => ['label' => 'Link to the evaluation report', 'type' => 'url', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information', 'inferred' => true],
            ],
        ],

        'aoda' => [
            'name' => 'AODA (Ontario)',
            'title' => 'Accessibility statement',
            'description' => 'Ontario Regulation 191/11 under the Accessibility for Ontarians with Disabilities Act, 2005. The regulation requires a statement of organisational commitment, a public notice about accessible formats, and WCAG 2.0 conformance; it does not require a statement document as such.',
            'standard_default' => 'WCAG 2.0 Level AA',
            'disclosures' => [
                'organization' => ['label' => 'Organisation name', 'type' => 'text', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'commitment' => ['label' => 'Statement of organisational commitment', 'required' => true, 'hint' => 'Required of every obligated organisation other than a small organisation (O. Reg. 191/11, s. 3(2)).', 'source' => 'O. Reg. 191/11, s. 3(2): "a statement of organizational commitment to meet the accessibility needs of persons with disabilities in a timely manner"'],
                'scope' => ['label' => 'Scope of the statement', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'standard' => ['label' => 'Standard, version and level', 'type' => 'text', 'required' => true, 'hint' => 'WCAG 2.0 Level AA, other than success criteria 1.2.4 and 1.2.5, for designated public sector and large organisations (s. 14(4)).', 'source' => 'O. Reg. 191/11, s. 14(2) and 14(4): WCAG 2.0 Level AA; that the statement names it is inferred from the W3C minimal content', 'inferred' => true],
                'conformance_status' => ['label' => 'Conformance status', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information; not in O. Reg. 191/11', 'inferred' => true],
                'exceptions' => ['label' => 'Known exceptions', 'required' => false, 'source' => 'W3C WAI, Developing an Accessibility Statement, Optional content; O. Reg. 191/11, s. 14(4) excludes success criteria 1.2.4 and 1.2.5', 'inferred' => true],
                'accessible_formats_notice' => ['label' => 'Notice: accessible formats and communication supports are available on request', 'required' => true, 'source' => 'O. Reg. 191/11, s. 12(3): "Every obligated organization shall notify the public about the availability of accessible formats and communication supports"; s. 11(2) says the same of the feedback process'],
                'feedback_mechanism' => ['label' => 'How to send feedback', 'required' => false, 'hint' => 'Where the organisation has a feedback process, it must be accessible and the public told that accessible formats are available for it (s. 11).', 'source' => 'O. Reg. 191/11, s. 11(1) and 11(2), which apply to an organisation "that has processes for receiving and responding to feedback"'],
                'contact' => ['label' => 'Contact details', 'required' => true, 'source' => 'W3C WAI, Developing an Accessibility Statement, Minimal content; not in O. Reg. 191/11', 'inferred' => true],
                'evaluation_method' => ['label' => 'How the site was evaluated', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information', 'inferred' => true],
                'assessment_date' => ['label' => 'Date of the most recent assessment', 'type' => 'date', 'required' => false, 'source' => 'This addon\'s, for the staleness check', 'inferred' => true],
                'preparation_date' => ['label' => 'Date this statement was prepared', 'type' => 'date', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Basic information', 'inferred' => true],
                'review_date' => ['label' => 'Date this statement was last reviewed', 'type' => 'date', 'required' => false, 'source' => 'Commission Implementing Decision (EU) 2018/1523, Annex, endnote xiii; not in O. Reg. 191/11', 'inferred' => true],
                'evaluation_report' => ['label' => 'Link to the evaluation report', 'type' => 'url', 'required' => false, 'source' => 'W3C Accessibility Statement Generator, Technical information', 'inferred' => true],
            ],
        ],

    ],

];
