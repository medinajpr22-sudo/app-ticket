import { DialogTitle } from '@headlessui/react';
import { PropsWithChildren } from 'react';
import Modal from './Modal';
import PrimaryButton from './PrimaryButton';
import SecondaryButton from './SecondaryButton';

/**
 * Asks before doing something the person may not want to undo.
 */
export default function ConfirmDialog({
    show,
    title,
    confirmLabel,
    cancelLabel = 'Cancelar',
    processing = false,
    onConfirm,
    onClose,
    children,
}: PropsWithChildren<{
    show: boolean;
    title: string;
    confirmLabel: string;
    cancelLabel?: string;
    processing?: boolean;
    onConfirm: () => void;
    onClose: () => void;
}>) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <DialogTitle className="text-xl font-extrabold">{title}</DialogTitle>
                <div className="mt-2 text-muted">{children}</div>
                <div className="mt-6 flex flex-wrap justify-end gap-3">
                    <SecondaryButton onClick={onClose} disabled={processing}>
                        {cancelLabel}
                    </SecondaryButton>
                    <PrimaryButton type="button" onClick={onConfirm} disabled={processing}>
                        {confirmLabel}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
