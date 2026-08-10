import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, router, usePage } from '@inertiajs/react';
import { Pencil, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useI18n } from '@/lib/i18n';

interface RoleData {
    id: number;
    name: string;
    guard_name: string;
    permissions: string[];
    created_at: string;
}

interface RolesIndexProps {
    roles: RoleData[];
    permissions: Record<string, string[]>;
}

export default function RolesIndex({ roles, permissions }: RolesIndexProps) {
    const { flash } = usePage().props;
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const [editingRole, setEditingRole] = useState<RoleData | null>(null);
    const [deleteRole, setDeleteRole] = useState<RoleData | null>(null);
    const [formName, setFormName] = useState('');
    const [selectedPerms, setSelectedPerms] = useState<Set<string>>(new Set());

    const openCreate = () => {
        setEditingRole(null);
        setFormName('');
        setSelectedPerms(new Set());
        setOpen(true);
    };

    const openEdit = (role: RoleData) => {
        setEditingRole(role);
        setFormName(role.name);
        setSelectedPerms(new Set(role.permissions));
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingRole(null);
        setFormName('');
        setSelectedPerms(new Set());
    };

    const togglePerm = (perm: string) => {
        setSelectedPerms(prev => {
            const next = new Set(prev);
            if (next.has(perm)) next.delete(perm);
            else next.add(perm);
            return next;
        });
    };

    const selectAllInGroup = (perms: string[], checked: boolean) => {
        setSelectedPerms(prev => {
            const next = new Set(prev);
            for (const p of perms) {
                if (checked) next.add(p);
                else next.delete(p);
            }
            return next;
        });
    };

    const handleSubmit = () => {
        if (!formName.trim()) return;
        const payload = { name: formName.trim(), permissions: Array.from(selectedPerms) };
        if (editingRole) {
            router.put(route('roles.update', editingRole.id), payload, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('roles.store'), payload, {
                onSuccess: () => closeDialog(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteRole) return;
        router.delete(route('roles.destroy', deleteRole.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteRole(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Role Management')}</h1>
                        <p className="text-sm text-muted-foreground">{t('Manage roles and their permissions')}</p>
                    </div>
                    <Dialog open={open} onOpenChange={(o) => { if (!o) closeDialog(); else setOpen(true); }}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>
                                <Plus className="mr-2 size-4" /> {t('Add Role')}
                            </Button>
                        </DialogTrigger>
                    </Dialog>
                </div>
            }
        >
            <Head title={t('Role Management')} />

            {flash?.success && <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>}
            {flash?.error && <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

            <Card>
                <CardHeader>
                    <CardTitle>{t('Roles List')}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Permissions')}</TableHead>
                                <TableHead>{t('Created')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.length === 0 && (
                                <TableRow><TableCell colSpan={4} className="text-center text-muted-foreground">{t('No roles yet.')}</TableCell></TableRow>
                            )}
                            {roles.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell className="font-medium">{role.name}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {role.permissions.slice(0, 5).map((p) => (
                                                <Badge key={p} variant="secondary" className="text-[10px]">{p}</Badge>
                                            ))}
                                            {role.permissions.length > 5 && (
                                                <Badge variant="outline" className="text-[10px]">+{role.permissions.length - 5}</Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {new Date(role.created_at).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button variant="outline" size="sm" onClick={() => openEdit(role)}>
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        {role.name !== 'super-admin' && (
                                            <Button variant="destructive" size="sm" onClick={() => setDeleteRole(role)}>
                                                <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog open={open} onOpenChange={(o) => { if (!o) closeDialog(); }}>
                <DialogContent className="max-w-xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{editingRole ? t('Edit Role') : t('Create Role')}</DialogTitle>
                        <DialogDescription>{t('Define the role name and assign permissions.')}</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="role-name">{t('Role Name')}</Label>
                            <Input
                                id="role-name"
                                value={formName}
                                onChange={(e) => setFormName(e.target.value)}
                                placeholder={t('e.g. sport-coordinator')}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label>{t('Permissions')}</Label>
                            <div className="max-h-80 overflow-y-auto rounded-md border divide-y">
                                {Object.entries(permissions).map(([group, perms]) => (
                                    <div key={group} className="px-3 py-2">
                                        <div className="flex items-center justify-between mb-1">
                                            <span className="text-xs font-semibold uppercase text-muted-foreground">{group}</span>
                                            <button
                                                type="button"
                                                onClick={() => selectAllInGroup(perms, !perms.every(p => selectedPerms.has(p)))}
                                                className="text-[10px] text-primary hover:underline"
                                            >
                                                {perms.every(p => selectedPerms.has(p)) ? t('Deselect all') : t('Select all')}
                                            </button>
                                        </div>
                                        <div className="flex flex-wrap gap-1.5">
                                            {perms.map((perm) => (
                                                <label
                                                    key={perm}
                                                    className={`inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs cursor-pointer transition ${
                                                        selectedPerms.has(perm)
                                                            ? 'bg-primary/10 border-primary/30 text-primary'
                                                            : 'hover:bg-muted'
                                                    }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedPerms.has(perm)}
                                                        onChange={() => togglePerm(perm)}
                                                        className="size-3"
                                                    />
                                                    {perm}
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={closeDialog}>{t('Cancel')}</Button>
                            <Button onClick={handleSubmit} disabled={!formName.trim()}>
                                <Save className="mr-2 size-4" /> {editingRole ? t('Update') : t('Save')}
                            </Button>
                        </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!deleteRole} onOpenChange={(o) => { if (!o) setDeleteRole(null); }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Role?')}</DialogTitle>
                        <DialogDescription>{t('Are you sure you want to delete')} <strong>{deleteRole?.name}</strong>? {t('Users with this role may lose access.')}</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteRole(null)}>{t('Cancel')}</Button>
                        <Button variant="destructive" onClick={handleDelete}>{t('Delete')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
