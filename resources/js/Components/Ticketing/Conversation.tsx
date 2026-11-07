import Avatar from '@/Components/ui/Avatar';
import Icon from '@/Components/ui/Icon';
import { classNames } from '@/lib/classNames';
import { formatFileSize } from '@/lib/formatFileSize';
import { formatRelative } from '@/lib/formatDate';
import { TicketAttachment, TicketMessage } from '@/types';
import { useState } from 'react';
import ImageLightbox from './ImageLightbox';

/**
 * The ticket's messages in order. The viewer's own messages sit on the right
 * and automatic messages are marked so nobody confuses them. Notes only IT
 * reads, such as who took or transferred the ticket, appear as a quiet line
 * of activity rather than as part of the chat.
 */
export default function Conversation({ messages, viewerId }: { messages: TicketMessage[]; viewerId: number }) {
    const [preview, setPreview] = useState<TicketAttachment | null>(null);

    return (
        <>
            <ol className="flex flex-col gap-4" aria-label="Conversación">
                {messages.map((message) =>
                    message.type === 'internal_note' ? (
                        <ActivityLine key={message.id} message={message} />
                    ) : (
                        <ChatMessage
                            key={message.id}
                            message={message}
                            isMine={message.author?.id === viewerId}
                            onPreview={setPreview}
                        />
                    ),
                )}
            </ol>

            <ImageLightbox image={preview} onClose={() => setPreview(null)} />
        </>
    );
}

function ActivityLine({ message }: { message: TicketMessage }) {
    return (
        <li className="flex items-start justify-center gap-2 px-6 text-center text-sm text-muted">
            <Icon name="lock" size={14} className="mt-0.5 shrink-0" />
            <p>
                <span className="font-semibold">{message.body}</span>
                <span className="block text-xs">
                    {message.author?.name ?? 'Sistema'} · {formatRelative(message.created_at)} · solo lo ve TI
                </span>
            </p>
        </li>
    );
}

function ChatMessage({
    message,
    isMine,
    onPreview,
}: {
    message: TicketMessage;
    isMine: boolean;
    onPreview: (attachment: TicketAttachment) => void;
}) {
    const attachments = message.attachments ?? [];
    const images = attachments.filter((attachment) => attachment.preview_url !== null);
    const files = attachments.filter((attachment) => attachment.preview_url === null);

    return (
        <li className={classNames('flex gap-3', isMine && 'flex-row-reverse')}>
            {message.author ? (
                <Avatar initials={message.author.initials} />
            ) : (
                <span aria-hidden="true" className="grid size-10 shrink-0 place-items-center rounded-full bg-blush text-wine">
                    <Icon name="spark" size={18} />
                </span>
            )}
            <div
                className={classNames(
                    'max-w-[min(86%,640px)] px-4 py-3',
                    isMine ? 'rounded-[16px_4px_16px_16px] bg-wine text-white' : 'rounded-[4px_16px_16px_16px] border border-line bg-white/85',
                )}
            >
                <p className="whitespace-pre-line">{message.body}</p>

                {images.length > 0 && (
                    <ul className="mt-2 flex flex-wrap gap-2" aria-label="Imágenes adjuntas">
                        {images.map((image) => (
                            <li key={image.id}>
                                <button
                                    type="button"
                                    onClick={() => onPreview(image)}
                                    className={classNames(
                                        'block overflow-hidden rounded-xl border-2 transition hover:opacity-90 focus-visible:outline-offset-2',
                                        isMine ? 'border-white/40' : 'border-line',
                                    )}
                                >
                                    <img
                                        src={image.preview_url ?? undefined}
                                        alt={image.name}
                                        loading="lazy"
                                        className="h-28 w-40 bg-white object-cover"
                                    />
                                    <span className="sr-only">Ver {image.name} en grande</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}

                {files.length > 0 && (
                    <ul className="mt-2 flex flex-wrap gap-1.5" aria-label="Archivos adjuntos">
                        {files.map((attachment) => (
                            <li key={attachment.id}>
                                <a
                                    href={attachment.url}
                                    className={classNames(
                                        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold underline-offset-2 hover:underline',
                                        isMine ? 'border-white/40 text-white' : 'border-line bg-white/80 text-wine',
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

                <p className={classNames('mt-1 text-xs font-bold', isMine ? 'text-blush' : 'text-muted')}>
                    {message.author?.name ?? 'Mensaje automático'}
                    {message.type === 'automated' && message.author && ' · automático'} · {formatRelative(message.created_at)}
                </p>
            </div>
        </li>
    );
}
