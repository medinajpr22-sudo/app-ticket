import Checkbox from '@/Components/ui/Checkbox';
import FormField from '@/Components/ui/FormField';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import TextInput from '@/Components/ui/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />

            <h1 className="text-3xl font-extrabold tracking-tight">Mesa de Ayuda TI</h1>
            <p className="mt-1 text-muted">Inicia sesión para continuar</p>

            {status && (
                <p role="status" className="mt-4 rounded-xl bg-success/10 px-4 py-3 font-bold text-success">
                    {status}
                </p>
            )}

            <form onSubmit={submit} noValidate className="mt-6 flex flex-col gap-4">
                <FormField id="email" label="Correo electrónico" error={errors.email}>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoComplete="username"
                        isFocused={true}
                        aria-invalid={errors.email ? true : undefined}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                </FormField>

                <FormField id="password" label="Contraseña" error={errors.password}>
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="current-password"
                        aria-invalid={errors.password ? true : undefined}
                        onChange={(e) => setData('password', e.target.value)}
                    />
                </FormField>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <label className="flex items-center gap-2.5 font-bold">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        Recordarme
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm font-extrabold text-wine underline underline-offset-4 hover:text-crimson-dark"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    )}
                </div>

                <PrimaryButton className="mt-2 w-full" disabled={processing}>
                    Entrar
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
