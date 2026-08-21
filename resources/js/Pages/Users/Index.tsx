import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { KeyRound, Pencil, Plus, Save, Search, Trash2, X } from 'lucide-react';
import { FormEvent, useState } from 'react';
import Pagination from '@/components/Pagination';
import { useI18n } from '@/lib/i18n';
import type { User, Role, Organization, Participant, Paginated, Flash, Sport } from '@/types';

const createUserSchema = z.object({
    name: z.string().min(1, 'Name is required'),
    username: z.string().min(3, 'Username must be at least 3 characters').regex(/^[a-z0-9_-]+$/, 'Use lowercase letters, numbers, _ or -'),
    email: z.string().min(1, 'Email is required').email('Invalid email'),
    password: z.string().min(1, 'Password is required'),
    password_confirmation: z.string().min(1, 'Please confirm password'),
    roles: z.array(z.number()).default([]),
    participant_id: z.string().optional().default(''),
    sports: z.array(z.string()).default([]),
});

const editUserSchema = z.object({
    name: z.string().min(1, 'Name is required'),
    username: z.string().min(3, 'Username must be at least 3 characters').regex(/^[a-z0-9_-]+$/, 'Use lowercase letters, numbers, _ or -'),
    email: z.string().min(1, 'Email is required').email('Invalid email'),
    password: z.string().optional().default(''),
    password_confirmation: z.string().optional().default(''),
    roles: z.array(z.number()).default([]),
    participant_id: z.string().optional().default(''),
    sports: z.array(z.string()).default([]),
});

type CreateUserForm = z.infer<typeof createUserSchema>;
type EditUserForm = z.infer<typeof editUserSchema>;
type UserForm = CreateUserForm | EditUserForm;

interface UsersIndexProps {
    users: Paginated<User> | User[];
    roles: Role[];
    organizations: Organization[];
    participants: (Participant & { name: string })[];
    sports: Sport[];
}

function UserFormDialog({
    onClose,
    editingUser,
    roles,
    participants,
    sports,
}: {
    onClose: () => void;
    editingUser: User | null;
    roles: Role[];
    participants: Participant[];
    sports: Sport[];
}) {
    const { t } = useI18n();
    const schema = editingUser ? editUserSchema : createUserSchema;
    const { register, handleSubmit, reset, watch, setValue, formState: { errors, isSubmitting } } = useForm<CreateUserForm>({
        defaultValues: editingUser
            ? {
                  name: editingUser.name,
                  username: editingUser.username,
                  email: editingUser.email,
                  password: '',
                  password_confirmation: '',
                  roles: editingUser.roles?.map(r => r.id) ?? [],
                  participant_id: editingUser.participant_id ?? '',
                  sports: editingUser.sports?.map(s => s.id) ?? [],
              }
            : { name: '', username: '', email: '', password: '', password_confirmation: '', roles: [], participant_id: '', sports: [] },
        resolver: zodResolver(schema),
    });

    const selectedRoles = watch('roles');
    const selectedSports = watch('sports');
    const facultyRepRoleId = roles.find(r => r.name === 'faculty-representative')?.id;
    const isFacultyRepSelected = facultyRepRoleId ? (selectedRoles ?? []).includes(facultyRepRoleId) : false;
    const deanRoleId = roles.find(r => r.name === 'dean')?.id;
    const isDeanSelected = deanRoleId ? (selectedRoles ?? []).includes(deanRoleId) : false;
    const adminSportRoleId = roles.find(r => r.name === 'admin-sport')?.id;
    const isAdminSportSelected = adminSportRoleId ? (selectedRoles ?? []).includes(adminSportRoleId) : false;

    const toggleRole = (roleId: number) => {
        const current = selectedRoles ?? [];
        if (current.includes(roleId)) {
            setValue('roles', current.filter(id => id !== roleId), { shouldValidate: true });
        } else {
            setValue('roles', [...current, roleId], { shouldValidate: true });
        }
    };

    const toggleSport = (sportId: string) => {
        const current = selectedSports ?? [];
        if (current.includes(sportId)) {
            setValue('sports', current.filter(id => id !== sportId), { shouldValidate: true });
        } else {
            setValue('sports', [...current, sportId], { shouldValidate: true });
        }
    };

    const onSubmit = (formData: CreateUserForm) => {
        const payload = editingUser
            ? { ...formData, password: formData.password || undefined, password_confirmation: formData.password_confirmation || undefined }
            : formData;

        if (editingUser) {
            router.put(route('users.update', editingUser.uuid), payload, {
                onSuccess: () => onClose(),
            });
        } else {
            router.post(route('users.store'), payload, {
                onSuccess: () => onClose(),
            });
        }
    };

    return (
        <Dialog open={true} onOpenChange={(isOpen) => { if (!isOpen) onClose(); }}>
            <DialogContent className="max-w-lg">
                <form onSubmit={handleSubmit(onSubmit)}>
                    <DialogHeader>
                        <DialogTitle>{editingUser ? t('Edit User') : t('Create New User')}</DialogTitle>
                        <DialogDescription>
                            {editingUser ? t('Update user information and roles.') : t('Create a new user in the organization.')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t('Name')}</Label>
                            <Input id="name" {...register('name')} required />
                            {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="username">{t('Username')}</Label>
                            <Input id="username" autoComplete="username" {...register('username')} required />
                            {errors.username && <p className="text-sm text-destructive">{errors.username.message}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">{t('Email')}</Label>
                            <Input id="email" type="email" {...register('email')} required />
                            {errors.email && <p className="text-sm text-destructive">{errors.email.message}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">{t('Password')} {editingUser && t('Password leave blank')}</Label>
                            <Input id="password" type="password" {...register('password', { required: !editingUser })} />
                            {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">{t('Confirm Password')}</Label>
                            <Input id="password_confirmation" type="password" {...register('password_confirmation', { required: !editingUser })} />
                        </div>

                        <div className="grid gap-2">
                            <Label>{t('Roles')}</Label>
                            <div className="grid gap-2">
                                {roles.map((role) => (
                                    <label key={role.id} className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={selectedRoles?.includes(role.id) ?? false}
                                            onChange={() => toggleRole(role.id)}
                                        />
                                        {role.name}
                                    </label>
                                ))}
                            </div>
                            {errors.roles && <p className="text-sm text-destructive">{errors.roles.message}</p>}
                        </div>

                        {(isFacultyRepSelected || isDeanSelected) && (
                            <div className="grid gap-2">
                                <Label htmlFor="participant_id">{t('Faculty (Participant) *')}</Label>
                                <select
                                    id="participant_id"
                                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                    {...register('participant_id')}
                                    required
                                >
                                    <option value="">{t('-- Select Faculty --')}</option>
                                    {participants.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                                {errors.participant_id && <p className="text-sm text-destructive">{errors.participant_id.message}</p>}
                            </div>
                        )}

                        {isAdminSportSelected && (
                            <div className="grid gap-2">
                                <Label>{t('Sports (admin-sport scope) *')}</Label>
                                <div className="max-h-48 overflow-y-auto rounded-md border p-2">
                                    <div className="grid gap-1">
                                        {sports.map((sport) => (
                                            <label key={sport.id} className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedSports?.includes(sport.id) ?? false}
                                                    onChange={() => toggleSport(sport.id)}
                                                />
                                                {sport.name}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                                {selectedSports?.length === 0 && (
                                    <p className="text-sm text-destructive">{t('Select at least one sport.')}</p>
                                )}
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>{t('Cancel')}</Button>
                        <Button type="submit" disabled={isSubmitting}>
                            <Save className="mr-2 size-4" />
                            {editingUser ? t('Update') : t('Save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function UsersIndex({ users: usersProp, roles, organizations, participants: participantsProp = [], sports: sportsProp = [] }: UsersIndexProps) {
    const { flash } = usePage().props;
    const { t } = useI18n();
    const [dialogMode, setDialogMode] = useState<null | 'create' | User>(null);
    const [deleteUser, setDeleteUser] = useState<User | null>(null);
    const [resetPasswordUser, setResetPasswordUser] = useState<User | null>(null);
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [resetSubmitting, setResetSubmitting] = useState(false);
    const [search, setSearch] = useState(() => new URLSearchParams(window.location.search).get('search') ?? '');

    const users = Array.isArray(usersProp) ? usersProp : (usersProp?.data ?? []);
    const participants = Array.isArray(participantsProp) ? participantsProp : [];
    const sports = Array.isArray(sportsProp) ? sportsProp : [];

    const applySearch = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); router.get(route('users.index'), search.trim() ? { search: search.trim() } : {}, { preserveState: true, preserveScroll: true, replace: true }); };
    const clearSearch = () => { setSearch(''); router.get(route('users.index'), {}, { preserveState: true, preserveScroll: true, replace: true }); };

    const handleDelete = () => {
        if (!deleteUser) return;
        router.delete(route('users.destroy', deleteUser.uuid), {
            preserveScroll: true,
            onSuccess: () => setDeleteUser(null),
        });
    };

    const handleResetPassword = () => {
        if (!resetPasswordUser || !newPassword || newPassword !== confirmPassword) return;
        setResetSubmitting(true);
        router.put(route('users.reset-password', resetPasswordUser.uuid), { password: newPassword, password_confirmation: confirmPassword }, {
            preserveScroll: true,
            onSuccess: () => {
                setResetPasswordUser(null);
                setNewPassword('');
                setConfirmPassword('');
                setResetSubmitting(false);
            },
            onError: () => setResetSubmitting(false),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Users')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Manage users and roles (M1 - RBAC)')}
                        </p>
                    </div>

                    <Button onClick={() => setDialogMode('create')}>
                        <Plus className="mr-2 size-4" />
                        {t('Add User')}
                    </Button>

                    {dialogMode && (
                        <UserFormDialog
                            key={dialogMode === 'create' ? 'create' : dialogMode.id}
                            onClose={() => setDialogMode(null)}
                            editingUser={dialogMode === 'create' ? null : dialogMode}
                            roles={roles}
                            participants={participants}
                            sports={sports}
                        />
                    )}
                </div>
            }
        >
            <Head title={t('Users')} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>{t('Users List')}</CardTitle>
                    <CardDescription>
                        {t('Manage users and assign roles.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={applySearch} className="mb-4 flex gap-2"><div className="relative flex-1"><Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('Search name, username, email...')} className="pl-9 pr-9" aria-label={t('Search users')} />{search && <button type="button" onClick={clearSearch} className="absolute right-2 top-1/2 -translate-y-1/2" aria-label={t('Clear search')}><X className="size-4" /></button>}</div><Button type="submit" variant="secondary">{t('Search')}</Button></form>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Username')}</TableHead>
                                <TableHead>{t('Email')}</TableHead>
                                <TableHead>{t('Organization')}</TableHead>
                                <TableHead>{t('Faculty')}</TableHead>
                                <TableHead>{t('Roles')}</TableHead>
                                <TableHead>{t('Sports')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={9} className="text-center text-muted-foreground">
                                        {search ? t('No users match your search.') : t('No users yet.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {users.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="font-medium">{user.name}</TableCell>
                                    <TableCell className="font-mono text-xs">{user.username}</TableCell>
                                    <TableCell>{user.email}</TableCell>
                                    <TableCell>{user.organization?.name || '-'}</TableCell>
                                    <TableCell>{user.participant?.name || '-'}</TableCell>
                                    <TableCell>
                                        {user.roles && user.roles.length > 0
                                            ? user.roles.map(r => r.name).join(', ')
                                            : '-'}
                                    </TableCell>
                                    <TableCell>
                                        {user.sports && user.sports.length > 0
                                            ? user.sports.map(s => s.name).join(', ')
                                            : '-'}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={
                                                user.is_active
                                                    ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700'
                                                    : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600'
                                            }
                                        >
                                            {user.is_active ? t('Active') : t('Inactive')}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            title={t('Reset Password')}
                                            onClick={() => { setResetPasswordUser(user); setNewPassword(''); setConfirmPassword(''); }}
                                        >
                                            <KeyRound className="size-3" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setDialogMode(user)}
                                        >
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => setDeleteUser(user)}
                                        >
                                            <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>

                <Pagination paginator={usersProp} />
            </Card>

            <Dialog open={!!deleteUser} onOpenChange={(isOpen) => !isOpen && setDeleteUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete User?')}</DialogTitle>
                        <DialogDescription>
                            {t('Are you sure you want to delete...This action cannot be undone.')} <strong>{deleteUser?.name}</strong>
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteUser(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            {t('Yes, Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!resetPasswordUser} onOpenChange={(isOpen) => !isOpen && setResetPasswordUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Reset Password')}</DialogTitle>
                        <DialogDescription>
                            {t('Set a new password for...')} <strong>{resetPasswordUser?.name ?? t('No user')}</strong> ({resetPasswordUser?.email})
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="reset-password">{t('New Password *')}</Label>
                            <Input
                                id="reset-password"
                                type="password"
                                value={newPassword}
                                onChange={e => setNewPassword(e.target.value)}
                                placeholder={t('Min. 8 characters')}
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reset-password-confirm">{t('Confirm Password *')}</Label>
                            <Input
                                id="reset-password-confirm"
                                type="password"
                                value={confirmPassword}
                                onChange={e => setConfirmPassword(e.target.value)}
                                placeholder={t('Repeat the new password')}
                                required
                            />
                            {confirmPassword && newPassword !== confirmPassword && (
                                <p className="text-sm text-destructive">{t('Passwords do not match.')}</p>
                            )}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setResetPasswordUser(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button
                            onClick={handleResetPassword}
                            disabled={resetSubmitting || !newPassword || newPassword.length < 8 || newPassword !== confirmPassword}
                        >
                            <KeyRound className="mr-2 size-4" />
                            {t('Reset Password')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="mt-6 text-xs text-muted-foreground">
                M1: User Management + RBAC. Users can login, create organizations, and assign roles.
            </div>
        </AuthenticatedLayout>
    );
}
