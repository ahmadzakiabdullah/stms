import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    settings: {
        app_name: string;
        logo_url: string | null;
        favicon_url: string | null;
    };
}

export default function SettingsIndex({ settings }: Props) {
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
        <AuthenticatedLayout header={<h1 className="text-2xl font-semibold tracking-tight">Settings</h1>}>
            <Head title="Settings" />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>
            )}

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>General</CardTitle>
                        <CardDescription>Application name and branding</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="app_name">Application Name</Label>
                            <Input id="app_name" value={appName} onChange={(e) => setAppName(e.target.value)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Logo</CardTitle>
                        <CardDescription>Upload your organization logo (PNG, JPG, SVG, WebP — max 2MB)</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {settings.logo_url && (
                            <div className="mb-4">
                                <Label>Current Logo</Label>
                                <img src={settings.logo_url} alt="Logo" className="mt-2 h-20 w-auto rounded border object-contain" />
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="logo">Upload New Logo</Label>
                            <Input id="logo" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" onChange={(e) => setLogoFile(e.target.files?.[0] || null)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Favicon</CardTitle>
                        <CardDescription>Upload your favicon (PNG, ICO, SVG — max 1MB)</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="favicon">Upload Favicon</Label>
                            <Input id="favicon" type="file" accept="image/png,image/x-icon,image/svg+xml" onChange={(e) => setFaviconFile(e.target.files?.[0] || null)} />
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button onClick={handleSave} disabled={saving}>
                        {saving ? 'Saving...' : 'Save Settings'}
                    </Button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
