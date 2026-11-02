import { motion, useInView } from 'framer-motion';
import { useRef } from 'react';

/**
 * Elemen yang muncul perlahan ketika masuk ke viewport.
 *
 * Memakai Framer Motion: useInView untuk deteksi viewport, motion.div untuk
 * animasi. Tetap menjaga fallback untuk browser tanpa JS/motion, dan
 * menghormati preferensi "reduce motion".
 */
const springPreset = {
    type: 'spring',
    stiffness: 80,
    damping: 18,
    mass: 0.6,
};

export default function Reveal({
    as: Tag = 'div',
    delay = 0,
    className = '',
    children,
    y = 24,
}) {
    const ref = useRef(null);
    const MotionTag = Tag === 'div' ? motion.div : motion[Tag];
    const isInView = useInView(ref, { once: true, margin: '-60px' });

    const prefersReducedMotion =
        typeof window !== 'undefined' &&
        window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion) {
        return (
            <Tag ref={ref} className={className}>
                {children}
            </Tag>
        );
    }

    return (
        <MotionTag
            ref={ref}
            initial={{ opacity: 0, y }}
            animate={isInView ? { opacity: 1, y: 0 } : { opacity: 0, y }}
            transition={{ ...springPreset, delay: delay / 1000 }}
            className={className}
        >
            {children}
        </MotionTag>
    );
}
