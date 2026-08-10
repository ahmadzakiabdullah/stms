import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useI18n } from '@/lib/i18n';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

interface EditProps {
    mustVerifyEmail: boolean;
    status: string;
}

export default function Edit({ mustVerifyEmail, status }: EditProps) {
    const { t } = useI18n();
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{t('Profile')}</h1>
                    <p className="text-sm text-muted-foreground">{t('Manage your account settings')}</p>
                </div>
            }
        >
            <Head title={t('Profile')} />

            <div className="mx-auto max-w-2xl space-y-8 py-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Profile Information')}</CardTitle>
                        <CardDescription>{t('Update your name and email address')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Password')}</CardTitle>
                        <CardDescription>{t('Ensure your account uses a strong password')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <UpdatePasswordForm />
                    </CardContent>
                </Card>

                <Card className="border-destructive/20">
                    <CardHeader>
                        <CardTitle className="text-destructive">{t('Delete Account')}</CardTitle>
                        <CardDescription>{t('Permanently delete your account and all associated data')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DeleteUserForm />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
