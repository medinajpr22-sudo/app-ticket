import Icon from '@/Components/ui/Icon';
import { classNames } from '@/lib/classNames';
import { formatRelative } from '@/lib/formatDate';
import { PageProps } from '@/types';
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/react';
import { router, usePage, usePoll } from '@inertiajs/react';

/**
 * How often the bell asks for news while the page is open.
 */
const POLL_INTERVAL_MS = 60_000;

/**
 * The bell in the top bar: how many notifications are unread and the latest
 * ones. Opening one marks it as read and leads to its ticket.
 */
export default function NotificationBell() {
    const { notifications } = usePage<PageProps>().props;

    usePoll(POLL_INTERVAL_MS, { only: ['notifications'] });

    if (!notifications) {
        return null;
    }

    const { unread_count: unreadCount, recent } = notifications;

    return (
        <Popover className="relative">
            <PopoverButton className="relative grid size-12 place-items-center rounded-full border-[1.5px] border-line bg-white/85 transition hover:border-crimson">
                <Icon name="bell" size={22} />
                {unreadCount > 0 && (
                    <span
                        aria-hidden="true"
                        className="absolute -top-1 -right-1 grid h-5 min-w-5 place-items-center rounded-full bg-crimson px-1 text-[11px] font-extrabold text-white ring-2 ring-white"
                    >
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
                <span className="sr-only">
                    {unreadCount > 0 ? `Notificaciones, ${unreadCount} sin leer` : 'Notificaciones'}
                </span>
            </PopoverButton>

            <PopoverPanel
                anchor={{ to: 'bottom end', gap: 8 }}
                className="z-50 w-[min(calc(100vw-2rem),380px)] rounded-[20px] border border-white/75 bg-white/97 shadow-[0_18px_50px_rgb(58_11_15/0.22)]"
            >
                <div className="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                    <h2 className="font-extrabold">Notificaciones</h2>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={() => router.delete(route('notifications.destroy'), { preserveScroll: true, only: ['notifications'] })}
                            className="text-sm font-bold text-wine underline-offset-2 hover:underline"
                        >
                            Marcar todas como leídas
                        </button>
                    )}
                </div>

                {recent.length === 0 ? (
                    <p className="px-4 py-8 text-center text-sm font-semibold text-muted">No tienes notificaciones.</p>
                ) : (
                    <ul className="max-h-[min(70vh,440px)] divide-y divide-line overflow-y-auto">
                        {recent.map((notification) => {
                            const isUnread = notification.read_at === null;

                            return (
                                <li key={notification.id}>
                                    <button
                                        type="button"
                                        onClick={() => router.patch(route('notifications.update', notification.id))}
                                        className={classNames(
                                            'flex w-full gap-3 px-4 py-3 text-left transition hover:bg-blush/40',
                                            isUnread && 'bg-blush/25',
                                        )}
                                    >
                                        <span
                                            aria-hidden="true"
                                            className={classNames(
                                                'mt-1.5 size-2.5 shrink-0 rounded-full',
                                                isUnread ? 'bg-crimson' : 'bg-transparent',
                                            )}
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className={classNames('block', isUnread ? 'font-extrabold' : 'font-bold')}>
                                                {notification.title}
                                                {isUnread && <span className="sr-only"> (sin leer)</span>}
                                            </span>
                                            <span className="block text-sm text-muted">{notification.body}</span>
                                            {notification.excerpt && (
                                                <span className="mt-1 line-clamp-2 block text-sm text-ink/80 italic">
                                                    “{notification.excerpt}”
                                                </span>
                                            )}
                                            <span className="mt-1 block text-xs font-bold text-muted">
                                                {formatRelative(notification.created_at)}
                                            </span>
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </PopoverPanel>
        </Popover>
    );
}
