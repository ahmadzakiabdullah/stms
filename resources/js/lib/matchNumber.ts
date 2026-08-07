/**
 * Generate a short event code from an event name, e.g.
 * "Hockey - Men's" / "Hockey (Men's)" -> "HM",
 * "Lawn Bowls - Mix" -> "LBM", "Football - Men's" -> "FM",
 * "Sepak Takraw - Team" -> "STT".
 *
 * Handles three shapes (in priority order):
 *   1. Dash-separated with category: "SAF 2026 Fasa 1 - Hockey - Men's"
 *   2. Dash-separated without category: "SAF 2026 Fasa 1 - Hockey"
 *   3. Parenthesized category: "Hockey (Men's)"
 *
 * Rule: initials of the first 1-2 sport words + a category letter
 * (Men's/Mix=M, Women's=W, Team=T, Open=O, ...).
 */

const CATEGORY_CODES: Record<string, string> = {
    "men's": 'M',
    men: 'M',
    male: 'M',
    "women's": 'W',
    women: 'W',
    female: 'W',
    mix: 'M',
    mixed: 'M',
    open: 'O',
    team: 'T',
    singles: 'S',
    doubles: 'D',
};

const sportInitials = (sportPart: string): string =>
    sportPart
        .replace(/-/g, ' ')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => {
            const letter = word.replace(/[^A-Za-z]/g, '')[0];
            return letter ? letter.toUpperCase() : '';
        })
        .join('');

const categoryCode = (category: string): string =>
    CATEGORY_CODES[category.toLowerCase()] ?? category[0]?.toUpperCase() ?? '';

export const eventCode = (eventName?: string | null): string => {
    if (!eventName) return '';

    const trimmed = eventName.trim();
    if (!trimmed) return '';

    const segments = trimmed
        .split(/\s*-\s*/)
        .map((segment) => segment.trim())
        .filter(Boolean);

    if (segments.length >= 2) {
        const last = segments[segments.length - 1];

        if (CATEGORY_CODES[last.toLowerCase()]) {
            const sportWords = segments.slice(1, -1);

            return `${sportInitials((sportWords.length > 0 ? sportWords : [segments[0]]).join(' '))}${CATEGORY_CODES[last.toLowerCase()]}`;
        }

        return sportInitials(segments.slice(1).join(' '));
    }

    const parens = [...trimmed.matchAll(/\(([^)]*)\)/g)]
        .map((m) => m[1].trim())
        .filter(Boolean);

    if (parens.length > 0) {
        const category = parens[parens.length - 1];
        const sportPart = trimmed.replace(/\([^)]*\)/g, '').trim();

        return `${sportInitials(sportPart)}${categoryCode(category)}`;
    }

    return sportInitials(trimmed);
};

/**
 * Short match label combining the event code and the match number,
 * e.g. "HM1" for Hockey (Men's) match 1. Falls back to the plain
 * match number when no event name is available.
 */
export const matchNumberLabel = (
    matchNumber?: number | null,
    eventName?: string | null
): string => {
    if (matchNumber == null) return '-';

    const code = eventCode(eventName);

    return code ? `${code}${matchNumber}` : String(matchNumber);
};
