export default function Avatar({
    initials,
    size = 'md',
    tone = 'dark',
    className = '',
}: {
    initials: string;
    size?: 'sm' | 'md';
    tone?: 'dark' | 'light';
    className?: string;
}) {
    const sizeClasses = size === 'sm' ? 'size-8 text-xs' : 'size-10 text-sm';
    const toneClasses = tone === 'light' ? 'bg-white text-wine' : 'bg-wine text-white';

    return (
        <span
            aria-hidden="true"
            className={`grid shrink-0 place-items-center rounded-full font-extrabold ${sizeClasses} ${toneClasses} ${className}`}
        >
            {initials}
        </span>
    );
}
