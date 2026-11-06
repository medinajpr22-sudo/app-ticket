import Avatar from '@/Components/ui/Avatar';
import Icon from '@/Components/ui/Icon';
import Logo from '@/Components/ui/Logo';
import { classNames } from '@/lib/classNames';
import { User } from '@/types';
import { Link } from '@inertiajs/react';
import { NavigationSection } from '../navigation';

export default function Sidebar({
    sections,
    user,
    isOpen,
}: {
    sections: NavigationSection[];
    user: User;
    isOpen: boolean;
}) {
    return (
        <aside
            id="sidebar"
            className={classNames(
                'fixed inset-y-0 left-0 z-40 w-[292px] p-3.5 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0',
                isOpen ? 'translate-x-0' : '-translate-x-full',
            )}
        >
            <div className="flex h-full flex-col gap-1 overflow-y-auto rounded-[26px] border border-white/20 bg-[linear-gradient(180deg,rgb(96_22_28/0.94),rgb(58_11_15/0.96))] p-3.5 text-white shadow-[0_18px_50px_rgb(58_11_15/0.35),inset_0_1px_0_rgb(255_255_255/0.2)]">
                <Logo className="mb-3.5 h-16 shrink-0" />

                <nav aria-label="Menú principal">
                    {sections.map((section) => (
                        <div key={section.label}>
                            <p className="mx-3 mt-3 mb-1 text-xs font-extrabold tracking-[0.12em] text-blush uppercase">
                                {section.label}
                            </p>
                            <ul>
                                {section.items.map((item) => {
                                    const isCurrent = route().current(item.activePattern);

                                    return (
                                        <li key={item.routeName}>
                                            <Link
                                                href={route(item.routeName)}
                                                aria-current={isCurrent ? 'page' : undefined}
                                                className={classNames(
                                                    'flex min-h-[46px] items-center gap-3 rounded-xl px-3.5 py-2.5 font-bold transition focus-visible:outline-white',
                                                    isCurrent
                                                        ? 'bg-white/95 font-extrabold text-wine'
                                                        : 'text-white hover:translate-x-0.5 hover:bg-white/12',
                                                )}
                                            >
                                                <Icon name={item.icon} />
                                                {item.label}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </nav>

                <div className="mt-auto flex items-center gap-3 rounded-2xl border border-white/15 bg-white/8 p-3">
                    <Avatar initials={user.initials} tone="light" />
                    <div className="min-w-0 leading-tight">
                        <p className="truncate font-extrabold">{user.name}</p>
                        <p className="truncate text-sm text-blush">{user.role?.label}</p>
                    </div>
                </div>
            </div>
        </aside>
    );
}
