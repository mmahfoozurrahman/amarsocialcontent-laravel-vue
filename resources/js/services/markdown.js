import { marked } from 'marked';
import DOMPurify from 'dompurify';

/**
 * Bengali numerals mapped to their ASCII counterparts. Markdown only treats
 * ASCII digits as ordered-list markers, so "১." / "২." must be converted
 * before parsing or every point collapses into a single paragraph.
 */
const BENGALI_DIGITS = {
    '০': '0',
    '১': '1',
    '২': '2',
    '৩': '3',
    '৪': '4',
    '৫': '5',
    '৬': '6',
    '৭': '7',
    '৮': '8',
    '৯': '9',
};

// Blocks that must never be rewritten (headings, code fences, tables, quotes).
const STRUCTURAL_LINE = /^\s*(?:#{1,6}\s|```|~~~|\||>)/;

// A numbered marker at the very start of a line: "১. ", "12) ", "3. ".
const LEADING_NUMBER_MARKER = /^(\s*)([০-৯]{1,3}|[0-9]{1,3})([.)])\s+/;

// A bullet marker at the very start of a line: "• ", "· ", "▪ ".
const LEADING_BULLET_MARKER = /^(\s*)[•·▪]\s+/;

// Two points glued into one line: "... শেষ। ২. পরের পয়েন্ট".
const INLINE_NUMBER_MARKER = /\s+(?=(?:[০-৯]{1,3}|[0-9]{1,3})[.)]\s)/;

// Numbered markers that sit on a word boundary anywhere inside a line.
const NUMBER_MARKER_OCCURRENCES = /(?<=^|\s)(?:[০-৯]{1,3}|[0-9]{1,3})[.)](?=\s)/g;

const isListItem = (line) => /^\s*(?:[-*+]|\d+[.)])\s+/.test(line);

// A line that starts with a marker and holds two or more of them is an
// enumeration whose points were glued together on a single line.
const isInlineEnumeration = (line) =>
    LEADING_NUMBER_MARKER.test(line) &&
    (line.match(NUMBER_MARKER_OCCURRENCES) || []).length >= 2;

const toAsciiDigits = (value) => value.replace(/[০-৯]/g, (digit) => BENGALI_DIGITS[digit]);

/**
 * Rewrite Bengali numbered/bulleted points into standard markdown lists so the
 * rendered article shows a clean, readable list instead of one long paragraph.
 */
export function normalizeBengaliLists(markdown) {
    if (typeof markdown !== 'string' || markdown.trim() === '') {
        return markdown ?? '';
    }

    const lines = markdown.split('\n').flatMap((line) => {
        const trimmed = line.trim();

        if (trimmed === '' || STRUCTURAL_LINE.test(trimmed)) {
            return [line];
        }

        const parts = isInlineEnumeration(trimmed)
            ? trimmed.split(INLINE_NUMBER_MARKER)
            : [trimmed];

        return parts.map((part) => part.trim()).filter((part) => part !== '');
    });

    const normalized = lines.map((line) => {
        if (line.trim() === '' || STRUCTURAL_LINE.test(line)) {
            return line;
        }

        const bulleted = line.replace(LEADING_BULLET_MARKER, '$1- ');

        return bulleted.replace(
            LEADING_NUMBER_MARKER,
            (match, indent, number, separator) => `${indent}${toAsciiDigits(number)}${separator} `
        );
    });

    // Keep a blank line between list blocks and the surrounding paragraphs so the
    // parser always recognises the points as a real list.
    const spaced = [];
    normalized.forEach((line) => {
        const previous = spaced[spaced.length - 1];

        if (
            previous !== undefined &&
            previous.trim() !== '' &&
            line.trim() !== '' &&
            isListItem(line) !== isListItem(previous)
        ) {
            spaced.push('');
        }

        spaced.push(line);
    });

    return spaced.join('\n');
}

/**
 * Convert stored Bengali article markdown into sanitized, readable HTML.
 */
export function renderMarkdown(rawMarkdown) {
    const raw = typeof rawMarkdown === 'string' ? rawMarkdown : '';

    if (raw.trim() === '') {
        return '<p class="text-muted">কোনো কনটেন্ট পাওয়া যায়নি।</p>';
    }

    // The AI-written markdown opens with an H1 that repeats the article title,
    // which every reader already renders as its own heading. Drop that leading
    // H1 so the title is shown exactly once.
    const body = raw.replace(/^\s*#\s+[^\n]*\n?/, '');

    return DOMPurify.sanitize(marked.parse(normalizeBengaliLists(body)));
}
