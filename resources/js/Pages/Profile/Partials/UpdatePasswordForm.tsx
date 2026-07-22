import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { Lock, Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';

const passwordSchema = z.object({
    current_password: z.string().min(1, 'Current password is required'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
}).refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
});

type PasswordForm = z.infer<typeof passwordSchema>;

export default function UpdatePasswordForm() {
    const [saved, setSaved] = useState(false);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<PasswordForm>({
        resolver: zodResolver(passwordSchema),
        defaultValues: {
            current_password: '',
            password: '',
            password_confirmation: '',
        },
    });

    const onSubmit = (formData: PasswordForm) => {
        router.put(route('password.update'), formData, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setSaved(true);
            },
            onError: () => {},
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
                <Label htmlFor="current_password">Current Password</Label>
                <Input
                    id="current_password"
                    type="password"
                    {...register('current_password')}
                    autoComplete="current-password"
                />
                {errors.current_password && <p className="text-sm text-destructive">{errors.current_password.message}</p>}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password">New Password</Label>
                <Input
                    id="password"
                    type="password"
                    {...register('password')}
                    autoComplete="new-password"
                />
                {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Confirm Password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    {...register('password_confirmation')}
                    autoComplete="new-password"
                />
                {errors.password_confirmation && <p className="text-sm text-destructive">{errors.password_confirmation.message}</p>}
            </div>

            <div className="flex items-center gap-3">
                <Button type="submit" disabled={isSubmitting}>
                    <Save className="mr-2 size-4" />
                    Save
                </Button>
                {saved && <span className="text-sm text-emerald-600">Saved.</span>}
            </div>
        </form>
    );
}
