import { Head, Link, router } from '@inertiajs/react';

interface ErrorProps {
    status: number;
}

const statusMessages: Record<number, { title: string; description: string }> = {
    403: { title: 'Access Denied', description: 'You do not have permission to access this page.' },
    404: { title: 'Page Not Found', description: 'The page you are looking for does not exist.' },
    419: { title: 'Session Expired', description: 'Your session has expired. Please refresh the page.' },
    429: { title: 'Too Many Requests', description: 'Please slow down and try again later.' },
    500: { title: 'Server Error', description: 'Something went wrong on our end. Please try again later.' },
};

export default function Error({ status }: ErrorProps) {
    const { title, description } = statusMessages[status] ?? {
        title: 'Error',
        description: 'An unexpected error occurred.',
    };

    return (
        <>
            <Head title={`${status} - ${title}`} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-background px-4">
                <div className="text-center">
                    <h1 className="text-6xl font-bold text-muted-foreground">{status}</h1>
                    <h2 className="mt-4 text-2xl font-semibold">{title}</h2>
                    <p className="mt-2 text-muted-foreground">{description}</p>
                    <div className="mt-6 flex gap-3 justify-center">
                        <Link
                            href={route('dashboard')}
                            className="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90"
                        >
                            Go to Dashboard
                        </Link>
                        <button
                            onClick={() => router.reload()}
                            className="inline-flex items-center rounded-md border border-input bg-background px-4 py-2 text-sm hover:bg-accent"
                        >
                            Try Again
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
