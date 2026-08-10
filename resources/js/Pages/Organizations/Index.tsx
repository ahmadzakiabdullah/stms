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
import { Pencil, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Pagination from '@/components/Pagination';
import type { Organization, Paginated, Flash } from '@/types';
import { useI18n } from '@/lib/i18n';

const organizationSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    slug: z.string().min(1, 'Slug is required').regex(/^[a-zA-Z0-9_-]+$/, 'Slug must be alpha-numeric with dashes or underscores'),
    organization_type: z.enum(['national', 'state', 'university', 'school', 'private']),
    parent_id: z.string().uuid().nullable().or(z.literal('')),
    is_active: z.boolean(),
});

type OrganizationForm = z.infer<typeof organizationSchema>;

interface OrganizationRow extends Organization {
    sessions_count: number;
    latest_session?: { name: string } | null;
    parent?: { name: string } | null;
}

interface OrganizationsIndexProps {
    organizations: Paginated<OrganizationRow> | OrganizationRow[];
}

export default function OrganizationsIndex({ organizations: organizationsProp }: OrganizationsIndexProps) {
    const { flash } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingOrg, setEditingOrg] = useState<OrganizationRow | null>(null);
    const [deleteOrg, setDeleteOrg] = useState<OrganizationRow | null>(null);

    const organizations = Array.isArray(organizationsProp) ? organizationsProp : (organizationsProp?.data ?? []);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<OrganizationForm>({
        resolver: zodResolver(organizationSchema),
        defaultValues: {
            name: '',
            slug: '',
            organization_type: 'university',
            parent_id: '',
            is_active: true,
        },
    });

    const openCreate = () => {
        setEditingOrg(null);
        reset({
            name: '',
            slug: '',
            organization_type: 'university',
            parent_id: '',
            is_active: true,
        });
        setOpen(true);
    };

    const openEdit = (org: OrganizationRow) => {
        setEditingOrg(org);
        reset({
            name: org.name,
            slug: org.slug,
            organization_type: org.organization_type as OrganizationForm['organization_type'],
            parent_id: org.parent_id || '',
            is_active: org.is_active,
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingOrg(null);
        reset();
    };

    const onSubmit = (formData: OrganizationForm) => {
        if (editingOrg) {
            router.put(route('organizations.update', editingOrg.slug), formData, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('organizations.store'), formData, {
                onSuccess: () => closeDialog(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteOrg) return;
        router.delete(route('organizations.destroy', deleteOrg.slug), {
            preserveScroll: true,
            onSuccess: () => setDeleteOrg(null),
        });
    };

    const { t } = useI18n();

    const organizationTypes = [
        { value: 'national', label: t('National') },
        { value: 'state', label: t('State') },
        { value: 'university', label: t('University') },
        { value: 'school', label: t('School') },
        { value: 'private', label: t('Private') },
    ] as const;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Organizations')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Manage organizations in the system (M1 - Foundation)')}
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={(isOpen) => {
                        if (!isOpen) closeDialog();
                        else setOpen(true);
                    }}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>
                                <Plus className="mr-2 size-4" />
                                {t('Add Organization')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{editingOrg ? t('Edit Organization') : t('Create New Organization')}</DialogTitle>
                                    <DialogDescription>
                                        {editingOrg ? t('Update organization information.') : t('Organizations are the root of the multi-tenancy hierarchy.')}
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">{t('Name')}</Label>
                                        <Input
                                            id="name"
                                            {...register('name')}
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="slug">{t('Slug (unique)')}</Label>
                                        <Input
                                            id="slug"
                                            {...register('slug')}
                                            placeholder={t('e.g. utm or sukma-2026')}
                                            required
                                        />
                                        {errors.slug && <p className="text-sm text-destructive">{errors.slug.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="organization_type">{t('Organization Type')}</Label>
                                        <select
                                            id="organization_type"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('organization_type')}
                                        >
                                            {organizationTypes.map((type) => (
                                                <option key={type.value} value={type.value}>
                                                    {type.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="is_active">{t('Status')}</Label>
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                {...register('is_active')}
                                            />
                                            {t('Active')}
                                        </label>
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={closeDialog}>
                                        {t('Cancel')}
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        <Save className="mr-2 size-4" />
                                        {editingOrg ? t('Update') : t('Save')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            }
        >
            <Head title="Organizations" />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>{t('Organizations List')}</CardTitle>
                    <CardDescription>
                        {t('Organizations are the root of the hierarchy and multi-tenancy.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Slug')}</TableHead>
                                <TableHead>{t('Type')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Parent')}</TableHead>
                                <TableHead>{t('Sessions')}</TableHead>
                                <TableHead>{t('Latest Session')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {organizations.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center text-muted-foreground">
                                        {t('No organizations yet. Create the first one.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {organizations.map((org) => (
                                <TableRow key={org.id}>
                                    <TableCell className="font-medium">{org.name}</TableCell>
                                    <TableCell>
                                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{org.slug}</code>
                                    </TableCell>
                                    <TableCell className="capitalize">{org.organization_type}</TableCell>
                                    <TableCell>
                                        <span
                                            className={
                                                org.is_active
                                                    ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700'
                                                    : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600'
                                            }
                                        >
                                            {org.is_active ? t('Active') : t('Inactive')}
                                        </span>
                                    </TableCell>
                                    <TableCell>{org.parent?.name || '-'}</TableCell>
                                    <TableCell>{org.sessions_count}</TableCell>
                                    <TableCell>{org.latest_session?.name || '-'}</TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => openEdit(org)}
                                        >
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => setDeleteOrg(org)}
                                        >
                                            <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>

                <Pagination paginator={organizationsProp} />
            </Card>

            <Dialog open={!!deleteOrg} onOpenChange={(isOpen) => !isOpen && setDeleteOrg(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Organization?')}</DialogTitle>
                        <DialogDescription>
                            {t('Are you sure you want to delete...This action cannot be undone.')} <strong>{deleteOrg?.name}</strong>
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteOrg(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                            {t('Yes, Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="mt-6 text-xs text-muted-foreground">
                M1: Organization module with full CRUD. Next: RBAC, User association with organization_id, and scoping.
            </div>
        </AuthenticatedLayout>
    );
}
