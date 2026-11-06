import Avatar from '@/Components/ui/Avatar';
import Dropdown from '@/Components/ui/Dropdown';
import Icon from '@/Components/ui/Icon';
import { User } from '@/types';

export default function UserMenu({ user }: { user: User }) {
    return (
        <Dropdown>
            <Dropdown.Trigger>
                <button
                    type="button"
                    className="flex items-center gap-2.5 rounded-full py-1 ps-1 pe-3 text-start hover:bg-white/60"
                >
                    <Avatar initials={user.initials} />
                    <span className="hidden leading-tight sm:block">
                        <span className="block font-extrabold">{user.name}</span>
                        <span className="block text-xs font-semibold text-muted">
                            {user.role?.label}
                        </span>
                    </span>
                    <Icon name="chevronDown" size={18} className="text-muted" />
                    <span className="sr-only">Abrir menú de usuario</span>
                </button>
            </Dropdown.Trigger>

            <Dropdown.Content width="56">
                <Dropdown.Link href={route('profile.edit')}>
                    <Icon name="user" size={18} />
                    Mi perfil
                </Dropdown.Link>
                <Dropdown.Link href={route('logout')} method="post" as="button">
                    <Icon name="logout" size={18} />
                    Cerrar sesión
                </Dropdown.Link>
            </Dropdown.Content>
        </Dropdown>
    );
}
