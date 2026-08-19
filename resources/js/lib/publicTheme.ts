import { type CSSProperties } from 'react';

export type PublicThemeSettings = {
    public_theme_dark?: string;
    public_theme_primary?: string;
    public_theme_accent?: string;
    public_theme_highlight?: string;
    public_theme_background?: string;
    public_theme_text?: string;
};

const DEFAULTS = {
    dark: '#071B33',
    primary: '#0057A8',
    accent: '#20B8E6',
    highlight: '#F4B942',
    background: '#F4F7FA',
    text: '#102A43',
};

const hexToRgba = (hex: string, alpha: number): string => {
    const value = hex.replace('#', '').trim();
    const full = value.length === 3 ? value.split('').map((part) => part + part).join('') : value;

    if (!/^[0-9a-fA-F]{6}$/.test(full)) {
        return `rgba(7, 27, 51, ${alpha})`;
    }

    const int = parseInt(full, 16);
    const r = (int >> 16) & 255;
    const g = (int >> 8) & 255;
    const b = int & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

export const publicThemeStyle = (s: PublicThemeSettings): CSSProperties => {
    const dark = s.public_theme_dark || DEFAULTS.dark;
    const primary = s.public_theme_primary || DEFAULTS.primary;
    const accent = s.public_theme_accent || DEFAULTS.accent;
    const highlight = s.public_theme_highlight || DEFAULTS.highlight;
    const background = s.public_theme_background || DEFAULTS.background;
    const text = s.public_theme_text || DEFAULTS.text;

    return {
        '--public-dark': dark,
        '--public-primary': primary,
        '--public-accent': accent,
        '--public-highlight': highlight,
        '--public-background': background,
        '--public-text': text,
        '--public-primary-soft': hexToRgba(primary, 0.1),
        '--public-primary-border': hexToRgba(primary, 0.3),
        '--public-dark-soft': hexToRgba(dark, 0.05),
        '--public-dark-border': hexToRgba(dark, 0.12),
        '--public-dark-faint': hexToRgba(dark, 0.55),
        '--public-highlight-soft': hexToRgba(highlight, 0.2),
        '--public-accent-soft': hexToRgba(accent, 0.14),
    } as CSSProperties;
};