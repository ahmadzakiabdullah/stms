export type ToastVariant = 'success' | 'error' | 'info';

export interface ToastItem {
    id: number;
    title: string;
    description?: string;
    variant: ToastVariant;
}

type Listener = () => void;

let items: ToastItem[] = [];
const listeners = new Set<Listener>();
let sequence = 0;

function emit(): void {
    listeners.forEach((listener) => listener());
}

export function subscribe(listener: Listener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function getSnapshot(): ToastItem[] {
    return items;
}

export function dismiss(id: number): void {
    items = items.filter((item) => item.id !== id);
    emit();
}

function push(variant: ToastVariant, title: string, description?: string, duration = 4500): number {
    const id = ++sequence;
    items = [...items, { id, title, description, variant }];
    emit();

    if (duration > 0) {
        setTimeout(() => dismiss(id), duration);
    }

    return id;
}

export const toast = {
    success: (title: string, description?: string): number => push('success', title, description),
    error: (title: string, description?: string): number => push('error', title, description),
    info: (title: string, description?: string): number => push('info', title, description),
    dismiss,
};
