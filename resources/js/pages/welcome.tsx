import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Banknote, Bell, Gavel, ReceiptText, ShieldCheck, Smartphone, Wallet } from 'lucide-react';

const features = [
    { icon: Wallet, title: 'Chitty management', body: 'Create schemes, run chitties, enroll subscribers and track every ticket through its lifecycle.' },
    { icon: ReceiptText, title: 'Payments & receipts', body: 'Online and offline collections with reconciliation, late-fee rules and instant PDF receipts.' },
    { icon: Gavel, title: 'Live auctions', body: 'Real-time, server-verified bidding with automatic prize, commission and dividend calculation.' },
    { icon: Bell, title: 'Smart notifications', body: 'In-app, email, WhatsApp and push reminders for dues, auctions, KYC and payments.' },
    { icon: ShieldCheck, title: 'Secure & compliant', body: 'Role-based access, KYC workflows, audit logs and maker-checker approvals for sensitive actions.' },
    { icon: Smartphone, title: 'Mobile app', body: 'A companion app for members to pay, bid and track chitties on the go.' },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="ChittyFund — Chit Fund Management">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
            </Head>

            <div className="min-h-screen bg-background text-foreground">
                {/* Nav */}
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5">
                    <div className="flex items-center gap-2 font-semibold">
                        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <Banknote className="h-5 w-5" />
                        </span>
                        <span className="text-lg">ChittyFund</span>
                    </div>
                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="rounded-md px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                {/* Hero */}
                <section className="relative overflow-hidden">
                    <div className="pointer-events-none absolute inset-0 -z-10 opacity-60 [background:radial-gradient(60%_60%_at_50%_0%,color-mix(in_oklab,var(--color-primary)_18%,transparent),transparent)]" />
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center px-6 py-20 text-center lg:py-28">
                        <span className="mb-4 inline-flex items-center rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-muted-foreground">
                            Secure • Compliant • Real-time
                        </span>
                        <h1 className="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                            Run your chit fund with confidence
                        </h1>
                        <p className="mt-5 max-w-2xl text-base text-muted-foreground sm:text-lg">
                            A complete platform to manage chitties, subscribers, KYC, installments, payments and live
                            auctions — with role-based access, audit trails and reliable notifications.
                        </p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                                >
                                    Go to dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('register')}
                                        className="rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                                    >
                                        Get started
                                    </Link>
                                    <Link
                                        href={route('login')}
                                        className="rounded-lg border border-border bg-card px-6 py-3 text-sm font-semibold transition hover:bg-muted"
                                    >
                                        Log in
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="mx-auto w-full max-w-6xl px-6 pb-24">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {features.map((f) => (
                            <div
                                key={f.title}
                                className="rounded-xl border border-border bg-card p-6 transition hover:border-primary/40"
                            >
                                <span className="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <f.icon className="h-5 w-5" />
                                </span>
                                <h3 className="mb-1 font-semibold">{f.title}</h3>
                                <p className="text-sm text-muted-foreground">{f.body}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-border">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-2 px-6 py-6 text-sm text-muted-foreground sm:flex-row">
                        <span>© {new Date().getFullYear()} ChittyFund. All rights reserved.</span>
                        <span>Built for compliant, secure chit fund operations.</span>
                    </div>
                </footer>
            </div>
        </>
    );
}
