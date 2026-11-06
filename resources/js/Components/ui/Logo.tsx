import logoUrl from '../../../images/logo.webp';

/**
 * Company logo. The source image is a square with a wine background,
 * so it is cropped as a background to fit wide containers.
 */
export default function Logo({ className = '' }: { className?: string }) {
    return (
        <div
            role="img"
            aria-label="MoMentum Tech"
            className={`rounded-xl bg-[#55131a] bg-center bg-no-repeat ${className}`}
            style={{ backgroundImage: `url(${logoUrl})`, backgroundSize: '118%' }}
        />
    );
}
