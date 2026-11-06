const SPLIT_ON_TOKENS = /(\{\{\s*[^{}\s]+\s*\}\})/;
const WHOLE_TOKEN = /^\{\{\s*[^{}\s]+\s*\}\}$/;

/**
 * Shows a template body with its {{variables}} highlighted.
 */
export default function TemplateText({ body, className = '' }: { body: string; className?: string }) {
    return (
        <p className={`whitespace-pre-line ${className}`}>
            {body.split(SPLIT_ON_TOKENS).map((part, index) =>
                WHOLE_TOKEN.test(part) ? (
                    <code
                        key={index}
                        className="rounded-md border border-crimson/25 bg-crimson/8 px-1 font-sans text-[0.85em] font-bold text-crimson-dark"
                    >
                        {part}
                    </code>
                ) : (
                    part
                ),
            )}
        </p>
    );
}
