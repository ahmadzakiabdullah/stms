interface ZiggyRoute {
    (): ZiggyRoute;
    (name: string, params?: unknown, absolute?: boolean): string;
    current(name?: string): boolean | string | undefined;
}

declare const route: ZiggyRoute;

declare module '@inertiajs/core' {
    interface PageProps {
        auth?: any;
        flash?: { success?: string; error?: string };
        errors?: Record<string, string>;
    }
}

declare module '@/components/Checkbox' { const component: React.ComponentType<any>; export default component; }
declare module '@/components/InputError' { const component: React.ComponentType<any>; export default component; }
declare module '@/components/InputLabel' { const component: React.ComponentType<any>; export default component; }
declare module '@/components/PrimaryButton' { const component: React.ComponentType<any>; export default component; }
declare module '@/components/TextInput' { const component: React.ComponentType<any>; export default component; }
declare module '@/Layouts/GuestLayout' { const component: React.ComponentType<any>; export default component; }

declare module '*.jsx' {
    const component: React.ComponentType<Record<string, unknown>>;
    export default component;
}

declare module '@/components/ui/*' {
    export const Avatar: React.ComponentType<any>;
    export const AvatarFallback: React.ComponentType<any>;
    export const AvatarImage: React.ComponentType<any>;
    export const Badge: React.ComponentType<any>;
    export const Button: React.ComponentType<any>;
    export const Card: React.ComponentType<any>;
    export const CardContent: React.ComponentType<any>;
    export const CardDescription: React.ComponentType<any>;
    export const CardFooter: React.ComponentType<any>;
    export const CardHeader: React.ComponentType<any>;
    export const CardTitle: React.ComponentType<any>;
    export const Dialog: React.ComponentType<any>;
    export const DialogClose: React.ComponentType<any>;
    export const DialogContent: React.ComponentType<any>;
    export const DialogDescription: React.ComponentType<any>;
    export const DialogFooter: React.ComponentType<any>;
    export const DialogHeader: React.ComponentType<any>;
    export const DialogTitle: React.ComponentType<any>;
    export const DialogTrigger: React.ComponentType<any>;
    export const DropdownMenu: React.ComponentType<any>;
    export const DropdownMenuContent: React.ComponentType<any>;
    export const DropdownMenuItem: React.ComponentType<any>;
    export const DropdownMenuLabel: React.ComponentType<any>;
    export const DropdownMenuSeparator: React.ComponentType<any>;
    export const DropdownMenuTrigger: React.ComponentType<any>;
    export const Input: React.ComponentType<any>;
    export const Label: React.ComponentType<any>;
    export const Separator: React.ComponentType<any>;
    export const Sheet: React.ComponentType<any>;
    export const SheetContent: React.ComponentType<any>;
    export const SheetDescription: React.ComponentType<any>;
    export const SheetHeader: React.ComponentType<any>;
    export const SheetTitle: React.ComponentType<any>;
    export const SheetTrigger: React.ComponentType<any>;
    export const Table: React.ComponentType<any>;
    export const TableBody: React.ComponentType<any>;
    export const TableCaption: React.ComponentType<any>;
    export const TableCell: React.ComponentType<any>;
    export const TableFooter: React.ComponentType<any>;
    export const TableHead: React.ComponentType<any>;
    export const TableHeader: React.ComponentType<any>;
    export const TableRow: React.ComponentType<any>;
}
