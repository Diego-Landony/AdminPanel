import { ImgHTMLAttributes } from 'react';

interface AppLogoIconProps extends Omit<ImgHTMLAttributes<HTMLImageElement>, 'src' | 'alt'> {
    className?: string;
}

export default function AppLogoIcon({ className = 'size-5', ...props }: AppLogoIconProps) {
    return <img src="/subway-icon.png" alt="Subway" className={className} {...props} />;
}
