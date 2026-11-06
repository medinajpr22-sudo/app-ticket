import PrimaryButton from '@/Components/ui/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verificar correo" />

            <div className="mb-4 text-muted">
                Antes de continuar, verifica tu correo electrónico con el enlace
                que te enviamos. Si no te llegó, podemos enviarte otro.
            </div>

            {status === 'verification-link-sent' && (
                <div role="status" className="mb-4 rounded-xl bg-success/10 px-4 py-3 font-bold text-success">
                    Te enviamos un nuevo enlace de verificación.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        Reenviar correo de verificación
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-sm font-extrabold text-wine underline underline-offset-4 hover:text-crimson-dark"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
