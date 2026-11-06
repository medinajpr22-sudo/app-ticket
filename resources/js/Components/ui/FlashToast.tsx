import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from './Icon';

/**
 * Shows the server's flash message for a few seconds. Screen readers hear it
 * through the polite live region.
 */
export default function FlashToast() {
    const { flash } = usePage<PageProps>().props;
    const [message, setMessage] = useState<string | null>(null);

    useEffect(() => {
        if (!flash.success) {
            return;
        }

        setMessage(flash.success);

        const timeout = setTimeout(() => setMessage(null), 3500);

        return () => clearTimeout(timeout);
    }, [flash]);

    return (
        <div role="status" aria-live="polite" className="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4">
            {message && (
                <p className="flex items-center gap-2 rounded-2xl border border-white/30 bg-wine px-6 py-3.5 font-bold text-white shadow-[0_10px_30px_rgb(0_0_0/0.3)]">
                    <Icon name="check" size={18} />
                    {message}
                </p>
            )}
        </div>
    );
}
