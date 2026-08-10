import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    CalendarCheck,
    LayoutDashboard,
    ListChecks,
    LogIn,
    Menu,
    ShieldCheck,
    Sparkles,
    Target,
    Trophy,
    Users,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { PageProps } from '@/types';
import LocaleSwitcher from '@/components/LocaleSwitcher';
import { useI18n } from '@/lib/i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const features = [
    {
        icon: ListChecks,
        title: 'Event Registration',
        desc: 'Faculties register for sports events with a simple, streamlined workflow from start to finish.',
    },
    {
        icon: Users,
        title: 'Faculty Management',
        desc: 'Manage faculty representatives, squad members, and participant records in one place.',
    },
    {
        icon: Award,
        title: 'Multi-Sport Support',
        desc: 'Configure any sport with custom categories and rules — nothing hardcoded, everything configurable.',
    },
    {
        icon: Target,
        title: 'Match Scheduling',
        desc: 'Schedule fixtures, track pools, and record results in real-time as the tournament unfolds.',
    },
    {
        icon: Trophy,
        title: 'Auto Rankings',
        desc: 'Rankings computed automatically from match results using configurable strategies.',
    },
    {
        icon: CalendarCheck,
        title: 'Session Planning',
        desc: 'Organise tournaments into sessions and cycles for clean, manageable event structures.',
    },
];

const steps = [
    {
        step: '01',
        title: 'Register your faculty',
        desc: 'Create an account and register your faculty as a tournament participant in seconds.',
    },
    {
        step: '02',
        title: 'Build your squads',
        desc: 'Add athletes and officials to each event, and submit them for verification.',
    },
    {
        step: '03',
        title: 'Compete & rank',
        desc: 'Follow fixtures, enter results, and watch rankings update automatically.',
    },
];

export default function Welcome() {
    const { t } = useI18n();
    const { auth, app, settings = {} } = usePage<PageProps>().props;
    const user = auth?.user;
    const logoUrl = (settings as Record<string, string>)?.logo_url;
    const [mobileOpen, setMobileOpen] = useState(false);

    const navLinks = [
        { href: '#features', label: t('Features') },
        { href: '#how-it-works', label: t('How it works') },
        { href: '#about', label: t('About') },
    ];

    return (
        <>
            <Head title={t('Welcome')} />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                {/* Header */}
                <header className="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur-md">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                        <Link href="/" className="flex items-center gap-2.5">
                            {logoUrl ? (
                                <img
                                    src={logoUrl}
                                    alt={`${app?.name || 'STMS'} logo`}
                                    className="size-10 rounded-lg object-contain"
                                />
                            ) : (
                                <div className="flex size-9 items-center justify-center rounded-xl bg-primary shadow-sm">
                                    <Trophy className="size-5 text-primary-foreground" />
                                </div>
                            )}
                            <div className="leading-tight">
                                <span className="block text-sm font-semibold tracking-tight">
                                    {app?.name || 'SAF'}
                                </span>
                                <span className="block text-[11px] text-muted-foreground">
                                    {t('Tournament Portal')}
                                </span>
                            </div>
                        </Link>

                        <nav className="hidden items-center gap-1 md:flex">
                            {navLinks.map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    className="rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                >
                                    {link.label}
                                </a>
                            ))}
                        </nav>

                        <div className="hidden items-center gap-2 md:flex">
                            {user ? (
                                <Button asChild>
                                    <Link href={route('dashboard')}>
                                        {t('Dashboard')}
                                        <LayoutDashboard data-icon="inline-end" />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={route('register')}>{t('Register')}</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={route('login')}>
                                            <LogIn data-icon="inline-start" />
                                            {t('Log in')}
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>

                        <div className="hidden md:block"><LocaleSwitcher compact showLabel={false} /></div>

                        <button
                            type="button"
                            onClick={() => setMobileOpen(!mobileOpen)}
                            className="inline-flex size-9 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:text-foreground md:hidden"
                            aria-label={mobileOpen ? 'Close menu' : 'Open menu'}
                        >
                            {mobileOpen ? <X className="size-4" /> : <Menu className="size-4" />}
                        </button>
                    </div>

                    {mobileOpen && (
                        <div className="border-t border-border/60 bg-background px-4 py-4 md:hidden">
                            <nav className="flex flex-col gap-1">
                                {navLinks.map((link) => (
                                    <a
                                        key={link.href}
                                        href={link.href}
                                        onClick={() => setMobileOpen(false)}
                                        className="rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                    >
                                        {link.label}
                                    </a>
                                ))}
                                <div className="mt-3 flex flex-col gap-2">
                                    {user ? (
                                        <Button asChild>
                                            <Link href={route('dashboard')}>
                                                Dashboard
                                                <ArrowRight data-icon="inline-end" />
                                            </Link>
                                        </Button>
                                    ) : (
                                        <>
                                            <Button variant="outline" asChild>
                                                <Link href={route('register')}>Register</Link>
                                            </Button>
                                            <Button asChild>
                                                <Link href={route('login')}>Log in</Link>
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </nav>
                        </div>
                    )}
                </header>

                {/* Hero */}
                <section className="relative overflow-hidden">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,var(--primary)/6%,transparent_55%)]"
                    />
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center px-4 pb-20 pt-16 text-center sm:px-6 sm:pt-24">
                        <Badge className="mb-6 gap-1.5 py-1 pl-1.5 pr-3">
                            <Sparkles className="size-3 text-primary-foreground" />
                            Faculty Sports Tournament Management
                        </Badge>

                        <h1 className="max-w-3xl text-balance text-4xl font-bold tracking-tight sm:text-6xl">
                            One platform for{' '}
                            <span className="bg-gradient-to-r from-primary via-primary to-muted-foreground bg-clip-text text-transparent">
                                every tournament
                            </span>
                        </h1>

                        <p className="mt-6 max-w-xl text-pretty text-base leading-relaxed text-muted-foreground sm:text-lg">
                            Manage multi-sport tournaments, track faculty registrations, schedule
                            matches, and generate rankings — all in one platform.
                        </p>

                        <div className="mt-10 flex flex-col items-center gap-3 sm:flex-row">
                            {user ? (
                                <Button size="lg" asChild>
                                    <Link href={route('dashboard')}>
                                        Go to dashboard
                                        <ArrowRight data-icon="inline-end" />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button size="lg" asChild>
                                        <Link href={route('login')}>
                                            Log in to get started
                                            <ArrowRight data-icon="inline-end" />
                                        </Link>
                                    </Button>
                                    <Button size="lg" variant="outline" asChild>
                                        <Link href="#features">Explore features</Link>
                                    </Button>
                                </>
                            )}
                        </div>

                        <div className="mt-14 grid w-full max-w-3xl grid-cols-1 gap-4 sm:grid-cols-3">
                            {[
                                { icon: Trophy, value: 'Multi-sport', label: 'Any sport, any scale' },
                                { icon: ShieldCheck, value: 'Secure', label: 'Tenant-isolated data' },
                                { icon: Award, value: 'Automatic', label: 'Rankings & standings' },
                            ].map((stat) => {
                                const Icon = stat.icon;
                                return (
                                    <Card key={stat.label} className="gap-1 py-4">
                                        <CardContent className="flex flex-col items-center gap-1 px-4 text-center">
                                            <Icon className="mb-1 size-5 text-primary" />
                                            <span className="text-sm font-semibold">{stat.value}</span>
                                            <span className="text-xs text-muted-foreground">{stat.label}</span>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section id="features" className="border-t border-border/60 bg-muted/30 py-20">
                    <div className="mx-auto w-full max-w-6xl px-4 sm:px-6">
                        <div className="mx-auto max-w-2xl text-center">
                            <Badge variant="outline" className="mb-4">Features</Badge>
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Everything you need to run a tournament
                            </h2>
                            <p className="mt-4 text-pretty text-muted-foreground">
                                From registration to final rankings, the portal covers the complete
                                tournament lifecycle for faculties and organisers.
                            </p>
                        </div>

                        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature) => {
                                const Icon = feature.icon;
                                return (
                                    <Card
                                        key={feature.title}
                                        className="group gap-3 py-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:ring-primary/30"
                                    >
                                        <CardHeader className="flex-row items-center gap-3 px-5">
                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                                <Icon className="size-5 text-primary transition-colors group-hover:text-primary-foreground" />
                                            </div>
                                            <CardTitle className="text-sm font-semibold">{feature.title}</CardTitle>
                                        </CardHeader>
                                        <CardContent className="px-5 pt-0">
                                            <CardDescription className="text-[13px] leading-relaxed">
                                                {feature.desc}
                                            </CardDescription>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section id="how-it-works" className="py-20">
                    <div className="mx-auto w-full max-w-6xl px-4 sm:px-6">
                        <div className="mx-auto max-w-2xl text-center">
                            <Badge variant="outline" className="mb-4">How it works</Badge>
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                From registration to podium in three steps
                            </h2>
                        </div>

                        <div className="mt-12 grid gap-6 md:grid-cols-3">
                            {steps.map((step, i) => (
                                <div key={step.step} className="relative flex flex-col items-start gap-3">
                                    {i < steps.length - 1 && (
                                        <div
                                            aria-hidden="true"
                                            className="absolute left-6 top-6 hidden h-px w-[calc(100%-2rem)] border-t border-dashed border-border md:block"
                                        />
                                    )}
                                    <div className="relative flex size-12 items-center justify-center rounded-xl bg-primary font-mono text-sm font-semibold text-primary-foreground shadow-sm">
                                        {step.step}
                                    </div>
                                    <h3 className="mt-1 text-base font-semibold">{step.title}</h3>
                                    <p className="text-sm leading-relaxed text-muted-foreground">{step.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                {!user && (
                    <section id="cta" className="px-4 pb-20 sm:px-6">
                        <div className="mx-auto flex w-full max-w-6xl flex-col items-center gap-6 rounded-2xl bg-primary px-6 py-14 text-center text-primary-foreground sm:px-12">
                            <h2 className="max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">
                                Ready to manage your next tournament?
                            </h2>
                            <p className="max-w-xl text-pretty text-sm leading-relaxed text-primary-foreground/70 sm:text-base">
                                Join the platform and get your faculty ready for the next sporting
                                season — registration takes less than a minute.
                            </p>
                            <Button
                                size="lg"
                                variant="secondary"
                                className="bg-background text-foreground hover:bg-background/90"
                                asChild
                            >
                                <Link href={route('register')}>
                                    Create an account
                                    <ArrowRight data-icon="inline-end" />
                                </Link>
                            </Button>
                        </div>
                    </section>
                )}

                {/* Footer */}
                <footer id="about" className="border-t border-border/60 bg-muted/30">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-4 px-4 py-10 sm:flex-row sm:px-6">
                        <div className="flex items-center gap-2.5">
                            <div className="flex size-8 items-center justify-center rounded-lg bg-primary">
                                <Trophy className="size-4 text-primary-foreground" />
                            </div>
                            <span className="text-sm font-semibold">{app?.name || 'SAF'}</span>
                        </div>
                        <div className="flex items-center gap-6 text-xs text-muted-foreground">
                            <a href="#features" className="transition hover:text-foreground">Features</a>
                            <a href="#how-it-works" className="transition hover:text-foreground">How it works</a>
                            <span>&copy; {new Date().getFullYear()} Universiti Teknikal Malaysia Melaka (UTeM). All rights reserved.</span>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
