import { Dialog, DialogContent } from '@/components/ui/dialog';

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}) {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[maxWidth];

    return (
        <Dialog open={show} onOpenChange={(open) => !open && close()}>
            <DialogContent
                id="modal"
                showCloseButton={closeable}
                className={`mb-6 max-h-[90vh] overflow-y-auto bg-white ${maxWidthClass}`}
            >
                {children}
            </DialogContent>
        </Dialog>
    );
}
