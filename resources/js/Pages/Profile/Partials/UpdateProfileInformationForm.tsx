import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, router, usePage } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const profileSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    email: z.string().email('Please enter a valid email address'),
});

type ProfileForm = z.infer<typeof profileSchema>;

interface UpdateProfileInformationProps {
    mustVerifyEmail: boolean;
    status: string;
}

export default function UpdateProfileInformation({ mustVerifyEmail, status }: UpdateProfileInformationProps) {
    const user = usePage().props.auth.user;
    const [saved, setSaved] = useState(false);

    const { register, handleSubmit, formState: { errors, isSubmitting, isDirty } } = useForm<ProfileForm>({
        resolver: zodResolver(profileSchema),
        defaultValues: {
            name: user.name,
            email: user.email,
        },
    });

    const onSubmit = (formData: ProfileForm) => {
        router.put(route('profile.update'), formData, {
            preserveScroll: true,
            onSuccess: () => {
                setSaved(true);
            },
        });
    };

    useEffect(() => {
        if (saved) {
            const t = setTimeout(() => setSaved(false), 2000);
            return () => clearTimeout(t);
        }
    }, [saved]);

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input id="name" {...register('name')} required autoComplete="name" />
                {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">Email</Label>
                <Input id="email" type="email" {...register('email')} required autoComplete="username" />
                {errors.email && <p className="text-sm text-destructive">{errors.email.message}</p>}
            </div>

            {mustVerifyEmail && user.email_verified_at === null && (
                <div className="rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                    Your email is unverified.{' '}
                    <Link
                        href={route('verification.send')}
                        method="post"
                        as="button"
                        className="underline hover:text-amber-900"
                    >
                        Resend verification email
                    </Link>
                    {status === 'verification-link-sent' && (
                        <span className="block mt-1 font-medium text-emerald-600">
                            Verification link sent!
                        </span>
                    )}
                </div>
            )}

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={isSubmitting || !isDirty}>
                    <Save className="mr-2 size-4" />
                    Save
                </Button>
                {saved && <span className="text-sm text-emerald-600">Saved.</span>}
            </div>
        </form>
    );
}
