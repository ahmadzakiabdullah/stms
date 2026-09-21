import { type ImgHTMLAttributes, type ReactNode, type SyntheticEvent, useEffect, useState } from 'react';

type Props = Omit<ImgHTMLAttributes<HTMLImageElement>, 'onError'> & {
    fallback?: ReactNode;
    onImageError?: (event: SyntheticEvent<HTMLImageElement, Event>) => void;
};

export default function SafeImage({ src, fallback = null, onImageError, ...props }: Props) {
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        setFailed(false);
    }, [src]);

    if (!src || failed) return <>{fallback}</>;

    return (
        <img
            {...props}
            src={src}
            onError={(event) => {
                setFailed(true);
                onImageError?.(event);
            }}
        />
    );
}
