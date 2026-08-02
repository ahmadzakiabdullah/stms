/**
 * Generate a short event code from an event name, e.g.
 * "Hockey (Men's)" -> "HM", "Football (Men's)" -> "FM",
 * "Sepak Takraw (Team)" -> "STT", "Chess (Mix)" -> "CX".
 *
 * Rule: initials of the first 1-2 sport words + a category letter
 * (Men's=M, Women's=W, Mix=X, Open=O, Team=T, otherwise first letter).
 */
export const eventCode = (eventName?: string | null): string => {
    if (!eventName) return '';

    const trimmed = eventName.trim();
    if (!trimmed) return '';

    const parens = [...trimmed.matchAll(/\(([^)]*)\)/g)]
        .map((m) => m[1].trim())
        .filter(Boolean);
    const category = parens.length > 0 ? parens[parens.length - 1] : '';
    const sportPart = trimmed.replace(/\([^)]*\)/g, '').trim();

    const sportInitials = sportPart
        .replace(/-/g, ' ')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => {
            const letter = word.replace(/[^A-Za-z]/g, '')[0];
            return letter ? letter.toUpperCase() : '';
        })
        .join('')
        .slice(0, 2);

    const categoryCodes: Record<string, string> = {
        "men's": 'M',
        men: 'M',
        male: 'M',
        "women's": 'W',
        women: 'W',
        female: 'W',
        mix: 'X',
        mixed: 'X',
        open: 'O',
        team: 'T',
    };

    const categoryCode = category
        ? (categoryCodes[category.toLowerCase()] ?? category[0]?.toUpperCase() ?? '')
        : '';

    return `${sportInitials}${categoryCode}`;
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
