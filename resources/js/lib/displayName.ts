export function displayName(value: unknown, _locale: string): string {
    if (typeof value === 'string') return value;
    if (!value || typeof value !== 'object') return '';

    const names = value as { name?: unknown; name_ms?: unknown };
    return (typeof names.name === 'string' && names.name)
        || (typeof names.name_ms === 'string' ? names.name_ms : '');
}
