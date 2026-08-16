import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useI18n } from '@/lib/i18n';

interface Props {
    settings: {
        app_name: string;
        logo_url: string | null;
        favicon_url: string | null;
        tournament_logo_url: string | null;
        secretariat_address: string;
        public_theme_dark: string;
        public_theme_primary: string;
        public_theme_accent: string;
        public_theme_highlight: string;
        public_theme_background: string;
        public_theme_text: string;
    };
}

export default function SettingsIndex({ settings }: Props) {
    const { t } = useI18n();
    const { flash } = usePage().props;
    const [appName, setAppName] = useState(settings.app_name);
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [faviconFile, setFaviconFile] = useState<File | null>(null);
    const [tournamentLogoFile, setTournamentLogoFile] = useState<File | null>(null);
    const [secretariatAddress, setSecretariatAddress] = useState(settings.secretariat_address);
    const [saving, setSaving] = useState(false);
    const [theme, setTheme] = useState({
        dark: settings.public_theme_dark,
        primary: settings.public_theme_primary,
        accent: settings.public_theme_accent,
        highlight: settings.public_theme_highlight,
        background: settings.public_theme_background,
        text: settings.public_theme_text,
    });

    const handleSave = () => {
        const fd = new FormData();
        fd.append('app_name', appName);
        if (logoFile) fd.append('logo', logoFile);
        if (faviconFile) fd.append('favicon', faviconFile);
        if (tournamentLogoFile) fd.append('tournament_logo', tournamentLogoFile);
        fd.append('secretariat_address', secretariatAddress);
        Object.entries(theme).forEach(([key, value]) => fd.append(`public_theme_${key}`, value));

        setSaving(true);
        router.post(route('settings.update'), fd, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    return (
        <AuthenticatedLayout header={<h1 className="text-2xl font-semibold tracking-tight">{t('Settings')}</h1>}>
            <Head title={t('Settings')} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>
            )}

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Public Portal Theme')}</CardTitle>
                        <CardDescription>{t('Customize the public portal colours for this organization.')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {Object.entries(theme).map(([key, value]) => (
                                <div key={key} className="space-y-2">
                                    <Label htmlFor={`theme-${key}`}>{t(key.charAt(0).toUpperCase() + key.slice(1))}</Label>
                                    <div className="flex gap-2">
                                        <Input id={`theme-${key}`} type="color" value={value} onChange={(event) => setTheme(current => ({ ...current, [key]: event.target.value.toUpperCase() }))} className="h-10 w-14 cursor-pointer p-1" />
                                        <Input value={value} onChange={(event) => setTheme(current => ({ ...current, [key]: event.target.value }))} pattern="#[0-9A-Fa-f]{6}" aria-label={`${key} hex colour`} />
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="overflow-hidden rounded-2xl border" style={{ backgroundColor: theme.background, color: theme.text }}>
                            <div className="p-5 text-white" style={{ backgroundColor: theme.dark }}><p className="text-xs font-bold uppercase tracking-widest" style={{ color: theme.accent }}>{t('Theme preview')}</p><h3 className="mt-2 text-2xl font-black">{appName}</h3><button type="button" className="mt-4 rounded-lg px-4 py-2 text-sm font-bold" style={{ backgroundColor: theme.highlight, color: theme.dark }}>{t('Primary action')}</button></div>
                            <div className="flex gap-3 p-5"><span className="size-10 rounded-xl" style={{ backgroundColor: theme.primary }} /><span className="size-10 rounded-xl" style={{ backgroundColor: theme.accent }} /><span className="size-10 rounded-xl" style={{ backgroundColor: theme.highlight }} /></div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('General')}</CardTitle>
                        <CardDescription>{t('Application name and branding')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="app_name">{t('Application Name')}</Label>
                            <Input id="app_name" value={appName} onChange={(e) => setAppName(e.target.value)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Logo')}</CardTitle>
                        <CardDescription>{t('Upload your organization logo (PNG, JPG, SVG, WebP - max 2MB)')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {settings.logo_url && (
                            <div className="mb-4">
                                <Label>{t('Current Logo')}</Label>
                                <img src={settings.logo_url} alt="Logo" className="mt-2 h-20 w-auto rounded border object-contain" />
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="logo">{t('Upload New Logo')}</Label>
                            <Input id="logo" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" onChange={(e) => setLogoFile(e.target.files?.[0] || null)} />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Participation Form Branding')}</CardTitle>
                        <CardDescription>{t('Configure the tournament logo and secretariat address shown on the printable confirmation form.')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {settings.tournament_logo_url && (
                            <div>
                                <Label>{t('Current Tournament Logo')}</Label>
                                <img src={settings.tournament_logo_url} alt="Tournament logo" className="mt-2 h-20 w-auto rounded border object-contain" />
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="tournament_logo">{t('Upload Tournament Logo')}</Label>
                            <Input id="tournament_logo" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" onChange={(e) => setTournamentLogoFile(e.target.files?.[0] || null)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="secretariat_address">{t('Secretariat Address')}</Label>
                            <textarea id="secretariat_address" rows={4} className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={secretariatAddress} onChange={(e) => setSecretariatAddress(e.target.value)} placeholder={t('Enter the full secretariat address')} />
                        </div>
                    </CardContent>
                </Card>


                <Card>
                    <CardHeader>
                        <CardTitle>{t('Favicon')}</CardTitle>
                        <CardDescription>{t('Upload your favicon (PNG, ICO, SVG - max 1MB)')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="favicon">{t('Upload Favicon')}</Label>
                            <Input id="favicon" type="file" accept="image/png,image/x-icon,image/svg+xml" onChange={(e) => setFaviconFile(e.target.files?.[0] || null)} />
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button onClick={handleSave} disabled={saving}>
                        {saving ? t('Saving...') : t('Save Settings')}
                    </Button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
