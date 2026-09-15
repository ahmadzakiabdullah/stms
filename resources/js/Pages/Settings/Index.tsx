import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useT } from '@/lib/i18n';

interface Props {
    settings: {
        app_name: string;
        logo_url: string | null;
        favicon_url: string | null;
    };
}

export default function SettingsIndex({ settings }: Props) {
    const t = useT();
    const { flash } = usePage().props;
    const [appName, setAppName] = useState(settings.app_name);
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [faviconFile, setFaviconFile] = useState<File | null>(null);
    const [saving, setSaving] = useState(false);

    const handleSave = () => {
        const fd = new FormData();
        fd.append('app_name', appName);
        if (logoFile) fd.append('logo', logoFile);
        if (faviconFile) fd.append('favicon', faviconFile);

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
                        <CardDescription>{t('Upload your organization logo (PNG, JPG, SVG, WebP — max 2MB)')}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {settings.logo_url && (
                            <div className="mb-4">
                                <Label>{t('Current Logo')}</Label>
                                <img src={settings.logo_url} alt={t('Logo')} className="mt-2 h-20 w-auto rounded border object-contain" />
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
                        <CardTitle>{t('Favicon')}</CardTitle>
                        <CardDescription>{t('Upload your favicon (PNG, ICO, SVG — max 1MB)')}</CardDescription>
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
