import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useI18n } from '@/lib/i18n';

const deleteSchema = z.object({
    password: z.string().min(1, 'Password is required to confirm deletion'),
});

type DeleteForm = z.infer<typeof deleteSchema>;

export default function DeleteUserForm() {
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<DeleteForm>({
        resolver: zodResolver(deleteSchema),
        defaultValues: { password: '' },
    });

    const onSubmit = (formData: DeleteForm) => {
        router.delete(route('profile.destroy'), {
            data: formData,
            preserveScroll: true,
            onError: () => {
                passwordInput.current?.focus();
            },
            onFinish: () => reset(),
        });
    };

    return (
        <div>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogTrigger asChild>
                    <Button variant="destructive">
                        <Trash2 className="mr-2 size-4" />
                        {t('Delete Account')}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <form onSubmit={handleSubmit(onSubmit)}>
                        <DialogHeader>
                            <DialogTitle>{t('Delete Account')}</DialogTitle>
                            <DialogDescription>
                                {t('This action cannot be undone. All your data will be permanently deleted.')}{' '}
                                {t('Please enter your password to confirm.')}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="delete-password" className="sr-only">{t('Password')}</Label>
                                <Input
                                    id="delete-password"
                                    type="password"
                                    ref={passwordInput}
                                    {...register('password')}
                                    placeholder={t('Enter your password to confirm')}
                                    autoFocus
                                />
                                {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                {t('Cancel')}
                            </Button>
                            <Button type="submit" variant="destructive" disabled={isSubmitting}>
                                {t('Delete Account')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
