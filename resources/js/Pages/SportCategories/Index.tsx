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
import { Mars, Pencil, Plus, Save, Trash2, Venus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import Pagination from '@/components/Pagination';
import type { Sport, SportCategory, Paginated, Flash } from '@/types';
import { useI18n } from '@/lib/i18n';

const categorySchema = z.object({
    sport_id: z.string().uuid('Sport is required'),
    name: z.string().min(1, 'Name is required').max(255),
    slug: z.string().regex(/^[a-zA-Z0-9_-]+$/, 'Slug must be alpha-numeric with dashes or underscores').optional().default(''),
    quota_mode: z.enum(['gender_based', 'open_total', 'mixed_total']).default('gender_based'),
    max_athletes_total: z.union([z.coerce.number().int().min(0), z.literal(''), z.literal(undefined)]).optional().transform(v => v === '' || v === undefined ? null : v),
    max_male_athletes: z.union([z.coerce.number().int().min(0), z.literal(''), z.literal(undefined)]).optional().transform(v => v === '' || v === undefined ? null : v),
    max_female_athletes: z.union([z.coerce.number().int().min(0), z.literal(''), z.literal(undefined)]).optional().transform(v => v === '' || v === undefined ? null : v),
    min_male_athletes: z.union([z.coerce.number().int().min(0), z.literal(''), z.literal(undefined)]).optional().transform(v => v === '' || v === undefined ? null : v),
    min_female_athletes: z.union([z.coerce.number().int().min(0), z.literal(''), z.literal(undefined)]).optional().transform(v => v === '' || v === undefined ? null : v),
    max_officials: z.union([z.coerce.number().int().min(0), z.literal(''), z.literal(undefined)]).optional().transform(v => v === '' || v === undefined ? null : v),
});

type CategoryForm = z.infer<typeof categorySchema>;

interface SportCategoriesIndexProps {
    categories: Paginated<SportCategory> | SportCategory[];
    sports: Sport[];
}

export default function SportCategoriesIndex({ categories: categoriesProp, sports }: SportCategoriesIndexProps) {
    const { flash, isSuperAdmin = false } = usePage().props;
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState<SportCategory | null>(null);
    const [deleteCategory, setDeleteCategory] = useState<SportCategory | null>(null);

    const categories = Array.isArray(categoriesProp) ? categoriesProp : (categoriesProp?.data ?? []);

    const autoSlugRef = useRef(true);
    const slugify = (val: string) =>
        val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    const { register, handleSubmit, reset, watch, setValue, formState: { errors, isSubmitting } } = useForm<CategoryForm>({
        resolver: zodResolver(categorySchema),
        defaultValues: {
            sport_id: sports.length > 0 ? sports[0].id : '',
            name: '',
            slug: '',
            quota_mode: 'gender_based',
            max_athletes_total: null,
            max_male_athletes: null,
            max_female_athletes: null,
            min_male_athletes: null,
            min_female_athletes: null,
            max_officials: null,
        },
    });

    const name = watch('name');
    const selectedSportId = watch('sport_id');
    const selectedSport = sports.find(s => s.id === selectedSportId);
    const quotaMode = watch('quota_mode');
    const displayCategoryName = (category: SportCategory) => {
        const sportName = category.sport?.name?.trim();
        const categoryName = category.name.trim();

        if (!sportName || categoryName.toLowerCase().startsWith(sportName.toLowerCase())) {
            return categoryName;
        }

        return `${sportName} ${categoryName}`;
    };
    const quotaSummary = (category: SportCategory) => {
        if (category.quota_mode !== 'gender_based' && category.max_athletes_total !== null) {
            const minimums = category.min_male_athletes || category.min_female_athletes
                ? `, min M ${category.min_male_athletes ?? 0}, min F ${category.min_female_athletes ?? 0}`
                : '';

            return `${category.max_athletes_total} total${minimums}`;
        }

        return (
            <div className="flex items-center gap-2">
                <span className="inline-flex items-center gap-1 text-blue-700">
                    <Mars className="size-3.5" aria-hidden="true" />
                    <span className="sr-only">{t('Male')}</span>
                    {category.max_male_athletes ?? '-'}
                </span>
                <span className="text-muted-foreground">/</span>
                <span className="inline-flex items-center gap-1 text-pink-700">
                    <Venus className="size-3.5" aria-hidden="true" />
                    <span className="sr-only">{t('Female')}</span>
                    {category.max_female_athletes ?? '-'}
                </span>
            </div>
        );
    };

    useEffect(() => {
        if (!editingCategory && autoSlugRef.current && name && selectedSport) {
            setValue('slug', slugify(selectedSport.name + ' ' + name));
        }
    }, [name, selectedSport, editingCategory, setValue]);

    const openCreate = () => {
        setEditingCategory(null);
        autoSlugRef.current = true;
        reset({
            sport_id: sports.length > 0 ? sports[0].id : '',
            name: '',
            slug: '',
            quota_mode: 'gender_based',
            max_athletes_total: null,
            max_male_athletes: null,
            max_female_athletes: null,
            min_male_athletes: null,
            min_female_athletes: null,
            max_officials: null,
        });
        setOpen(true);
    };

    const openEdit = (category: SportCategory) => {
        setEditingCategory(category);
        autoSlugRef.current = false;
        reset({
            sport_id: category.sport_id,
            name: category.name,
            slug: category.slug,
            quota_mode: category.quota_mode ?? 'gender_based',
            max_athletes_total: category.max_athletes_total,
            max_male_athletes: category.max_male_athletes,
            max_female_athletes: category.max_female_athletes,
            min_male_athletes: category.min_male_athletes,
            min_female_athletes: category.min_female_athletes,
            max_officials: category.max_officials,
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingCategory(null);
        reset();
    };

    const [serverError, setServerError] = useState<string | null>(null);

    const onSubmit = (formData: CategoryForm) => {
        setServerError(null);
        if (editingCategory) {
            router.put(route('sport-categories.update', editingCategory.id), formData, {
                onSuccess: () => closeDialog(),
                onError: (err) => setServerError(Object.values(err).join(', ')),
            });
        } else {
            router.post(route('sport-categories.store'), formData, {
                onSuccess: () => closeDialog(),
                onError: (err) => setServerError(Object.values(err).join(', ')),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteCategory) return;
        router.delete(route('sport-categories.destroy', deleteCategory.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteCategory(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Sport Categories')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Manage categories for each sport')}
                        </p>
                    </div>

                    {isSuperAdmin && (
                    <Dialog open={open} onOpenChange={(isOpen) => {
                        if (!isOpen) closeDialog();
                        else setOpen(true);
                    }}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate} disabled={sports.length === 0}>
                                <Plus className="mr-2 size-4" />
                                {t('Add Category')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] overflow-y-auto">
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{editingCategory ? t('Edit Category') : t('Create New Category')}</DialogTitle>
                                    <DialogDescription>
                                        {t('Categories are specific divisions or events within a sport.')}
                                    </DialogDescription>
                                </DialogHeader>

                                {serverError && (
                                    <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                                        {serverError}
                                    </div>
                                )}

                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="sport_id">{t('Sport')}</Label>
                                        <select
                                            id="sport_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('sport_id')}
                                            disabled={!!editingCategory}
                                            required
                                        >
                                            <option value="">{t('-- Select Sport --')}</option>
                                            {sports.map((sport) => (
                                                <option key={sport.id} value={sport.id}>
                                                    {sport.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.sport_id && <p className="text-sm text-destructive">{errors.sport_id.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">{t('Category Name')}</Label>
                                        <Input
                                            id="name"
                                            {...register('name')}
                                            placeholder={t("e.g. Men's Singles")}
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="slug">{t('Slug (unique per sport)')}</Label>
                                        <Input
                                            id="slug"
                                            {...register('slug')}
                                            placeholder={t('mens-singles')}
                                            onChange={(e) => {
                                                autoSlugRef.current = false;
                                                register('slug').onChange(e);
                                            }}
                                        />
                                        {errors.slug && <p className="text-sm text-destructive">{errors.slug.message}</p>}
                                    </div>

                                    <div className="border-t pt-4">
                                        <h4 className="mb-3 text-sm font-medium">{t('Quota / Participant Limits')}</h4>
                                        <div className="mb-3 grid gap-2">
                                            <Label htmlFor="quota_mode">{t('Quota Mode')}</Label>
                                            <select
                                                id="quota_mode"
                                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                                {...register('quota_mode')}
                                            >
                                                <option value="gender_based">{t('Gender based')}</option>
                                                <option value="open_total">{t('Open total')}</option>
                                                <option value="mixed_total">{t('Mixed total with minimums')}</option>
                                            </select>
                                        </div>
                                        <div className="grid grid-cols-3 gap-3">
                                            {quotaMode !== 'gender_based' && (
                                            <div className="grid gap-2">
                                                <Label htmlFor="max_athletes_total">{t('Max Total Athletes')}</Label>
                                                <Input
                                                    id="max_athletes_total"
                                                    type="number"
                                                    min="0"
                                                    {...register('max_athletes_total')}
                                                    placeholder="e.g. 6"
                                                />
                                                {errors.max_athletes_total && <p className="text-sm text-destructive">{String(errors.max_athletes_total.message ?? '')}</p>}
                                            </div>
                                            )}
                                            <div className="grid gap-2">
                                                <Label htmlFor="max_male_athletes">{t('Max Male Athletes')}</Label>
                                                <Input
                                                    id="max_male_athletes"
                                                    type="number"
                                                    min="0"
                                                    {...register('max_male_athletes')}
                                                    placeholder="e.g. 12"
                                                />
                                                {errors.max_male_athletes && <p className="text-sm text-destructive">{String(errors.max_male_athletes.message ?? '')}</p>}
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="max_female_athletes">{t('Max Female Athletes')}</Label>
                                                <Input
                                                    id="max_female_athletes"
                                                    type="number"
                                                    min="0"
                                                    {...register('max_female_athletes')}
                                                    placeholder="e.g. 12"
                                                />
                                                {errors.max_female_athletes && <p className="text-sm text-destructive">{String(errors.max_female_athletes.message ?? '')}</p>}
                                            </div>
                                            {quotaMode === 'mixed_total' && (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="min_male_athletes">{t('Min Male Athletes')}</Label>
                                                    <Input
                                                        id="min_male_athletes"
                                                        type="number"
                                                        min="0"
                                                        {...register('min_male_athletes')}
                                                        placeholder="e.g. 2"
                                                    />
                                                    {errors.min_male_athletes && <p className="text-sm text-destructive">{String(errors.min_male_athletes.message ?? '')}</p>}
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="min_female_athletes">{t('Min Female Athletes')}</Label>
                                                    <Input
                                                        id="min_female_athletes"
                                                        type="number"
                                                        min="0"
                                                        {...register('min_female_athletes')}
                                                        placeholder="e.g. 2"
                                                    />
                                                    {errors.min_female_athletes && <p className="text-sm text-destructive">{String(errors.min_female_athletes.message ?? '')}</p>}
                                                </div>
                                            </>
                                            )}
                                            <div className="grid gap-2">
                                                <Label htmlFor="max_officials">{t('Max Officials')}</Label>
                                                <Input
                                                    id="max_officials"
                                                    type="number"
                                                    min="0"
                                                    {...register('max_officials')}
                                                    placeholder="e.g. 5"
                                                />
                                                {errors.max_officials && <p className="text-sm text-destructive">{String(errors.max_officials.message ?? '')}</p>}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={closeDialog}>
                                        {t('Cancel')}
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        <Save className="mr-2 size-4" />
                                        {editingCategory ? t('Update') : t('Save')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    )}
                </div>
            }
        >
            <Head title={t('Sport Categories')} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {flash.error}
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>{t('Categories List')}</CardTitle>
                    <CardDescription>
                        {t('Categories define the specific competitions or divisions under each sport.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Sport')}</TableHead>
                                <TableHead>{t('Athlete Quota')}</TableHead>
                                <TableHead>{t('Officials')}</TableHead>
                                {isSuperAdmin && <TableHead className="text-right">{t('Actions')}</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={isSuperAdmin ? 5 : 4} className="text-center text-muted-foreground">
                                        {t('No categories yet.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {categories.map((category) => (
                                <TableRow key={category.id}>
                                    <TableCell className="font-medium">
                                        {displayCategoryName(category)}
                                    </TableCell>
                                    <TableCell>{category.sport?.name || '-'}</TableCell>
                                    <TableCell>{quotaSummary(category)}</TableCell>
                                    <TableCell>{category.max_officials ?? '-'}</TableCell>
                                    {isSuperAdmin && (
                                    <TableCell className="text-right space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => openEdit(category)}
                                        >
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => setDeleteCategory(category)}
                                        >
                                            <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                        </Button>
                                    </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>

                <Pagination paginator={categoriesProp} />
            </Card>

            {isSuperAdmin && (
            <Dialog open={!!deleteCategory} onOpenChange={(isOpen) => !isOpen && setDeleteCategory(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Category?')}</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{deleteCategory?.name}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteCategory(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                            {t('Yes, Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            )}

            <div className="mt-6 text-xs text-muted-foreground">
                M2: Categories are managed per sport. Next: Sessions and Tournaments.
            </div>
        </AuthenticatedLayout>
    );
}
