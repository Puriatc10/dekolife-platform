# Contract Source — Provenance and Extraction Record

| | |
|---|---|
| **Purpose** | Record exactly which document is the source of truth, what was extracted from it, how to reproduce the extraction, and which parts of the file are unreliable. |
| **Audience** | Every agent or engineer who quotes the contract; reviewers auditing a requirement's traceability. |
| **Status** | Baseline v1.0 — verified against page renders on 2026-11-25. |
| **Source** | `input/contract/1-قرارداد-دکولایف-۹۰م-ویرایش.pdf` (11 pages), read in full; cross-checked against page renders `p01.png`–`p11.png`. |

## 1. The artifact

| Property | Value |
|---|---|
| File name (in repo) | `input/contract/1-قرارداد-دکولایف-۹۰م-ویرایش.pdf` |
| Size | 475,560 bytes (464 KB) |
| Pages | 11 (footer reads «صفحه N از ۱۱» on every page) |
| SHA-256 | `9685608e40947d5a9a859a444419d5d87b4b4367dccd6ebebfb20c6ed1f288a2` |
| Contract number | `DK-1405-01` (printed as `-1405-01DK`, reversed by the extractor) |
| Title | «قرارداد طراحی و پیادهسازی وبسایت چندفروشندگی، سیستم بازاریابی، امتیاز و پاداش پلکانی همراه با اپلیکیشن موبایل» |
| Subject system | سامانه دکولایف (also spelled دکوالیف / دکالیف in the document — three spellings of the same product name) |
| Language | Persian; numerals printed in Persian/Arabic-Indic digits (`۹۰٬۰۰۰٬۰۰۰`) |
| Document status (page 1) | «پیشنویس نسخه ۱ — در انتظار توافق و امضای طرفین» — i.e. the file states it is an **unsigned draft v1** |
| Signature block (page 11) | Four dated boxes: employer, contractor, witness 1, witness 2 — **all dates blank** |

Parties as printed (page 1, and repeated in every page header):

- **کارفرما (client):** آقای داوودی — کد ملی and تلفن left blank.
- **مجری (contractor):** محسن فتحی — کد ملی and تلفن left blank.

Because the printed document says "draft, awaiting signature" and none of the signature dates are filled, the contract's *execution status* is itself an open question: see `AMB-CTR-007` in [`../ambiguities/ambiguity-register.md`](../ambiguities/ambiguity-register.md). All work in this repository proceeds on the owner's instruction that this text is the agreed scope.

**Integrity rule:** treat the SHA-256 above as the identity of the document. If the PDF is replaced, re-run §5 and update this file; do not edit the PDF in place.

## 2. What is extracted, and where

### 2.1 In-repo extraction (canonical, committed)

`docs/contract/contract-extracted-text.txt` — 493 lines, page-delimited by markers of the form:

```
===== صفحه 1 =====
```

It contains, in order: the cover + summary + material 1 (page 1), materials 2–4 (page 2), rest of material 4 through material 10 (page 3), materials 10–16 (page 4), and appendices 1–14 (pages 5–11). Every page of the source is present; nothing is summarised away.

### 2.2 Page-level working files (not committed)

Per-page clean text and page renders were produced during analysis and live outside the repository (scratch/temp), because they are reproducible artefacts, not sources:

- `page1.txt` … `page11.txt` — one file per page, no page markers. These are the most convenient files for precise clause reading; `page2_uniq.txt` is a de-duplicated view of page 2 (see §3.1).
- `pages/p01.png` … `pages/p11.png` — 1654×2339 px renders, one per page. **These are what the contract actually looks like.** When text and render disagree, the render wins.

Anything you cannot resolve from the text must be resolved from `pNN.png`.

### 2.3 Page map

| Page | Content | Raw extracted chars |
|---|---|---|
| 1 | Cover block (number, parties, total, duration, payment, status), «خلاصه وضعیت پروژه» summary table, ماده ۱ (subject + main deliverables) | 9,676 |
| 2 | End of ماده ۴ بند ۵–۷, ماده ۲ (price + payment table), ماده ۳ (duration + phase table), ماده ۴ بند ۱–۷ | 98,399 ⚠ |
| 3 | ماده ۴ بند ۸–۱۲, ماده ۵ (client obligations), ماده ۶ (disputes), ماده ۷ (IP), ماده ۸ (amendments), ماده ۹ (termination), ماده ۱۰ heading | 3,200 |
| 4 | ماده ۱۰ (force majeure), ماده ۱۱ (notices), ماده ۱۲ (warranty/support), ماده ۱۳ (non-assignment), ماده ۱۴ (copies), ماده ۱۵ (addendum mechanism + table), ماده ۱۶ (15 % profit share) | 7,845 |
| 5 | Appendices preamble, پیوست ۱ (architecture/tech/infrastructure, 10 clauses), پیوست ۲ (UX/UI pages, 12), پیوست ۳ heading + 3-1 (store, 10) | 2,763 |
| 6 | پیوست ۳ 3-1 (cont. 7–10), 3-2 بازاریاب (10), 3-3 مصرفکننده (6), 3-4 تأمینکننده, 3-5 ادمین, پیوست ۴ (points/rewards/discounts, clauses 1–7) | 2,389 |
| 7 | پیوست ۴ clauses 8–24, پیوست ۵ heading + clauses 1–11 | 3,526 |
| 8 | پیوست ۵ clauses 12–42 | 3,751 |
| 9 | پیوست ۵ clauses 43–44 + the reservation-module exclusion note, پیوست ۶ (marketer panel, 12), پیوست ۷ (admin panel, 10), پیوست ۸ (mobile app, 1–4) | 3,318 |
| 10 | پیوست ۸ clauses 5–8, پیوست ۹ (SEO/security/branding, 6), پیوست ۱۰ (delivery/test/training, 6), پیوست ۱۱ (supplier panel, 1–15) | 2,896 |
| 11 | پیوست ۱۱ clauses 16–20, پیوست ۱۲ (consumer plan, 16), پیوست ۱۳ (financial reporting/settlement/bank, 7), پیوست ۱۴ (marketer field capabilities, 2), signature blocks | 8,974 |

Totals: **16 مواد** (materials) + **203 numbered appendix clauses** across **14 پیوستها**, plus two unnumbered narrative sub-sections in پیوست ۳ and two table templates (payment plan; addendum register in ماده ۱۵).

## 3. Known defects of the PDF text layer — read this before quoting

The PDF was produced from a word processor and re-saved; its text layer is not a faithful copy of the printed page. Four independent defects exist. `pdfminer`/`pdftotext`/`PyPDF2` each surface a different subset, which is why different agents saw different numbers. **The clean text in this repo was produced with pdfium (`pypdfium2`) and then de-duplicated.**

### 3.1 Two overlapping text layers on pages 1–4 (critical)

Pages 1–4 carry two revisions of the same content stacked in the text layer. The renderer shows revision **B**; some extractors emit revision **A** first, or interleave both. Consequences:

- Page 2 extracts to 98,399 characters for a page that holds ~4,000 characters of visible text — roughly 20× duplication. `page2_uniq.txt` exists because of this.
- Some paragraphs appear twice with **different wording and different numbers**. The two cases that matter:

| Topic | Revision A (text layer only, does **not** render) | Revision B (what the page shows) |
|---|---|---|
| Payment plan (ماده ۲) | «پیشپرداخت گشایش کار ۱۰٬۰۰۰٬۰۰۰ همزمان با امضای قرارداد و پیش از شروع کار» + «تسویه نهایی ۸۰٬۰۰۰٬۰۰۰ یکجا، پس از گذشت یک ماه از تحویل نهایی و تأیید کتبی کارفرما» | one row: «پرداخت مرحلهای \| ۹۰٬۰۰۰٬۰۰۰ \| در طول اجرا و پس از تحویل نهایی و تأیید کتبی کارفرما» |
| Free support duration (ماده ۱۲ بند ۱) | «پشتیبانی و آموزش فنی رایگان از تاریخ تحویل نهایی **به مدت ۵ سال** …» | «پشتیبانی و آموزش فنی رایگان از تاریخ تحویل نهایی **تا پایان شرکت طرفین** …» |
| Guarantee row in the summary table (page 1) | «۵ سال» | «نامحدود (تا زمان شرکت)» |

Both variants are in the file. Neither can be called "the" contract without the owner's confirmation, so both are carried in the register: `AMB-CTR-005` (payment plan) and `AMB-CTR-006` (support duration). **Do not write either figure into code, invoices or client-facing text as settled fact.** The working assumption for planning is recorded in [`../ambiguities/agent-decisions.md`](../ambiguities/agent-decisions.md) (DEC-015, DEC-016).

### 3.2 Word-order reversal in some blocks

Several blocks (notably the ماده ۱۲/۱۶ body on page 4, the quote block on page 1, and پیوست ۱۴ on page 11) are stored with each word's characters reversed:

- `هناماس فیلاوکد` → «سامانه دکولایف»
- `ینابیتشپ` → «پشتیبانی»
- `تکارش` → «شراکت»
- `تسویپ ۱۴` → «پیوست ۱۴»

These are readable by reversing each word, never by reversing the line. When quoting such a block, reverse the characters of each word and cross-check the result against the page render — the reversal also silently mangles `شرکت` (company) into `تکارش` (partnership), which changes the legal meaning of ماده ۱۲ بند ۱.

### 3.3 Lost lam-alef ligatures and ZWNJ

`اطلاعات` extracts as `اطالعات`, `علاوه` as `عالوه`, `محصولات` as `محصوالت`, `اسلایدر` as `اسالیدر`, `سؤالات` as `سؤاالت`. These are ligature artefacts, not typos in the contract. In this documentation set such words are quoted in their **correct** form; no other character is altered.

### 3.4 Structural artefacts

- **Missing material numbers:** on page 4 the headings «ماده — نسخ قرارداد» and «ماده — جایگاه فیچرهای اضافه و الحاقیهها» have no number in the text layer. The render shows them as **ماده ۱۴** and **ماده ۱۵** (confirmed against `p04.png`), which is consistent with «این قرارداد در ۱۶ ماده … تنظیم شده».
- **Wrong page count in one layer:** page 4's footer extracts once as «صفحه ۵ از ۱۲». The render reads «صفحه ۴ از ۱۱». The document has 11 pages.
- **Decimal separator:** printed as `٫` (`۰٫۵ درصد`). In English prose this file set writes `0.5 %`.
- **Thousands separator:** printed as `٬` (`۹۰٬۰۰۰٬۰۰۰`).
- **Placeholder rows:** the addendum table in ماده ۱۵ and the payment/phase tables contain dash-filled template rows (`- ٫٫٫ ٫٫٫٫٫٫٫٫ …`). They are form scaffolding, not requirements.

## 4. How to read a citation in this documentation set

Every requirement-level statement cites `page N / پیوست M / بند K` (or `ماده N / بند K`). Conventions:

- `ماده` = numbered article of the contract body (1–16).
- `پیوست` = appendix (1–14). `بند` = its numbered clause.
- `۳-۱`…`۳-۵` = the five sub-sections of پیوست ۳ (one per user level).
- Where a clause number is not printed in the text layer, the render's numbering is used and the quote is marked as verified against the render (§5).
- Where a clause exists in two revisions (§3.1), the citation names both and points to the ambiguity ID.

## 5. How to re-extract

The procedure below was executed on 2026-11-25 and reproduced the committed text. It needs `uv` (present on this host) and no PHP.

**Step 1 — text, per page (pdfium).** Write the helper outside the repo (scratch dir), then run it:

```bash
cat > "$TMPDIR/pdfium_extract.py" <<'PY'
import sys, pathlib
import pypdfium2 as pdfium
pdf = pdfium.PdfDocument(sys.argv[1])
out = pathlib.Path(sys.argv[2]); out.mkdir(parents=True, exist_ok=True)
print("pages:", len(pdf))
for i in range(len(pdf)):
    txt = pdf[i].get_textpage().get_text_range()
    (out / f"page{i+1}.txt").write_text(txt, encoding="utf-8")
    print(i + 1, len(txt))
PY

cd "input/contract"
uv run --with pypdfium2 python "$TMPDIR/pdfium_extract.py" \
  "1-قرارداد-دکولایف-۹۰م-ویرایش.pdf" "$TMPDIR/re-extract"
```

Expected output: `pages: 11` and the char counts in §2.3. A page-2 count far above ~10,000 means you are seeing the duplicate layer of §3.1, not an error.

**Step 2 — page renders (for anything numeric or contested).**

```bash
cd "input/contract"
uv run --with pypdfium2 --with pillow python -c "
import pypdfium2 as pdfium, pathlib
pdf = pdfium.PdfDocument('1-قرارداد-دکولایف-۹۰م-ویرایش.pdf')
out = pathlib.Path('$TMPDIR/pages'); out.mkdir(parents=True, exist_ok=True)
for i in range(len(pdf)):
    pdf[i].render(scale=2).to_pil().save(out / f'p{i+1:02d}.png')
print(sorted(p.name for p in out.iterdir()))
"
```

**Step 3 — de-duplicate.** Keep line order, drop exact repeats, then read both variants of any paragraph that now appears twice with *different* digits. `sort -u` destroys reading order; de-duplicate in order instead (a 6-line Python pass over `splitlines()`).

**Step 4 — never trust a bare number.** Any figure that will appear in code, an invoice, a penalty calculation or a client message must be confirmed against `pNN.png`. Numbers already confirmed this way are listed in §6.

## 6. Verification log

Renders inspected and what was settled (all against `p01.png`–`p11.png`):

| Page | Question | Result |
|---|---|---|
| 1 | Summary-table rows, guarantee row, duration row | Guarantee = «نامحدود (تا زمان شرکت)»; duration = «۳ ماه (۹۰ روز تقویمی)»; payment = «۹۰٬۰۰۰٬۰۰۰ … مرحلهای» |
| 2 | Number of rows in the payment table | **One** data row (90,000,000 staged). No 10,000,000/80,000,000 row renders → `AMB-CTR-005` |
| 2 | Phase table | Month 1 = full system (web+app), month 2 = debugging, month 3 = heavy test + final delivery; all «در انتظار شروع» |
| 2 | Delay penalty | «۰٫۵ درصد» per day, cap «۲۰ درصد مبلغ قرارداد» — confirmed |
| 3 | Penalty for a missing appendix heading | «۵ درصد … سقف مجموع این جریمه ۱۰ درصد مبلغ قرارداد» — confirmed; clause is item 11 of ماده ۴ |
| 4 | Numbers of the two unnumbered headings | ماده ۱۴ (copies) and ماده ۱۵ (addendum table) |
| 4 | ماده ۱۲ بند ۱ wording | «تا پایان شرکت طرفین» renders; «به مدت ۵ سال» does not → `AMB-CTR-006` |
| 6 | پیوست ۴ coefficients and rules | ۰٫۷۵ / ۰٫۵ / ۰٫۲۵ / ۰٫۲ درصد; 4× reward threshold; half-reward fallback; inactivity deduction — confirmed |
| 9 | Reservation-module exclusion note | «مبلغ ۳۵٬۰۰۰٬۰۰۰ تومان، مدت ۱ ماه» under «قرارداد شماره ۲» — confirmed |
| 11 | Last appendix and signature block | پیوست ۱۴ exists (2 clauses: location capture, live tracking/route analysis); signatures follow it, undated |

## 7. Rules for anyone editing this documentation set

1. Quote Persian exactly as printed, after applying §3.2–§3.3 normalisation; mark the normalisation in the sentence if the wording is legally load-bearing.
2. Never invent a clause. If the contract is silent, the correct output is an ambiguity ID, not a plausible sentence.
3. Never cite a page you did not read. The 11 pages are short; read them.
4. If you find a new text/render divergence, add it to §3.1 and open an `AMB-CTR-*` row rather than choosing a version silently.
