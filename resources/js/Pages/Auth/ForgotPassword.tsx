import InputError from '@/components/InputError';
import PrimaryButton from '@/components/PrimaryButton';
import TextInput from '@/components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    status?: string;
}

export default function ForgotPassword({ status }: Props) {
    const { t } = useI18n();

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title={t('Forgot Password')} />

            <div className="mb-4 text-sm text-gray-600">
                {t('Forgot password hint')}
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton className="ms-4" disabled={processing}>
                        {t('Email Password Reset Link')}
                    </PrimaryButton>
                </div>
            </form>

            <div className="mt-6 border-t pt-4 text-center">
                <Link
                    href={route('login')}
                    className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    {t('Back to login')}
                </Link>
            </div>
        </GuestLayout>
    );
}
