# Profiles: where each disclosure comes from

Every disclosure in every profile in `config/statamic-grange.php` is traced
here to the text that asks for it. Where a regime's own text does not ask
for a disclosure, the field is marked **inferred** and the source is the
W3C model it was taken from instead. Nothing in this file is legal advice,
and nothing in the profiles says what the law means: they encode what a
statement under a regime is expected to contain, and where that
expectation is written down.

Sources were read on 15 September 2026. Primary sources were read from the
publisher's own site; where a page could not be fetched without a browser,
the method is noted.

## Sources

| Key | Source | Read from |
|---|---|---|
| W3C-min | W3C WAI, *Developing an Accessibility Statement*, "Minimal content" and optional items | https://www.w3.org/WAI/planning/statements/ |
| W3C-gen | W3C Accessibility Statement Generator, every field marked "not required" | https://www.w3.org/WAI/planning/statements/generator/ |
| WAD | Directive (EU) 2016/2102, Article 7(1) | https://eur-lex.europa.eu/legal-content/EN/TXT/HTML/?uri=CELEX:32016L2102 |
| MAS | Commission Implementing Decision (EU) 2018/1523, Articles 3 and 4, Annex (model accessibility statement) | https://eur-lex.europa.eu/legal-content/EN/TXT/HTML/?uri=CELEX:32018D1523 |
| EAA | Directive (EU) 2019/882, Article 13(2) and Annex V | https://eur-lex.europa.eu/legal-content/EN/TXT/HTML/?uri=CELEX:32019L0882 |
| M-24-08 | OMB Memorandum M-24-08 (21 December 2023), the bullet beginning "Agencies shall maintain a digital accessibility statement on Federal websites" | https://www.whitehouse.gov/wp-content/uploads/2023/12/M-24-08-Strengthening-Digital-Accessibility-and-the-Management-of-Section-508-of-the-Rehabilitation-Act.pdf (PDF, text extracted with pdftotext) |
| 508.gov | Section508.gov, *Developing a Website Accessibility Statement*, which restates M-24-08's list | https://www.section508.gov/manage/laws-and-policies/website-accessibility-statement/ |
| T2 | 28 CFR Part 35, Subpart H, sections 35.200 to 35.205 | https://www.law.cornell.edu/cfr/text/28/35.200 (eCFR redirected to a bot check) |
| T2-IFR | Interim Final Rule extending the Subpart H compliance dates, 91 FR 20902, published 20 April 2026 | https://www.federalregister.gov/documents/2026/04/20/2026-07663/ (metadata read from the Federal Register API) |
| AODA | Ontario Regulation 191/11, Integrated Accessibility Standards, sections 3, 11, 12 and 14, consolidation current to 10 September 2026 | https://www.ontario.ca/laws/regulation/110191 (e-Laws needs JavaScript; read through a headless browser) |

## What each source says, in its own words

**W3C-min.** A statement should contain at least "A commitment to
accessibility for people with disabilities", "The accessibility standard
applied, such as WCAG 2.2", and "Contact information in case users encounter
problems". It is "also advisable" to include known limitations, measures
taken, technical prerequisites, environments tested, and references to
applicable laws.

**W3C-gen.** Four conformance statuses: "Fully conformant: the content fully
conforms to the accessibility standard without any exceptions", "Partially
conformant: some parts of the content do not fully conform", "Non conformant:
the content does not conform", and "Not assessed: the content has not been
evaluated or the evaluation results are not available". Two assessment
approaches: self-evaluation and external evaluation. Every field is marked
not required.

**WAD, Article 7(1).** The statement "shall include": (a) "an explanation
concerning those parts of the content that are not accessible, and the
reasons for that inaccessibility and, where appropriate, the accessible
alternatives provided for"; (b) "a description of, and a link to, a feedback
mechanism"; (c) "a link to the enforcement procedure".

**MAS.** Article 3(2): "The statement shall indicate the method used", which
Article 3(1) gives as an actual evaluation (self-assessment or third party)
or another measure the Member State accepts. Article 4(1): bodies provide
"at least the mandatory content requirements set out in Section 1 of the
Annex". Section 1 is: the opening sentence naming the body and its
commitment "in accordance with [national legislation transposing Directive
(EU) 2016/2102]"; the scope; the compliance status as one of fully
compliant, partially compliant, not compliant; the non-accessible content
(endnote x: "Can be deleted if not applicable"); "This statement was
prepared on [date]"; the method; "[The statement was last reviewed on
[insert date of latest review]]" (bracketed, endnote xiii: reviewed "at least
once per year"); feedback and contact information; enforcement procedure
and the enforcement body's contact. Section 2 lists optional content,
including a link to an evaluation report.

**EAA.** Article 13(2): service providers "shall prepare the necessary
information in accordance with Annex V and shall explain how the services
meet the applicable accessibility requirements", made public "in written and
oral format". Annex V puts that information "in the general terms and
conditions, or equivalent document". The EAA does not use the term
"accessibility statement".

**M-24-08.** "Agencies shall maintain a digital accessibility statement on
Federal websites that provides or links to the following information": the
standard applied "and any known limitations or alternative versions, as
appropriate"; "the contact information for the Section 508 program manager
(name and email address)"; "a public feedback mechanism"; "instructions for
filing a complaint alleging a violation of Section 508"; "information about
the agency's reasonable accommodations procedures for Federal employees and
job applicants, consistent with Section 501"; "instructions on the use of the
telecommunications relay service"; "links to any relevant, publicly available
organizational policies or procedures on digital accessibility"; and "date
that the digital accessibility statement was last updated or reviewed".

**T2.** 35.200(b): web content and mobile apps "comply with Level A and Level
AA success criteria and conformance requirements specified in WCAG 2.1".
35.201 lists exceptions, 35.202 conforming alternate versions, 35.203
equivalent facilitation, 35.204 duties, 35.205 the effect of noncompliance
with minimal impact. **No section requires a statement, a feedback mechanism
or a contact.**

**T2-IFR.** The compliance dates in 35.200(b) were extended on 20 April 2026:
for entities with a population of 50,000 or more, from 24 April 2026 to
**26 April 2027**; for entities under 50,000 and special district
governments, from 26 April 2027 to **26 April 2028**. The build brief's dates
(24 April 2026 and 26 April 2027) were the dates before this extension.
Nothing in the addon prints a compliance date.

**AODA.** s. 3(2): organisations other than small organisations "shall
include a statement of organizational commitment to meet the accessibility
needs of persons with disabilities in a timely manner in their policies".
s. 3(3): those organisations prepare documents describing the policies and
"make the documents publicly available". s. 11(1): an organisation "that has
processes for receiving and responding to feedback shall ensure that the
processes are accessible" by providing accessible formats and communication
supports on request; s. 11(2): "The organization shall notify the public
about the availability of accessible formats and communications supports
with respect to the feedback process". s. 12(3): "Every obligated organization
shall notify the public about the availability of accessible formats and
communication supports". s. 14(2) and (4): designated public sector and large
organisations conform to WCAG 2.0 Level AA, other than success criteria 1.2.4
and 1.2.5. **The regulation does not require an accessibility statement as a
document.**

## Disclosure by profile

R = required, O = optional, R* = required unless fully conformant. An
asterisk after the source marks a field the regime's text does not ask for
(inferred from the W3C model).

### w3c (Generic W3C)

| Disclosure | Required | Source |
|---|---|---|
| organization | O | W3C-gen, "Name of your organization" |
| commitment | R | W3C-min |
| scope | O | W3C-gen, "Address of your website" |
| standard | R | W3C-min |
| conformance_status | O | W3C-gen |
| exceptions | O | W3C-min, "Any known limitations" |
| feedback_mechanism | O | W3C-gen, contact options and "Typical duration for response" |
| contact | R | W3C-min |
| evaluation_method | O | W3C-gen, "Assessment approach" |
| assessment_date | O | This addon's, for the staleness check * |
| preparation_date | O | W3C-gen, "Publication date of this accessibility statement" |
| review_date | O | MAS endnote xiii; not in the W3C model * |
| evaluation_report | O | W3C-gen, "Link to evaluation report" |

### en301549 (EU)

| Disclosure | Required | Source |
|---|---|---|
| organization | R | MAS Annex s. 1, opening sentence |
| commitment | R | MAS Annex s. 1, opening sentence |
| scope | R | MAS Annex s. 1, "This accessibility statement applies to" |
| standard | R | MAS Annex endnote iv |
| conformance_status | R | MAS Annex s. 1, "Compliance status" |
| exceptions | R* | WAD Art. 7(1)(a); MAS Annex s. 1, "Non-accessible content", endnote x |
| preparation_date | R | MAS Annex s. 1, "This statement was prepared on" |
| evaluation_method | R | MAS Art. 3(2) |
| review_date | O | MAS Annex s. 1, bracketed sentence, endnote xiii |
| assessment_date | O | MAS endnote xii names an evaluation; its date is this addon's * |
| feedback_mechanism | R | WAD Art. 7(1)(b); MAS Annex s. 1 |
| contact | R | MAS Annex s. 1, "Feedback and contact information" |
| enforcement_procedure | R | WAD Art. 7(1)(c); MAS Annex s. 1 |
| enforcement_body | R | MAS Annex s. 1, "Enforcement procedure" |
| evaluation_report | O | MAS Annex s. 2 (5) |
| eaa_service_information | O | EAA Art. 13(2), Annex V |

The EU model has three compliance statuses and no "not assessed". A
statement under this profile that is still "not assessed" is incomplete in
the sense that it does not yet make the claim the model asks for; the
profile marks `conformance_status` required, and the editor treats "not
assessed" as an answer to that field. **Open question for the owner:**
whether the EU profile should refuse to generate while the status is "not
assessed". Not decided here.

### section508 (US federal)

| Disclosure | Required | Source |
|---|---|---|
| organization | O | W3C-gen * |
| commitment | O | W3C-min * |
| scope | O | W3C-gen * |
| standard | R | M-24-08, "The accessibility standard applied to the website" |
| conformance_status | O | W3C-gen * |
| exceptions | O | M-24-08, "any known limitations or alternative versions, as appropriate" |
| contact | R | M-24-08, Section 508 program manager, name and email |
| feedback_mechanism | R | M-24-08, "A public feedback mechanism" |
| complaint_instructions | R | M-24-08, "Instructions for filing a complaint" |
| reasonable_accommodations | R | M-24-08, Section 501 procedures |
| relay_service | R | M-24-08, "Instructions on the use of the telecommunications relay service" |
| policy_links | O | M-24-08, "Links to any relevant, publicly available" policies ("any" admits none) |
| review_date | R | M-24-08, "Date that the digital accessibility statement was last updated or reviewed" |
| evaluation_method | O | W3C-gen * |
| assessment_date | O | This addon's * |
| preparation_date | O | W3C-gen * |
| evaluation_report | O | W3C-gen * |

M-24-08 binds federal agencies. Section508.gov recommends the same list to
anyone else. The profile's `standard_default` names Section 508 (36 CFR
1194) and WCAG 2.0 Level AA, which is what the 2017 Section 508 refresh
incorporates; the refresh itself was not re-read for this file and the
default is the site's to change.

### ada_title_ii (US state and local government)

Every disclosure in this profile is inferred. 28 CFR 35 Subpart H sets the
technical standard and does not require a statement. The profile exists so
that a public entity writing one anyway has the standard the rule names as
its default and the W3C's minimal content as its requirements.

| Disclosure | Required | Source |
|---|---|---|
| organization | O | W3C-gen * |
| commitment | R | W3C-min * |
| scope | O | T2 35.200(b) names "web content and mobile apps"; W3C-gen * |
| standard | R | T2 35.200(b), WCAG 2.1 Level A and AA; that the statement names it is W3C-min * |
| conformance_status | R | W3C-gen * |
| exceptions | R* | W3C-min, "Any known limitations" * |
| feedback_mechanism | O | W3C-gen * |
| contact | R | W3C-min * |
| evaluation_method | O | W3C-gen * |
| assessment_date | O | This addon's * |
| preparation_date | O | W3C-gen * |
| review_date | O | MAS endnote xiii * |
| evaluation_report | O | W3C-gen * |

The rule's own exceptions (35.201) and its minimal-impact provision (35.205)
are legal questions about whether content is covered, not disclosures. The
`exceptions` hint in the config file says so and sends the reader to
counsel.

**Open question for the owner:** whether `commitment`, `conformance_status`
and `contact` should be required under a profile whose regime requires
none of them. They are required here because a statement without them is
not one by the W3C's minimum, and the brief's common disclosure set
includes them. A site can set them optional in its published config.

### aoda (Ontario)

| Disclosure | Required | Source |
|---|---|---|
| organization | O | W3C-gen * |
| commitment | R | AODA s. 3(2) (organisations other than small organisations) |
| scope | O | W3C-gen * |
| standard | R | AODA s. 14(2), 14(4): WCAG 2.0 Level AA; that the statement names it is W3C-min * |
| conformance_status | O | W3C-gen * |
| exceptions | O | W3C-min; AODA s. 14(4) excludes 1.2.4 and 1.2.5 * |
| accessible_formats_notice | R | AODA s. 12(3); s. 11(2) for the feedback process |
| feedback_mechanism | O | AODA s. 11(1), conditional on having a feedback process |
| contact | R | W3C-min * |
| evaluation_method | O | W3C-gen * |
| assessment_date | O | This addon's * |
| preparation_date | O | W3C-gen * |
| review_date | O | MAS endnote xiii * |
| evaluation_report | O | W3C-gen * |

**Open question for the owner:** s. 3(2) exempts small organisations from
the commitment statement, and s. 14 applies WCAG to designated public
sector and large organisations. The profile requires `commitment` of
everyone. A small organisation under AODA can set it optional in its
published config; whether the addon should offer an "aoda_small" profile is
not decided here.

## Two things this file decides that the brief did not

1. **Four conformance statuses, not three.** The brief names three. The
   W3C generator, which the brief says the model follows, offers "not
   assessed", and it is the only honest value for a statement whose site
   has not been evaluated yet. Every new statement starts there. Confirmed
   by the owner on 15 September 2026. The EU model has no such value; see
   the note under `en301549`.
2. **The staleness default is 365 days**, from MAS endnote xiii: claims
   should be reviewed "at least once per year". No other source names a
   period.
