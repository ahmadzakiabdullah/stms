import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
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
import { ChevronDown, ChevronRight, Pencil, Plus, Save, Trash2 } from 'lucide-react';
import { Fragment, useEffect, useRef, useState } from 'react';
import Pagination from '@/components/Pagination';
import type { Paginated, Flash, Sport, SportCategory } from '@/types';

const sportSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    slug: z.string().min(1, 'Slug is required').regex(/^[a-zA-Z0-9_-]+$/, 'Slug must be alpha-numeric with dashes or underscores'),
    icon: z.string().optional().default(''),
    is_active: z.boolean(),
});

type SportForm = z.infer<typeof sportSchema>;

const categorySchema = z.object({
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

interface SportsIndexProps {
    sports: Paginated<Sport> | Sport[];
}

export default function SportsIndex({ sports: sportsProp }: SportsIndexProps) {
    const { flash, isSuperAdmin = false } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingSport, setEditingSport] = useState<Sport | null>(null);
    const [deleteSport, setDeleteSport] = useState<Sport | null>(null);
    const [expandedId, setExpandedId] = useState<string | null>(null);
    const [serverError, setServerError] = useState<string | null>(null);

    // ── Category dialog state ──
    const [catOpen, setCatOpen] = useState(false);
    const [editingCat, setEditingCat] = useState<SportCategory | null>(null);
    const [deleteCat, setDeleteCat] = useState<SportCategory | null>(null);
    const [catSportId, setCatSportId] = useState<string>('');
    const [catServerError, setCatServerError] = useState<string | null>(null);

    const sports = Array.isArray(sportsProp) ? sportsProp : (sportsProp?.data ?? []);

    // ── Sport form ──
    const autoSlugRef = useRef(true);
    const slugify = (val: string) =>
        val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    const sportForm = useForm<SportForm>({
        resolver: zodResolver(sportSchema),
        defaultValues: { name: '', slug: '', icon: '', is_active: true },
    });
    const { register, handleSubmit, reset, watch, setValue, formState: { errors, isSubmitting } } = sportForm;
    const name = watch('name');

    useEffect(() => {
        if (!editingSport && autoSlugRef.current && name) {
            setValue('slug', slugify(name));
        }
    }, [name, editingSport, setValue]);

    const openCreate = () => {
        setEditingSport(null);
        setServerError(null);
        autoSlugRef.current = true;
        reset({ name: '', slug: '', icon: '', is_active: true });
        setOpen(true);
    };

    const openEdit = (sport: Sport) => {
        setEditingSport(sport);
        setServerError(null);
        autoSlugRef.current = false;
        reset({ name: sport.name, slug: sport.slug, icon: sport.icon || '', is_active: sport.is_active });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingSport(null);
        setServerError(null);
        reset();
    };

    const onSubmit = (formData: SportForm) => {
        setServerError(null);
        const cb = {
            onSuccess: () => closeDialog(),
            onError: (err: Record<string, string>) => setServerError(Object.values(err).join(', ')),
        };
        if (editingSport) {
            router.put(route('sports.update', editingSport.slug), formData, cb);
        } else {
            router.post(route('sports.store'), formData, cb);
        }
    };

    const handleDelete = () => {
        if (!deleteSport) return;
        router.delete(route('sports.destroy', deleteSport.slug), {
            preserveScroll: true,
            onSuccess: () => setDeleteSport(null),
        });
    };

    // ── Category form ──
    const catForm = useForm<CategoryForm>({
        resolver: zodResolver(categorySchema),
        defaultValues: {
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
    const { register: catReg, handleSubmit: catSubmit, reset: catReset, setValue: catSetValue, watch: catWatch, formState: { errors: catErrors, isSubmitting: catSubmitting } } = catForm;
    const catName = catWatch('name');
    const catQuotaMode = catWatch('quota_mode');

    const categoryQuotaSummary = (category: SportCategory) => {
        if (category.quota_mode !== 'gender_based' && category.max_athletes_total !== null) {
            const minimums = category.min_male_athletes || category.min_female_athletes
                ? `, min M ${category.min_male_athletes ?? 0}, min F ${category.min_female_athletes ?? 0}`
                : '';

            return `${category.max_athletes_total} total${minimums}`;
        }

        return `M ${category.max_male_athletes ?? '-'} / F ${category.max_female_athletes ?? '-'}`;
    };

    useEffect(() => {
        if (!editingCat && catName) {
            catSetValue('slug', slugify(catName));
        }
    }, [catName, editingCat, catSetValue]);

    const openCatCreate = (sportId: string) => {
        setCatSportId(sportId);
        setEditingCat(null);
        setCatServerError(null);
        catReset({
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
        setCatOpen(true);
    };

    const openCatEdit = (cat: SportCategory) => {
        setCatSportId(cat.sport_id);
        setEditingCat(cat);
        setCatServerError(null);
        catReset({
            name: cat.name,
            slug: cat.slug,
            quota_mode: cat.quota_mode ?? 'gender_based',
            max_athletes_total: cat.max_athletes_total,
            max_male_athletes: cat.max_male_athletes,
            max_female_athletes: cat.max_female_athletes,
            min_male_athletes: cat.min_male_athletes,
            min_female_athletes: cat.min_female_athletes,
            max_officials: cat.max_officials,
        });
        setCatOpen(true);
    };

    const closeCatDialog = () => {
        setCatOpen(false);
        setEditingCat(null);
        setCatServerError(null);
        catReset();
    };

    const onCatSubmit = (formData: CategoryForm) => {
        setCatServerError(null);
        const cb = {
            onSuccess: () => { closeCatDialog(); router.reload({ only: ['sports'] }); },
            onError: (err: Record<string, string>) => setCatServerError(Object.values(err).join(', ')),
        };
        if (editingCat) {
            router.put(route('sport-categories.update', editingCat.id), { ...formData, sport_id: catSportId }, cb);
        } else {
            router.post(route('sport-categories.store'), { ...formData, sport_id: catSportId }, cb);
        }
    };

    const handleCatDelete = () => {
        if (!deleteCat) return;
        router.delete(route('sport-categories.destroy', deleteCat.id), {
            preserveScroll: true,
            onSuccess: () => { setDeleteCat(null); router.reload({ only: ['sports'] }); },
        });
    };

    const toggleExpand = (id: string) => {
        setExpandedId(expandedId === id ? null : id);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Sports</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage sports and their categories
                        </p>
                    </div>

                    {isSuperAdmin && (
                        <Button onClick={openCreate}>
                            <Plus className="mr-2 size-4" />
                            Add Sport
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Sports" />

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
                    <CardTitle>Sports List</CardTitle>
                    <CardDescription>
                        Click a sport to view and manage its categories. Categories define the competition divisions
                        (e.g. Men's Singles, Women's Team).
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-8" />
                                <TableHead>Name</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Categories</TableHead>
                                <TableHead>Status</TableHead>
                                {isSuperAdmin && <TableHead className="text-right">Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sports.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={isSuperAdmin ? 6 : 5} className="text-center text-muted-foreground">
                                        No sports yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {sports.map((sport) => (
                                <Fragment key={sport.id}>
                                    <TableRow className="cursor-pointer" onClick={() => toggleExpand(sport.id)}>
                                        <TableCell>
                                            {expandedId === sport.id
                                                ? <ChevronDown className="size-4 text-muted-foreground" />
                                                : <ChevronRight className="size-4 text-muted-foreground" />}
                                        </TableCell>
                                        <TableCell className="font-medium">{sport.name}</TableCell>
                                        <TableCell>
                                            <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{sport.slug}</code>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary" className="text-xs">
                                                {(sport.categories?.length ?? 0)} categories
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <span
                                                className={
                                                    sport.is_active
                                                        ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700'
                                                        : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600'
                                                }
                                            >
                                                {sport.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </TableCell>
                                        {isSuperAdmin && (
                                        <TableCell className="text-right space-x-2">
                                            <Button variant="outline" size="sm" onClick={(e) => { e.stopPropagation(); openEdit(sport); }}>
                                                <Pencil className="mr-1 size-3" /> Edit
                                            </Button>
                                            <Button variant="destructive" size="sm" onClick={(e) => { e.stopPropagation(); setDeleteSport(sport); }}>
                                                <Trash2 className="mr-1 size-3" /> Delete
                                            </Button>
                                        </TableCell>
                                        )}
                                    </TableRow>
                                    {expandedId === sport.id && (
                                        <TableRow>
                                            <TableCell colSpan={isSuperAdmin ? 6 : 5} className="bg-muted/30 p-0">
                                                <div className="px-6 py-4">
                                                    <div className="flex items-center justify-between mb-3">
                                                        <h4 className="text-sm font-medium">Categories</h4>
                                                        {isSuperAdmin && (
                                                        <Button size="sm" variant="outline" onClick={() => openCatCreate(sport.id)}>
                                                            <Plus className="mr-1 size-3" /> Add Category
                                                        </Button>
                                                        )}
                                                    </div>
                                                    {(sport.categories?.length ?? 0) === 0 ? (
                                                        <p className="text-sm text-muted-foreground">No categories yet for this sport.</p>
                                                    ) : (
                                                        <div className="space-y-1">
                                                            {sport.categories?.map((cat) => (
                                                                <div key={cat.id} className="flex items-center justify-between rounded border bg-background px-3 py-2 text-sm">
                                                                    <div>
                                                                        <span className="font-medium">{cat.name}</span>
                                                                        <code className="ml-2 rounded bg-muted px-1.5 py-0.5 text-xs">{cat.slug}</code>
                                                                        <span className="ml-2 text-xs text-muted-foreground">
                                                                            {categoryQuotaSummary(cat)}
                                                                        </span>
                                                                    </div>
                                                                    {isSuperAdmin && (
                                                                    <div className="space-x-1">
                                                                        <Button variant="ghost" size="sm" onClick={() => openCatEdit(cat)}>
                                                                            <Pencil className="size-3" />
                                                                        </Button>
                                                                        <Button variant="ghost" size="sm" className="text-destructive" onClick={() => setDeleteCat(cat)}>
                                                                            <Trash2 className="size-3" />
                                                                        </Button>
                                                                    </div>
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </Fragment>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>

                <Pagination paginator={sportsProp} />
            </Card>

            {isSuperAdmin && (
                <>
            {/* ── Sport CRUD dialogs ── */}
            <Dialog open={open} onOpenChange={(isOpen) => { if (!isOpen) closeDialog(); }}>
                <DialogContent>
                    <form onSubmit={handleSubmit(onSubmit)}>
                        <DialogHeader>
                            <DialogTitle>{editingSport ? 'Edit Sport' : 'Create New Sport'}</DialogTitle>
                            <DialogDescription>
                                {editingSport ? 'Update sport information.' : 'Sports are the foundation for categories and events.'}
                            </DialogDescription>
                        </DialogHeader>

                        {serverError && (
                            <div className="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                                {serverError}
                            </div>
                        )}

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Sport Name</Label>
                                <Input id="name" {...register('name')} placeholder="e.g. Badminton" required />
                                {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug (unique)</Label>
                                <Input
                                    id="slug" {...register('slug')} placeholder="badminton" required
                                    onChange={(e) => { autoSlugRef.current = false; register('slug').onChange(e); }}
                                />
                                {errors.slug && <p className="text-sm text-destructive">{errors.slug.message}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="icon">Icon (Lucide name, optional)</Label>
                                <Input id="icon" {...register('icon')} placeholder="trophy or badminton" />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="is_active">Status</Label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" {...register('is_active')} /> Active
                                </label>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeDialog}>Cancel</Button>
                            <Button type="submit" disabled={isSubmitting}>
                                <Save className="mr-2 size-4" /> {editingSport ? 'Update' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!deleteSport} onOpenChange={(isOpen) => !isOpen && setDeleteSport(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Sport?</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{deleteSport?.name}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteSport(null)}>Cancel</Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>Yes, Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Category CRUD dialogs ── */}
            <Dialog open={catOpen} onOpenChange={(isOpen) => { if (!isOpen) closeCatDialog(); }}>
                <DialogContent className="max-h-[90vh] overflow-y-auto">
                    <form onSubmit={catSubmit(onCatSubmit)}>
                        <DialogHeader>
                            <DialogTitle>{editingCat ? 'Edit Category' : 'Add Category'}</DialogTitle>
                            <DialogDescription>
                                {sports.find(s => s.id === catSportId)?.name
                                    ? `Category for ${sports.find(s => s.id === catSportId)!.name}`
                                    : 'Create a new category under this sport'}
                            </DialogDescription>
                        </DialogHeader>

                        {catServerError && (
                            <div className="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                                {catServerError}
                            </div>
                        )}

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="cat-name">Category Name</Label>
                                <Input id="cat-name" {...catReg('name')} placeholder="e.g. Men's Singles" required />
                                {catErrors.name && <p className="text-sm text-destructive">{catErrors.name.message}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="cat-slug">Slug (unique per sport)</Label>
                                <Input id="cat-slug" {...catReg('slug')} placeholder="mens-singles" />
                                {catErrors.slug && <p className="text-sm text-destructive">{catErrors.slug.message}</p>}
                            </div>

                            <div className="border-t pt-4">
                                <h4 className="mb-3 text-sm font-medium">Quota / Participant Limits</h4>
                                <div className="mb-3 grid gap-2">
                                    <Label htmlFor="cat-quota-mode">Quota Mode</Label>
                                    <select
                                        id="cat-quota-mode"
                                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                        {...catReg('quota_mode')}
                                    >
                                        <option value="gender_based">Gender based</option>
                                        <option value="open_total">Open total</option>
                                        <option value="mixed_total">Mixed total with minimums</option>
                                    </select>
                                </div>
                                <div className="grid grid-cols-3 gap-3">
                                    {catQuotaMode !== 'gender_based' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="cat-max-total">Max Total Athletes</Label>
                                        <Input
                                            id="cat-max-total"
                                            type="number"
                                            min="0"
                                            {...catReg('max_athletes_total')}
                                            placeholder="e.g. 6"
                                        />
                                        {catErrors.max_athletes_total && <p className="text-sm text-destructive">{catErrors.max_athletes_total.message}</p>}
                                    </div>
                                    )}
                                    <div className="grid gap-2">
                                        <Label htmlFor="cat-max-male">Max Male Athletes</Label>
                                        <Input id="cat-max-male" type="number" min="0" {...catReg('max_male_athletes')} placeholder="e.g. 12" />
                                        {catErrors.max_male_athletes && <p className="text-sm text-destructive">{catErrors.max_male_athletes.message}</p>}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="cat-max-female">Max Female Athletes</Label>
                                        <Input id="cat-max-female" type="number" min="0" {...catReg('max_female_athletes')} placeholder="e.g. 12" />
                                        {catErrors.max_female_athletes && <p className="text-sm text-destructive">{catErrors.max_female_athletes.message}</p>}
                                    </div>
                                    {catQuotaMode === 'mixed_total' && (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="cat-min-male">Min Male Athletes</Label>
                                            <Input
                                                id="cat-min-male"
                                                type="number"
                                                min="0"
                                                {...catReg('min_male_athletes')}
                                                placeholder="e.g. 2"
                                            />
                                            {catErrors.min_male_athletes && <p className="text-sm text-destructive">{catErrors.min_male_athletes.message}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="cat-min-female">Min Female Athletes</Label>
                                            <Input
                                                id="cat-min-female"
                                                type="number"
                                                min="0"
                                                {...catReg('min_female_athletes')}
                                                placeholder="e.g. 2"
                                            />
                                            {catErrors.min_female_athletes && <p className="text-sm text-destructive">{catErrors.min_female_athletes.message}</p>}
                                        </div>
                                    </>
                                    )}
                                    <div className="grid gap-2">
                                        <Label htmlFor="cat-max-officials">Max Officials</Label>
                                        <Input id="cat-max-officials" type="number" min="0" {...catReg('max_officials')} placeholder="e.g. 5" />
                                        {catErrors.max_officials && <p className="text-sm text-destructive">{catErrors.max_officials.message}</p>}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeCatDialog}>Cancel</Button>
                            <Button type="submit" disabled={catSubmitting}>
                                <Save className="mr-2 size-4" /> {editingCat ? 'Update' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!deleteCat} onOpenChange={(isOpen) => !isOpen && setDeleteCat(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Category?</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{deleteCat?.name}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteCat(null)}>Cancel</Button>
                        <Button variant="destructive" onClick={handleCatDelete} disabled={catSubmitting}>Yes, Delete</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            </>
            )}

            <div className="mt-6 text-xs text-muted-foreground">
                M2: Sports with inline category management. Use the Categories menu for full category listing.
            </div>
        </AuthenticatedLayout>
    );
}
