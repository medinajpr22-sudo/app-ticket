import Avatar from '@/Components/ui/Avatar';
import Icon from '@/Components/ui/Icon';
import { classNames } from '@/lib/classNames';
import { formatFileSize } from '@/lib/formatFileSize';
import { formatRelative } from '@/lib/formatDate';
import { TicketMessage } from '@/types';

/**
 * The ticket's messages in order. The viewer's own messages sit on the right;
 * internal notes and automatic messages are marked so nobody confuses them.
 */
export default function Conversation({ messages, viewerId }: { messages: TicketMessage[]; viewerId: number }) {
    return (
        <ol className="flex flex-col gap-4" aria-label="Conversación">
            {messages.map((message) => {
                const isMine = message.author?.id === viewerId;
                const isNote = message.type === 'internal_note';
                const isAutomated = message.type === 'automated';

                return (
                    <li key={message.id} className={classNames('flex gap-3', isMine && 'flex-row-reverse')}>
                        {message.author ? (
                            <Avatar initials={message.author.initials} />
                        ) : (
                            <span
                                aria-hidden="true"
                                className="grid size-10 shrink-0 place-items-center rounded-full bg-blush text-wine"
                            >
                                <Icon name="spark" size={18} />
                            </span>
                        )}
                        <div
                            className={classNames(
                                'max-w-[min(86%,640px)] px-4 py-3',
                                isNote && 'rounded-2xl border-2 border-dashed border-[#b88a1c] bg-[#fff7e0] text-ink',
                                !isNote && isMine && 'rounded-[16px_4px_16px_16px] bg-wine text-white',
                                !isNote && !isMine && 'rounded-[4px_16px_16px_16px] border border-line bg-white/85',
                            )}
                        >
                            {isNote && (
                                <p className="mb-1 flex items-center gap-1.5 text-xs font-extrabold text-warning">
                                    <Icon name="lock" size={14} />
                                    Nota interna · solo la ve TI
                                </p>
                            )}
                            <p className="whitespace-pre-line">{message.body}</p>
                            {message.attachments && message.attachments.length > 0 && (
                                <ul className="mt-2 flex flex-wrap gap-1.5" aria-label="Archivos adjuntos">
                                    {message.attachments.map((attachment) => (
                                        <li key={attachment.id}>
                                            <a
                                                href={attachment.url}
                                                className={classNames(
                                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold underline-offset-2 hover:underline',
                                                    !isNote && isMine ? 'border-white/40 text-white' : 'border-line bg-white/80 text-wine',
                                                )}
                                            >
                                                <Icon name="clip" size={14} />
                                                {attachment.name}
                                                <span className="font-semibold opacity-80">{formatFileSize(attachment.size_bytes)}</span>
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <p
                                className={classNames(
                                    'mt-1 text-xs font-bold',
                                    !isNote && isMine ? 'text-blush' : 'text-muted',
                                )}
                            >
                                {message.author?.name ?? 'Mensaje automático'}
                                {isAutomated && message.author && ' · automático'} · {formatRelative(message.created_at)}
                            </p>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
