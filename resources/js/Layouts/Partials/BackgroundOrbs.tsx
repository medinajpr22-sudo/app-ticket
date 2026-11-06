/**
 * Soft blurred color blobs behind the glass surfaces, as in the prototype.
 */
export default function BackgroundOrbs() {
    return (
        <div aria-hidden="true" className="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-[linear-gradient(135deg,#fbf5f4,#efdfdf)]">
            <i className="animate-drift absolute -top-[12vw] -right-[8vw] size-[42vw] rounded-full bg-[#e0343c] opacity-40 blur-[80px]" />
            <i className="animate-drift absolute -bottom-[16vw] left-[6vw] size-[46vw] rounded-full bg-[#7d2227] opacity-40 blur-[80px] [animation-duration:32s]" />
            <i className="animate-drift absolute top-[38%] left-[52%] size-[26vw] rounded-full bg-[#f3a3a6] opacity-40 blur-[80px] [animation-duration:22s]" />
        </div>
    );
}
