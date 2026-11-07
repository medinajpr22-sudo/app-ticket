import Icon from '@/Components/ui/Icon';
import { formatFileSize } from '@/lib/formatFileSize';
import { TicketAttachment } from '@/types';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';

/**
 * An image of the conversation, enlarged over the page. Escape, the close
 * button or a click outside the picture close it.
 */
export default function ImageLightbox({
    image,
    onClose,
}: {
    image: TicketAttachment | null;
    onClose: () => void;
}) {
    return (
        <Dialog open={image !== null} onClose={onClose} className="relative z-50">
            <div aria-hidden="true" className="fixed inset-0 bg-[rgb(20_4_6/0.82)] backdrop-blur-sm" />

            <div className="fixed inset-0 flex items-center justify-center p-4">
                {image && (
                    <DialogPanel className="flex max-h-full w-full max-w-5xl flex-col gap-3">
                        <div className="flex flex-wrap items-center gap-3 text-white">
                            <DialogTitle className="min-w-0 flex-1 truncate font-extrabold">
                                {image.name}
                                <span className="ms-2 text-sm font-semibold opacity-80">{formatFileSize(image.size_bytes)}</span>
                            </DialogTitle>
                            <a
                                href={image.url}
                                className="inline-flex min-h-10 items-center gap-2 rounded-full border border-white/40 px-4 text-sm font-bold hover:bg-white/10"
                            >
                                <Icon name="down" size={16} />
                                Descargar
                            </a>
                            <button
                                type="button"
                                onClick={onClose}
                                className="grid size-10 place-items-center rounded-full border border-white/40 hover:bg-white/10"
                            >
                                <Icon name="x" size={20} />
                                <span className="sr-only">Cerrar vista previa</span>
                            </button>
                        </div>
                        <img
                            src={image.preview_url ?? image.url}
                            alt={image.name}
                            className="mx-auto max-h-[80vh] w-auto rounded-2xl bg-white object-contain shadow-2xl"
                        />
                    </DialogPanel>
                )}
            </div>
        </Dialog>
    );
}
