import AppLogoIcon from './app-logo-icon';

const appName = import.meta.env.VITE_APP_NAME || 'Subway Guatemala';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
                <AppLogoIcon className="size-8 object-contain" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">{appName}</span>
            </div>
        </>
    );
}
