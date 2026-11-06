import FlashToast from '@/Components/ui/FlashToast';
import Icon from '@/Components/ui/Icon';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { PropsWithChildren, useEffect, useState } from 'react';
import { navigation } from './navigation';
import BackgroundOrbs from './Partials/BackgroundOrbs';
import Sidebar from './Partials/Sidebar';
import UserMenu from './Partials/UserMenu';

export default function AppLayout({
    title,
    children,
}: PropsWithChildren<{ title: string }>) {
    const { auth } = usePage<PageProps>().props;
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    useEffect(() => {
        if (!isMenuOpen) {
            return;
        }

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsMenuOpen(false);
            }
        };

        document.addEventListener('keydown', closeOnEscape);

        return () => document.removeEventListener('keydown', closeOnEscape);
    }, [isMenuOpen]);

    const sections = navigation
        .map((section) => ({
            ...section,
            items: section.items.filter(
                (item) => !item.permission || auth.permissions.includes(item.permission),
            ),
        }))
        .filter((section) => section.items.length > 0);

    return (
        <>
            <Head title={title} />

            <a
                href="#main"
                className="fixed top-3 left-3 z-50 -translate-y-24 rounded-xl bg-white px-4 py-3 font-extrabold focus:translate-y-0"
            >
                Saltar al contenido
            </a>

            <BackgroundOrbs />

            <div className="min-h-screen lg:grid lg:grid-cols-[292px_minmax(0,1fr)]">
                <Sidebar sections={sections} user={auth.user} isOpen={isMenuOpen} />

                {isMenuOpen && (
                    <div
                        aria-hidden="true"
                        className="fixed inset-0 z-30 bg-[rgb(30_6_9/0.45)] lg:hidden"
                        onClick={() => setIsMenuOpen(false)}
                    />
                )}

                <div className="min-w-0">
                    <header className="glass sticky top-3 z-20 mx-4 mt-3 flex items-center gap-3 rounded-[20px] px-3.5 py-2 lg:mx-8">
                        <button
                            type="button"
                            className="grid size-12 place-items-center rounded-full border-[1.5px] border-line bg-white/85 lg:hidden"
                            aria-controls="sidebar"
                            aria-expanded={isMenuOpen}
                            onClick={() => setIsMenuOpen((isOpen) => !isOpen)}
                        >
                            <Icon name={isMenuOpen ? 'x' : 'menu'} size={22} />
                            <span className="sr-only">Menú principal</span>
                        </button>
                        <div className="flex-1" />
                        <UserMenu user={auth.user} />
                    </header>

                    <main id="main" tabIndex={-1} className="px-4 pt-7 pb-20 lg:px-8">
                        {children}
                    </main>
                </div>
            </div>

            <FlashToast />
        </>
    );
}
