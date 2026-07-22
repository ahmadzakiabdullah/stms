import { Head, Link, usePage } from '@inertiajs/react';
import { Trophy, CalendarCheck, Users, ListChecks, Target, Award, LogIn, ArrowRight } from 'lucide-react';
import type { PageProps } from '@/types';

export default function Welcome() {
    const { auth, app } = usePage<PageProps>().props;
    const user = auth?.user;

    return (
        <>
            <Head title="Welcome" />

            <div className="flex min-h-screen flex-col bg-gradient-to-br from-background via-background to-primary/5">
                <header className="flex items-center justify-between px-6 py-4">
                    <div className="flex items-center gap-2">
                        <div className="flex size-9 items-center justify-center rounded-lg bg-primary/10">
                            <Trophy className="size-5 text-primary" />
                        </div>
                        <span className="text-lg font-semibold tracking-tight">{app?.name || 'SAF'}</span>
                    </div>
                    <nav className="flex items-center gap-3">
                        {user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-flex h-9 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
                            >
                                Dashboard
                                <ArrowRight className="size-3.5" />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('register')}
                                    className="inline-flex h-9 items-center rounded-lg px-4 text-sm font-medium text-muted-foreground transition hover:text-foreground"
                                >
                                    Register
                                </Link>
                                <Link
                                    href={route('login')}
                                    className="inline-flex h-9 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
                                >
                                    <LogIn className="size-3.5" />
                                    Log in
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                <main className="flex flex-1 flex-col items-center justify-center px-6">
                    <div className="mx-auto max-w-2xl text-center">
                        <div className="mx-auto mb-6 flex size-16 items-center justify-center rounded-2xl bg-primary/10">
                            <Trophy className="size-8 text-primary" />
                        </div>

                        <h1 className="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">
                            {app?.name || 'SAF'} Portal
                        </h1>

                        <p className="mt-4 text-lg text-muted-foreground">
                            Faculty Sports Tournament Management System
                        </p>

                        <p className="mx-auto mt-3 max-w-md text-sm text-muted-foreground">
                            Manage multi-sport tournaments, track faculty registrations, schedule matches, and generate rankings — all in one platform.
                        </p>

                        {!user && (
                            <div className="mt-8 flex items-center justify-center gap-3">
                                <Link
                                    href={route('login')}
                                    className="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-6 text-sm font-medium text-primary-foreground shadow transition hover:bg-primary/90"
                                >
                                    <LogIn className="size-4" />
                                    Log in to get started
                                </Link>
                            </div>
                        )}
                    </div>

                    <div className="mt-16 grid w-full max-w-4xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {[
                            { icon: ListChecks, title: 'Event Registration', desc: 'Faculties register for sports events with a simple workflow' },
                            { icon: Users, title: 'Faculty Management', desc: 'Manage faculty representatives and participant records' },
                            { icon: Award, title: 'Multi-Sport Support', desc: 'Configure any sport with custom categories and rules' },
                            { icon: Target, title: 'Match Scheduling', desc: 'Schedule fixtures and record results in real-time' },
                            { icon: Trophy, title: 'Auto Rankings', desc: 'Rankings computed automatically from match results' },
                            { icon: CalendarCheck, title: 'Session Planning', desc: 'Organise tournaments into sessions and cycles' },
                        ].map((f) => {
                            const Icon = f.icon;
                            return (
                                <div key={f.title} className="rounded-lg border bg-card p-4 text-card-foreground shadow-sm">
                                    <div className="flex size-9 items-center justify-center rounded-lg bg-muted">
                                        <Icon className="size-4.5 text-muted-foreground" />
                                    </div>
                                    <h3 className="mt-3 text-sm font-semibold">{f.title}</h3>
                                    <p className="mt-1 text-xs text-muted-foreground">{f.desc}</p>
                                </div>
                            );
                        })}
                    </div>
                </main>

                <footer className="border-t px-6 py-6 text-center text-xs text-muted-foreground">
                    &copy; {new Date().getFullYear()} Universiti Teknikal Malaysia Melaka (UTeM). All rights reserved.
                </footer>
            </div>
        </>
    );
}
