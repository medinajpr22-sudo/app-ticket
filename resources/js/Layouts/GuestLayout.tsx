import Logo from '@/Components/ui/Logo';
import { PropsWithChildren } from 'react';
import heroUrl from '../../images/hero.webp';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <main
            className="grid min-h-screen place-items-center bg-cover bg-top-left p-6"
            style={{
                backgroundImage: `linear-gradient(rgb(40 6 9 / 0.25), rgb(40 6 9 / 0.25)), url(${heroUrl})`,
            }}
        >
            <div className="glass w-full max-w-[420px] bg-white/80 p-8">
                <Logo className="mb-6 h-20" />
                {children}
            </div>
        </main>
    );
}
